<?php

namespace App\Filament\Resources\GuestResource\Pages;

use App\Filament\Resources\GuestResource;
use App\Models\Guest;
use App\Models\GuestHousehold;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportGuests extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = GuestResource::class;

    protected static string $view = 'filament.resources.guest-resource.pages.import-guests';

    public ?array $data = [];

    public array $headers = [];

    public array $preview = [];

    public array $stats = [];

    public array $dedupe = [];

    public function mount(): void
    {
        $this->form->fill([
            'create_households' => true,
            'import_duplicates' => false,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Arquivo')
                    ->schema([
                        Forms\Components\FileUpload::make('file')
                            ->label('Arquivo de convidados (CSV/XLSX)')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->multiple(false)
                            ->maxFiles(1)
                            ->disk('local')
                            ->directory('imports')
                            ->storeFiles()
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
                                return $file->store('imports');
                            })
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                $this->loadPreview($state);
                            })
                            ->helperText('Use o modelo. A primeira linha deve conter os cabeçalhos.'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Mapeamento de colunas')
                    ->schema($this->getMappingFields())
                    ->visible(fn () => count($this->headers) > 0),

                Forms\Components\Section::make('Opções')
                    ->schema([
                        Forms\Components\Toggle::make('create_households')
                            ->label('Criar núcleos automaticamente')
                            ->inline(false),

                        Forms\Components\Toggle::make('import_duplicates')
                            ->label('Importar duplicados')
                            ->inline(false)
                            ->helperText('Se desativado, emails/telefones já cadastrados serão ignorados.'),
                    ])
                    ->columns(2)
                    ->visible(fn () => count($this->headers) > 0),
            ])
            ->statePath('data');
    }

    public function loadPreview($fileState = null): void
    {
        $this->headers = [];
        $this->preview = [];
        $this->stats = [];
        $this->dedupe = [];

        $file = $fileState ?? ($this->data['file'] ?? null);
        if (!$file && !empty($this->data['stored_path'])) {
            $file = $this->data['stored_path'];
        }
        $file = $this->normalizeFileState($file);
        if (!$file) {
            return;
        }

        $file = $this->persistFile($file);
        if (!$file) {
            return;
        }

        $path = $this->resolvePath($file);
        if (!$path || !is_readable($path)) {
            return;
        }

        $extension = $this->resolveExtension($file, $path);
        $delimiter = $this->detectDelimiter($path, $extension);
        [$headers, $rows] = $this->readRows($path, $delimiter, true, 10, $extension);

        $this->headers = $headers;
        $this->preview = $rows;

        $this->data['mapping'] = $this->guessMapping($headers, $this->data['mapping'] ?? []);
    }

    protected function normalizeFileState($file)
    {
        if ($file instanceof TemporaryUploadedFile) {
            return $file;
        }

        if (is_string($file)) {
            return $file;
        }

        if (is_array($file)) {
            if (array_key_exists('path', $file) || array_key_exists('tmp_name', $file) || array_key_exists('name', $file)) {
                return $file;
            }

            foreach ($file as $value) {
                $normalized = $this->normalizeFileState($value);
                if ($normalized) {
                    return $normalized;
                }
            }
        }

        return null;
    }

    protected function persistFile($file)
    {
        if (is_string($file)) {
            if (empty($this->data['stored_path'])) {
                $this->data['stored_path'] = $file;
            }
            return $file;
        }

        if (is_array($file) && !empty($file['path'])) {
            $this->data['stored_path'] = $file['path'];
            $this->data['file'] = $file['path'];
            return $file['path'];
        }

        if ($file instanceof TemporaryUploadedFile) {
            $stored = $file->store('imports');
            if ($stored) {
                $this->data['stored_path'] = $stored;
                $this->data['file'] = $stored;
                return $stored;
            }
        }

        return $file;
    }

    public function analyze(): void
    {
        $file = $this->data['file'] ?? null;
        if (!$file && !empty($this->data['stored_path'])) {
            $file = $this->data['stored_path'];
        }
        $file = $this->normalizeFileState($file);
        if (!$file) {
            Notification::make()
                ->title('Selecione um arquivo para analisar')
                ->warning()
                ->send();
            return;
        }

        $file = $this->persistFile($file);
        if (!$file) {
            Notification::make()
                ->title('Arquivo indisponível')
                ->body('Reenvie o arquivo para continuar.')
                ->danger()
                ->send();
            return;
        }

        $path = $this->resolvePath($file);
        if (!$path || !is_readable($path)) {
            \Log::warning('Guest import analyze: arquivo indisponível', [
                'file_type' => is_object($file) ? get_class($file) : gettype($file),
                'file_value' => is_string($file) ? $file : null,
                'stored_path' => $this->data['stored_path'] ?? null,
                'resolved_path' => $path,
            ]);
            Notification::make()
                ->title('Arquivo indisponível')
                ->body('Reenvie o arquivo para continuar.')
                ->danger()
                ->send();
            return;
        }
        $extension = $this->resolveExtension($file, $path);
        $delimiter = $this->detectDelimiter($path, $extension);
        if (!$this->assertConsistentRows($path, $delimiter, $extension)) {
            return;
        }
        [$headers, $rows] = $this->readRows($path, $delimiter, true, 0, $extension);

        if (!$headers || !$rows) {
            Notification::make()
                ->title('Não foi possível ler o arquivo')
                ->body('Verifique se o arquivo está no formato correto (CSV ou XLSX).')
                ->danger()
                ->send();
            return;
        }

        $mapping = $this->data['mapping'] ?? [];
        if (empty($mapping['name'])) {
            $mapping = $this->guessMapping($headers, $mapping);
            $this->data['mapping'] = $mapping;
        }

        if (empty($mapping['name'])) {
            Notification::make()
                ->title('Mapeamento inválido')
                ->body('Você precisa mapear a coluna de Nome.')
                ->danger()
                ->send();
            return;
        }

        $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');
        $existingGuests = Guest::where('wedding_id', $weddingId)
            ->select('id', 'name', 'email', 'phone')
            ->get();

        $existingEmails = $existingGuests
            ->pluck('email')
            ->filter()
            ->map(fn ($email) => $this->normalizeEmail($email))
            ->toArray();
        $existingPhones = $existingGuests
            ->pluck('phone')
            ->filter()
            ->map(fn ($phone) => $this->normalizePhone($phone))
            ->toArray();

        $seenEmails = [];
        $seenPhones = [];

        $total = 0;
        $valid = 0;
        $duplicates = 0;
        $invalid = 0;
        $issues = [];

        foreach ($rows as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }
            $total++;
            $payload = $this->mapRow($headers, $row, $mapping);

            $name = trim((string) ($payload['name'] ?? ''));
            if ($name === '') {
                $invalid++;
                $issues[] = [
                    'row' => $index + 1,
                    'issue' => 'Nome ausente',
                ];
                continue;
            }

            $email = $this->normalizeEmail($payload['email'] ?? null);
            $phone = $this->normalizePhone($payload['phone'] ?? null);

            $isDuplicate = false;
            if ($email && in_array($email, $existingEmails, true)) {
                $isDuplicate = true;
            }
            if ($phone && in_array($phone, $existingPhones, true)) {
                $isDuplicate = true;
            }
            if ($email && in_array($email, $seenEmails, true)) {
                $isDuplicate = true;
            }
            if ($phone && in_array($phone, $seenPhones, true)) {
                $isDuplicate = true;
            }

            if ($isDuplicate) {
                $duplicates++;
            } else {
                $valid++;
            }

            if ($email) {
                $seenEmails[] = $email;
            }
            if ($phone) {
                $seenPhones[] = $phone;
            }
        }

        $this->stats = compact('total', 'valid', 'duplicates', 'invalid');
        $this->dedupe = $issues;

        Notification::make()
            ->title('Análise concluída')
            ->success()
            ->send();
    }

    public function import(): void
    {
        $file = $this->data['file'] ?? null;
        if (!$file && !empty($this->data['stored_path'])) {
            $file = $this->data['stored_path'];
        }
        $file = $this->normalizeFileState($file);
        $mapping = $this->data['mapping'] ?? [];
        if (!$file) {
            Notification::make()
                ->title('Arquivo ou mapeamento inválido')
                ->danger()
                ->send();
            return;
        }

        $file = $this->persistFile($file);
        if (!$file) {
            Notification::make()
                ->title('Arquivo indisponível')
                ->body('Reenvie o arquivo para continuar.')
                ->danger()
                ->send();
            return;
        }

        $path = $this->resolvePath($file);
        if (!$path || !is_readable($path)) {
            \Log::warning('Guest import: arquivo indisponível', [
                'file_type' => is_object($file) ? get_class($file) : gettype($file),
                'file_value' => is_string($file) ? $file : null,
                'stored_path' => $this->data['stored_path'] ?? null,
                'resolved_path' => $path,
            ]);
            Notification::make()
                ->title('Arquivo indisponível')
                ->body('Reenvie o arquivo para continuar.')
                ->danger()
                ->send();
            return;
        }
        $extension = $this->resolveExtension($file, $path);
        $delimiter = $this->detectDelimiter($path, $extension);
        if (!$this->assertConsistentRows($path, $delimiter, $extension)) {
            return;
        }
        [$headers, $rows] = $this->readRows($path, $delimiter, true, 0, $extension);

        if (!$headers || !$rows) {
            Notification::make()
                ->title('Não foi possível ler o arquivo')
                ->body('Verifique se o arquivo está no formato correto (CSV ou XLSX).')
                ->danger()
                ->send();
            return;
        }

        if (empty($mapping['name'])) {
            $mapping = $this->guessMapping($headers, $mapping);
            $this->data['mapping'] = $mapping;
        }

        if (empty($mapping['name'])) {
            Notification::make()
                ->title('Mapeamento inválido')
                ->body('Você precisa mapear a coluna de Nome.')
                ->danger()
                ->send();
            return;
        }

        $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');
        $existingEmails = Guest::where('wedding_id', $weddingId)->pluck('email')->filter()->map(fn ($e) => $this->normalizeEmail($e))->toArray();
        $existingPhones = Guest::where('wedding_id', $weddingId)->pluck('phone')->filter()->map(fn ($p) => $this->normalizePhone($p))->toArray();

        $seenEmails = [];
        $seenPhones = [];
        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }
            $payload = $this->mapRow($headers, $row, $mapping);

            $name = trim((string) ($payload['name'] ?? ''));
            if ($name === '') {
                $skipped++;
                continue;
            }

            $email = $this->normalizeEmail($payload['email'] ?? null);
            $phone = $this->normalizePhone($payload['phone'] ?? null);

            $isDuplicate = false;
            if ($email && (in_array($email, $existingEmails, true) || in_array($email, $seenEmails, true))) {
                $isDuplicate = true;
            }
            if ($phone && (in_array($phone, $existingPhones, true) || in_array($phone, $seenPhones, true))) {
                $isDuplicate = true;
            }

            if ($isDuplicate && empty($this->data['import_duplicates'])) {
                $skipped++;
                continue;
            }

            $householdId = null;
            $householdName = trim((string) ($payload['household_name'] ?? ''));
            if ($householdName !== '') {
                $household = GuestHousehold::where('wedding_id', $weddingId)
                    ->whereRaw('lower(name) = ?', [Str::lower($householdName)])
                    ->first();

                if (!$household && !empty($this->data['create_households'])) {
                    $household = GuestHousehold::create([
                        'wedding_id' => $weddingId,
                        'created_by' => auth()->id(),
                        'name' => $householdName,
                    ]);
                }

                $householdId = $household?->id;
            }

            $guest = Guest::create([
                'wedding_id' => $weddingId,
                'household_id' => $householdId,
                'name' => $name,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'nickname' => $payload['nickname'] ?? null,
                'role_in_household' => $payload['role_in_household'] ?? null,
                'is_child' => $this->normalizeBoolean($payload['is_child'] ?? null),
                'category' => $payload['category'] ?? null,
                'side' => $payload['side'] ?? null,
                'status' => $payload['status'] ?? 'pending',
                'tags' => $this->normalizeTags($payload['tags'] ?? null),
                'notes' => $payload['notes'] ?? null,
            ]);

            $created++;

            if ($email) {
                $seenEmails[] = $email;
            }
            if ($phone) {
                $seenPhones[] = $phone;
            }
        }

        Notification::make()
            ->title('Importação concluída')
            ->success()
            ->body("Criados: {$created}. Ignorados: {$skipped}.")
            ->send();

        $this->redirect(GuestResource::getUrl('index'));
    }

    protected function getMappingFields(): array
    {
        $options = $this->getHeaderOptions();

        return [
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Select::make('mapping.name')
                        ->label('Nome (obrigatório)')
                        ->options($options)
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('mapping.nickname')
                        ->label('Apelido')
                        ->options($options)
                        ->native(false),

                    Forms\Components\Select::make('mapping.email')
                        ->label('Email')
                        ->options($options)
                        ->native(false),

                    Forms\Components\Select::make('mapping.phone')
                        ->label('Telefone')
                        ->options($options)
                        ->native(false),

                    Forms\Components\Select::make('mapping.household_name')
                        ->label('Núcleo')
                        ->options($options)
                        ->native(false),

                    Forms\Components\Select::make('mapping.role_in_household')
                        ->label('Papel no núcleo')
                        ->options($options)
                        ->native(false),

                    Forms\Components\Select::make('mapping.is_child')
                        ->label('Criança')
                        ->options($options)
                        ->native(false),

                    Forms\Components\Select::make('mapping.side')
                        ->label('Lado')
                        ->options($options)
                        ->native(false),

                    Forms\Components\Select::make('mapping.category')
                        ->label('Categoria')
                        ->options($options)
                        ->native(false),

                    Forms\Components\Select::make('mapping.status')
                        ->label('Status')
                        ->options($options)
                        ->native(false),

                    Forms\Components\Select::make('mapping.tags')
                        ->label('Tags')
                        ->options($options)
                        ->native(false),

                    Forms\Components\Select::make('mapping.notes')
                        ->label('Observações')
                        ->options($options)
                        ->native(false),
                ]),
        ];
    }

    protected function getHeaderOptions(): array
    {
        $options = [];
        foreach ($this->headers as $header) {
            $options[$header] = $header;
        }

        return $options;
    }

    protected function resolvePath($file): ?string
    {
        if ($file instanceof TemporaryUploadedFile) {
            if (!empty($this->data['stored_path'])) {
                $storedPath = storage_path('app/' . ltrim($this->data['stored_path'], '/'));
                if (is_readable($storedPath)) {
                    return $storedPath;
                }
            }

            $realPath = $file->getRealPath();
            if ($realPath && is_readable($realPath)) {
                return $realPath;
            }

            $stored = $file->store('imports');
            if ($stored) {
                $this->data['stored_path'] = $stored;
                $storedPath = storage_path('app/' . ltrim($stored, '/'));
                return is_readable($storedPath) ? $storedPath : null;
            }

            return null;
        }

        if (is_array($file)) {
            if (!empty($file['path'])) {
                $disk = $file['disk'] ?? config('filesystems.default');
                try {
                    $path = Storage::disk($disk)->path($file['path']);
                    if (is_readable($path)) {
                        return $path;
                    }
                } catch (\Throwable $e) {
                }

                $privatePath = storage_path('app/private/' . ltrim($file['path'], '/'));
                if (is_readable($privatePath)) {
                    return $privatePath;
                }

                return null;
            }

            if (!empty($file['tmp_name']) && is_readable($file['tmp_name'])) {
                return $file['tmp_name'];
            }
        }

        if (is_string($file)) {
            $path = storage_path('app/' . ltrim($file, '/'));
            if (file_exists($path)) {
                return $path;
            }

            $privatePath = storage_path('app/private/' . ltrim($file, '/'));
            return file_exists($privatePath) ? $privatePath : null;
        }

        return null;
    }

    protected function readRows(string $path, string $delimiter, bool $hasHeader, int $limit = 0, ?string $extensionOverride = null): array
    {
        $extension = $extensionOverride ?: Str::lower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            return $this->readSpreadsheet($path, $hasHeader, $limit);
        }

        return $this->readCsv($path, $delimiter, $hasHeader, $limit);
    }

    protected function resolveExtension($file, ?string $path = null): ?string
    {
        if ($file instanceof TemporaryUploadedFile) {
            $extension = Str::lower($file->getClientOriginalExtension() ?: '');
            if ($extension !== '') {
                return $extension;
            }

            $mime = Str::lower((string) $file->getMimeType());
            if (Str::contains($mime, 'spreadsheet') || Str::contains($mime, 'excel')) {
                return 'xlsx';
            }
        }

        if (is_array($file)) {
            $extension = Str::lower($file['extension'] ?? '');
            if ($extension !== '') {
                return $extension;
            }

            if (!empty($file['name'])) {
                $ext = Str::lower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
                if ($ext !== '') {
                    return $ext;
                }
            }
        }

        if ($path) {
            $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
            return $extension !== '' ? $extension : null;
        }

        return null;
    }

    protected function readSpreadsheet(string $path, bool $hasHeader, int $limit = 0): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $info = $reader->listWorksheetInfo($path);
        $totalRows = (int) ($info[0]['totalRows'] ?? 0);
        if ($totalRows === 0) {
            return [[], []];
        }

        $chunkSize = 500;
        $startRow = 1;
        $rows = [];
        $headers = [];

        while ($startRow <= $totalRows) {
            $endRow = $startRow + $chunkSize - 1;
            $reader->setReadFilter(new class($startRow, $endRow) implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
                public function __construct(private int $startRow, private int $endRow) {}
                public function readCell($column, $row, $worksheetName = ''): bool
                {
                    return $row >= $this->startRow && $row <= $this->endRow;
                }
            });

            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();

            foreach ($sheet->getRowIterator($startRow, $endRow) as $row) {
                $rowIndex = $row->getRowIndex();
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $data = [];
                foreach ($cellIterator as $cell) {
                    $data[] = (string) $cell->getValue();
                }

                if ($rowIndex === 1 && $hasHeader) {
                    $headers = $this->normalizeHeaders($data);
                    continue;
                }

                if (!$headers) {
                    $headers = $this->generateHeaders(count($data));
                }

                $rows[] = $data;

                if ($limit > 0 && count($rows) >= $limit) {
                    break 2;
                }
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $startRow += $chunkSize;
        }

        return [$headers, $rows];
    }

    protected function readCsv(string $path, string $delimiter, bool $hasHeader, int $limit = 0): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [[], []];
        }

        $headers = [];
        $rows = [];
        $line = 0;

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;

            if ($line === 1 && $hasHeader) {
                $headers = $this->normalizeHeaders($data);
                continue;
            }

            if (!$headers) {
                $headers = $this->generateHeaders(count($data));
            }

            $rows[] = $data;

            if ($limit > 0 && count($rows) >= $limit) {
                break;
            }
        }

        fclose($handle);

        return [$headers, $rows];
    }

    public function downloadTemplate()
    {
        $headers = [
            'nome',
            'apelido',
            'email',
            'telefone',
            'nucleo',
            'papel_no_nucleo',
            'crianca',
            'lado',
            'categoria',
            'status',
            'tags',
            'observacoes',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            'Tia Zilda',
            'Tia Zilda',
            'tia@example.com',
            '(11) 99999-0000',
            'Tio Muarílio',
            'responsavel',
            'nao',
            'noiva',
            'família',
            'pendente',
            'família,prioridade',
            'Observação opcional',
        ], null, 'A2');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'modelo-importacao-convidados.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function normalizeDelimiter(string $delimiter): string
    {
        $delimiter = trim($delimiter);
        if ($delimiter === '') {
            return ',';
        }

        return $delimiter;
    }

    protected function detectDelimiter(string $path, ?string $extension): string
    {
        if (in_array($extension, ['xlsx', 'xls'], true)) {
            return ',';
        }

        $candidates = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $bestScore = -1;

        foreach ($candidates as $candidate) {
            $rows = $this->readCsvSample($path, $candidate, 5);
            if (count($rows) < 2) {
                continue;
            }

            $lengths = array_map(fn ($row) => count($row), $rows);
            $unique = array_unique($lengths);
            $score = count($rows) - count($unique);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $candidate;
            }
        }

        return $bestDelimiter;
    }

    protected function assertConsistentRows(string $path, string $delimiter, ?string $extension): bool
    {
        if (in_array($extension, ['xlsx', 'xls'], true)) {
            return true;
        }

        $rows = $this->readCsvSample($path, $delimiter, 20);
        if (count($rows) <= 1) {
            return true;
        }

        $lengths = array_map(fn ($row) => count($row), $rows);
        $expected = $lengths[0];
        foreach ($lengths as $length) {
            if ($length !== $expected) {
                Notification::make()
                    ->title('Separador inconsistente')
                    ->body('Detectamos linhas com número de colunas diferente. Verifique o separador e o arquivo.')
                    ->danger()
                    ->send();
                return false;
            }
        }

        return true;
    }

    protected function readCsvSample(string $path, string $delimiter, int $limit): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [];
        }

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $data;
            if (count($rows) >= $limit) {
                break;
            }
        }

        fclose($handle);
        return $rows;
    }

    protected function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $header = trim((string) $header);
            return $header === '' ? 'col_' . Str::random(5) : $header;
        }, $headers);
    }

    protected function generateHeaders(int $count): array
    {
        $headers = [];
        for ($i = 1; $i <= $count; $i++) {
            $headers[] = 'col_' . $i;
        }
        return $headers;
    }

    protected function guessMapping(array $headers, array $mapping): array
    {
        $aliases = [
            'name' => ['nome', 'name'],
            'email' => ['email', 'e-mail'],
            'phone' => ['telefone', 'phone', 'celular', 'whatsapp'],
            'nickname' => ['apelido', 'nickname'],
            'household_name' => ['núcleo', 'nucleo', 'familia', 'grupo', 'household'],
            'role_in_household' => ['papel', 'role', 'parentesco'],
            'is_child' => ['crianca', 'criança', 'child'],
            'side' => ['lado', 'side'],
            'category' => ['categoria', 'category'],
            'status' => ['status', 'rsvp'],
            'tags' => ['tags', 'etiquetas'],
            'notes' => ['observacoes', 'observações', 'notes'],
        ];

        foreach ($aliases as $target => $values) {
            if (!empty($mapping[$target])) {
                continue;
            }

            foreach ($headers as $header) {
                $normalized = Str::lower($header);
                foreach ($values as $value) {
                    if (Str::contains($normalized, Str::lower($value))) {
                        $mapping[$target] = $header;
                        break 2;
                    }
                }
            }
        }

        return $mapping;
    }

    protected function mapRow(array $headers, array $row, array $mapping): array
    {
        $rowAssoc = [];
        foreach ($headers as $index => $header) {
            $rowAssoc[$header] = $row[$index] ?? null;
        }

        $payload = [];
        foreach ($mapping as $target => $source) {
            if ($source && isset($rowAssoc[$source])) {
                $payload[$target] = $rowAssoc[$source];
            }
        }

        return $payload;
    }

    protected function normalizeEmail(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return Str::lower(trim($value));
    }

    protected function normalizePhone(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);
        return $digits ?: null;
    }

    protected function normalizeTags($value): ?array
    {
        if (empty($value)) {
            return null;
        }

        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    protected function normalizeBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = Str::lower(trim((string) $value));
        return in_array($value, ['1', 'true', 'sim', 'yes', 'y'], true);
    }

    protected function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
