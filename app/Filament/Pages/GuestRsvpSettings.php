<?php

namespace App\Filament\Pages;

use App\Filament\Resources\GuestEventResource;
use App\Filament\Resources\GuestHouseholdResource;
use App\Filament\Resources\GuestResource;
use App\Models\Guest;
use App\Models\GuestEvent;
use App\Models\GuestHousehold;
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
    private ?array $overviewMetrics = null;

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

    public function getOverviewMetrics(): array
    {
        if ($this->overviewMetrics !== null) {
            return $this->overviewMetrics;
        }

        $wedding = $this->getWedding();

        if (!$wedding) {
            return $this->overviewMetrics = [
                'households' => 0,
                'guests' => 0,
                'events_total' => 0,
                'events_active' => 0,
                'events_with_questions' => 0,
                'questions_total' => 0,
            ];
        }

        $events = GuestEvent::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->get(['is_active', 'questions']);

        return $this->overviewMetrics = [
            'households' => GuestHousehold::withoutGlobalScopes()
                ->where('wedding_id', $wedding->id)
                ->count(),
            'guests' => Guest::withoutGlobalScopes()
                ->where('wedding_id', $wedding->id)
                ->count(),
            'events_total' => $events->count(),
            'events_active' => $events->where('is_active', true)->count(),
            'events_with_questions' => $events
                ->filter(fn (GuestEvent $event): bool => is_array($event->questions) && count($event->questions) > 0)
                ->count(),
            'questions_total' => $events
                ->sum(fn (GuestEvent $event): int => is_array($event->questions) ? count($event->questions) : 0),
        ];
    }

    public function getQuickLinks(): array
    {
        return [
            [
                'label' => 'Eventos RSVP',
                'description' => 'Crie eventos e configure perguntas personalizadas.',
                'url' => GuestEventResource::getUrl('index'),
            ],
            [
                'label' => 'Núcleos',
                'description' => 'Organize famílias e grupos de convidados.',
                'url' => GuestHouseholdResource::getUrl('index'),
            ],
            [
                'label' => 'Convidados',
                'description' => 'Cadastre convidados e acompanhe status.',
                'url' => GuestResource::getUrl('index'),
            ],
            [
                'label' => 'Dashboard RSVP',
                'description' => 'Monitore respostas, alertas e incidentes.',
                'url' => GuestOperationsDashboard::getUrl(),
            ],
        ];
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
