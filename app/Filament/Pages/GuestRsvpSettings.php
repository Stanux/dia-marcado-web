<?php

namespace App\Filament\Pages;

use App\Models\Wedding;
use App\Services\PermissionService;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GuestRsvpSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static string $view = 'filament.pages.guest-rsvp-settings';

    protected static ?string $navigationLabel = 'Configurações RSVP';

    protected static ?string $title = 'Configurações RSVP';

    protected static ?string $slug = 'guest-rsvp-settings';

    protected static ?string $navigationGroup = 'CONVIDADOS';

    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public function mount(): void
    {
        $wedding = $this->getWedding();

        if (!$wedding) {
            redirect()->route('filament.admin.pages.dashboard');
            return;
        }

        $this->form->fill([
            'rsvp_access' => $wedding->settings['rsvp_access'] ?? 'open',
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
                $this->getAccessSection(),
            ])
            ->statePath('data');
    }

    protected function getAccessSection(): Section
    {
        return Section::make('Acesso ao RSVP')
            ->description('Defina se o RSVP do site é aberto para todos ou restrito a convidados cadastrados.')
            ->schema([
                Radio::make('rsvp_access')
                    ->label('Modo de acesso')
                    ->options([
                        'open' => 'Aberto (qualquer pessoa pode responder)',
                        'restricted' => 'Restrito (somente convidados cadastrados ou com convite)',
                    ])
                    ->descriptions([
                        'open' => 'Qualquer pessoa pode preencher o RSVP, mesmo sem estar na lista.',
                        'restricted' => 'Exige token do convite ou correspondência por e-mail/telefone.',
                    ])
                    ->default('open')
                    ->required()
                    ->inline(false),
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
            $settings['rsvp_access'] = $data['rsvp_access'] ?? 'open';
            $wedding->settings = $settings;
            $wedding->save();

            Notification::make()
                ->title('Configurações atualizadas')
                ->body('O modo de acesso ao RSVP foi atualizado com sucesso.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao salvar')
                ->body('Ocorreu um erro ao atualizar as configurações. Tente novamente.')
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

        $wedding = Wedding::find($weddingId);
        if (!$wedding) {
            return false;
        }

        return app(PermissionService::class)->canAccess($user, 'guests', $wedding);
    }
}
