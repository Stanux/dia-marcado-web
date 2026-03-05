<?php

namespace App\Filament\Resources\WeddingGuestResource\Pages;

use App\Filament\Resources\WeddingGuestResource;
use App\Services\Guests\WeddingGuestSpreadsheetImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Attributes\On;

class ListWeddingGuests extends ListRecords
{
    protected static string $resource = WeddingGuestResource::class;

    /**
     * @var array{
     *     processed_rows: int,
     *     created: int,
     *     duplicates: int,
     *     errors: int,
     *     duplicate_rows: array<int, string>,
     *     error_rows: array<int, string>
     * }
     */
    public array $importResultModal = [
        'processed_rows' => 0,
        'created' => 0,
        'duplicates' => 0,
        'errors' => 0,
        'duplicate_rows' => [],
        'error_rows' => [],
    ];

    #[On('topbar-wedding-guest-open-import')]
    public function openImportModalFromTopbar(): void
    {
        $this->mountAction('importSpreadsheet');
    }

    public function getTitle(): string
    {
        return 'Convidados';
    }

    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importSpreadsheet')
                ->label('Importar Arquivo')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalHeading('Importar convidados (.xlsx)')
                ->modalDescription('Use o arquivo modelo para garantir a estrutura correta dos dados.')
                ->modalSubmitActionLabel('Importar')
                ->form([
                    Forms\Components\Placeholder::make('template')
                        ->label('Arquivo modelo')
                        ->content(fn (): HtmlString => new HtmlString(
                            '<a href="' . e(route('admin.guests-v2.import-template')) . '" target="_blank" class="text-primary-600 underline">Baixar modelo (.xlsx)</a>'
                        )),

                    Forms\Components\FileUpload::make('file')
                        ->label('Arquivo de importação')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->maxFiles(1)
                        ->maxSize(10240)
                        ->directory('imports/wedding-guests-v2')
                        ->disk('local')
                        ->storeFiles()
                        ->required()
                        ->helperText('Estrutura obrigatória: Nome, Apelido, E-mail, Telefone, Contato Principal, Lado, Criança.'),
                ])
                ->action(function (array $data): void {
                    $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');
                    if (!$weddingId) {
                        Notification::make()
                            ->title('Nenhum casamento selecionado')
                            ->body('Selecione um casamento antes de importar convidados.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $path = $this->resolveStoredFilePath($data['file'] ?? null);
                    if (!$path || !is_readable($path)) {
                        Notification::make()
                            ->title('Arquivo inválido')
                            ->body('Não foi possível localizar o arquivo enviado no armazenamento. Reenvie e tente novamente.')
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        $result = app(WeddingGuestSpreadsheetImportService::class)->import(
                            $path,
                            (string) $weddingId,
                            auth()->id() ? (string) auth()->id() : null
                        );
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title('Importação não realizada')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                        return;
                    } catch (\Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Erro inesperado na importação')
                            ->body('Não foi possível concluir a importação. Tente novamente.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $this->prepareImportResultModalData($result);
                    $this->replaceMountedAction('showImportResult');
                }),

            Actions\Action::make('showImportResult')
                ->label('Resultado da Importação')
                ->modalHeading('Resultado da importação de convidados')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Fechar')
                ->modalWidth('3xl')
                ->modalContent(fn (): HtmlString => $this->buildImportResultModalContent())
                ->action(static function (): void {
                }),
        ];
    }

    /**
     * @param mixed $file
     */
    private function resolveStoredFilePath(mixed $file): ?string
    {
        if (is_string($file) && $file !== '') {
            return $this->resolveDiskPath($file);
        }

        if (is_array($file)) {
            if (!empty($file['path']) && is_string($file['path'])) {
                return $this->resolveDiskPath($file['path']);
            }

            foreach ($file as $item) {
                $resolved = $this->resolveStoredFilePath($item);
                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }

        return null;
    }

    private function resolveDiskPath(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        if (is_readable($path)) {
            return $path;
        }

        $relativePath = ltrim($path, '/');
        $disk = Storage::disk('local');

        if (!$disk->exists($relativePath)) {
            return null;
        }

        return $disk->path($relativePath);
    }

    /**
     * @param array{
     *     processed_rows?: int,
     *     created?: int,
     *     duplicates?: int,
     *     errors?: int,
     *     rows?: array<int, array{row: int, status: string, message: string, name: string}>
     * } $result
     */
    private function prepareImportResultModalData(array $result): void
    {
        $duplicateRows = collect($result['rows'] ?? [])
            ->filter(fn (array $row): bool => ($row['status'] ?? '') === 'duplicate')
            ->take(12)
            ->map(fn (array $row): string => 'Linha ' . $row['row'] . ': ' . ($row['message'] ?? 'Duplicado'))
            ->values()
            ->all();

        $errorRows = collect($result['rows'] ?? [])
            ->filter(fn (array $row): bool => ($row['status'] ?? '') === 'error')
            ->take(20)
            ->map(fn (array $row): string => 'Linha ' . $row['row'] . ': ' . ($row['message'] ?? 'Erro de validação'))
            ->values()
            ->all();

        $this->importResultModal = [
            'processed_rows' => (int) ($result['processed_rows'] ?? 0),
            'created' => (int) ($result['created'] ?? 0),
            'duplicates' => (int) ($result['duplicates'] ?? 0),
            'errors' => (int) ($result['errors'] ?? 0),
            'duplicate_rows' => $duplicateRows,
            'error_rows' => $errorRows,
        ];
    }

    private function buildImportResultModalContent(): HtmlString
    {
        $summary = $this->importResultModal;

        $metrics = implode('', [
            $this->buildMetricHtml('Linhas processadas', (string) $summary['processed_rows']),
            $this->buildMetricHtml('Importados', (string) $summary['created']),
            $this->buildMetricHtml('Duplicados ignorados', (string) $summary['duplicates']),
            $this->buildMetricHtml('Erros', (string) $summary['errors']),
        ]);

        $statusText = ($summary['errors'] > 0 || $summary['duplicates'] > 0)
            ? '<p class="text-sm text-warning-700 dark:text-warning-400">A importação foi concluída com pendências.</p>'
            : '<p class="text-sm text-success-700 dark:text-success-400">A importação foi concluída com sucesso.</p>';

        $duplicateSection = '';
        if ($summary['duplicate_rows'] !== []) {
            $duplicateSection .= '<div class="space-y-2">';
            $duplicateSection .= '<h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Duplicados</h4>';
            $duplicateSection .= '<ul class="list-disc space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300">';
            foreach ($summary['duplicate_rows'] as $line) {
                $duplicateSection .= '<li>' . e($line) . '</li>';
            }
            if ($summary['duplicates'] > count($summary['duplicate_rows'])) {
                $remaining = $summary['duplicates'] - count($summary['duplicate_rows']);
                $duplicateSection .= '<li>... e mais ' . e((string) $remaining) . ' duplicado(s).</li>';
            }
            $duplicateSection .= '</ul>';
            $duplicateSection .= '</div>';
        }

        $errorSection = '';
        if ($summary['error_rows'] !== []) {
            $errorSection .= '<div class="space-y-2">';
            $errorSection .= '<h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Erros encontrados</h4>';
            $errorSection .= '<ul class="list-disc space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300">';
            foreach ($summary['error_rows'] as $line) {
                $errorSection .= '<li>' . e($line) . '</li>';
            }
            if ($summary['errors'] > count($summary['error_rows'])) {
                $remaining = $summary['errors'] - count($summary['error_rows']);
                $errorSection .= '<li>... e mais ' . e((string) $remaining) . ' erro(s).</li>';
            }
            $errorSection .= '</ul>';
            $errorSection .= '</div>';
        }

        return new HtmlString(
            '<div class="space-y-4">' .
                $statusText .
                '<dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">' . $metrics . '</dl>' .
                $duplicateSection .
                $errorSection .
            '</div>'
        );
    }

    private function buildMetricHtml(string $label, string $value): string
    {
        return '<div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-white/10 dark:bg-white/5">' .
            '<dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">' . e($label) . '</dt>' .
            '<dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">' . e($value) . '</dd>' .
        '</div>';
    }
}
