<?php

namespace App\Filament\Pages;

use App\Models\Wedding;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

/**
 * My Plan page for managing wedding plan selection.
 */
class MyPlan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static string $view = 'filament.pages.my-plan';

    protected static ?string $navigationLabel = 'Meu Plano';

    protected static ?string $title = 'Meu Plano';

    protected static ?string $slug = 'my-plan';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?int $navigationSort = 8;

    public ?array $data = [];

    public function mount(): void
    {
        $wedding = $this->getWedding();
        
        if (!$wedding) {
            redirect()->route('filament.admin.pages.dashboard');
            return;
        }

        $this->form->fill([
            'plan' => $wedding->settings['plan'] ?? 'basic',
        ]);
    }

    protected function getWedding(): ?Wedding
    {
        $weddingId = session('filament_wedding_id');
        
        if (!$weddingId) {
            return null;
        }

        return Wedding::find($weddingId);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getPlanSection(),
            ])
            ->statePath('data');
    }

    protected function getPlanSection(): Section
    {
        return Section::make('Plano')
            ->description('Selecione o plano que melhor atende suas necessidades')
            ->schema([
                Radio::make('plan')
                    ->label('')
                    ->options([
                        'basic' => 'Plano Básico',
                        'premium' => 'Plano Premium',
                    ])
                    ->descriptions([
                        'basic' => 'Acesso a todas as funcionalidades essenciais para planejar seu casamento.',
                        'premium' => 'Todas as funcionalidades do plano básico + recursos exclusivos (em breve).',
                    ])
                    ->default('basic')
                    ->required()
                    ->inline(false),

                Placeholder::make('plan_note')
                    ->label('')
                    ->content(new HtmlString('
                        <div class="text-sm text-gray-500 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg mt-4">
                            <strong>Nota:</strong> Atualmente ambos os planos oferecem as mesmas funcionalidades. 
                            Recursos exclusivos do plano Premium serão liberados em breve.
                        </div>
                    ')),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $wedding = $this->getWedding();

        if (!$wedding) {
            Notification::make()
                ->title('Erro')
                ->body('Casamento não encontrado.')
                ->danger()
                ->send();
            return;
        }

        try {
            $settings = $wedding->settings ?? [];
            $settings['plan'] = $data['plan'];
            $wedding->settings = $settings;
            $wedding->save();

            Notification::make()
                ->title('Plano atualizado!')
                ->body('Seu plano foi atualizado com sucesso.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao salvar')
                ->body('Ocorreu um erro ao atualizar o plano. Tente novamente.')
                ->danger()
                ->send();

            report($e);
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $weddingId = session('filament_wedding_id');

        if (!$user || !$weddingId) {
            return false;
        }

        return $user->weddings()
            ->where('wedding_id', $weddingId)
            ->wherePivot('role', 'couple')
            ->exists();
    }
}
