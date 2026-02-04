<?php

namespace App\Filament\Pages;

use App\Contracts\PartnerInviteServiceInterface;
use App\Contracts\WeddingSettingsServiceInterface;
use App\Models\PartnerInvite;
use App\Models\Wedding;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

/**
 * Wedding Settings page for editing wedding data after onboarding.
 * 
 * Allows couples to edit:
 * - Wedding date
 * - Venue information
 * - Plan selection
 * - Partner invitation management
 */
class WeddingSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.wedding-settings';

    protected static ?string $navigationLabel = 'Dados do Evento';

    protected static ?string $title = 'Dados do Evento';

    protected static ?string $slug = 'wedding-settings';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = true;

    public ?array $data = [];

    /**
     * Partner status enum values.
     */
    private const PARTNER_STATUS_NO_PARTNER = 'no_partner';
    private const PARTNER_STATUS_INVITE_PENDING = 'invite_pending';
    private const PARTNER_STATUS_INVITE_EXPIRED = 'invite_expired';
    private const PARTNER_STATUS_INVITE_DECLINED = 'invite_declined';
    private const PARTNER_STATUS_PARTNER_LINKED = 'partner_linked';

    public function mount(): void
    {
        $wedding = $this->getWedding();
        
        if (!$wedding) {
            redirect()->route('filament.admin.pages.dashboard');
            return;
        }

        $partnerData = $this->getPartnerFormData($wedding);

        $this->form->fill(array_merge([
            'wedding_date' => $wedding->wedding_date?->format('Y-m-d'),
            'venue_name' => $wedding->venue,
            'venue_city' => $wedding->city,
            'venue_state' => $wedding->state,
            'venue_address' => $wedding->settings['venue_address'] ?? null,
            'venue_neighborhood' => $wedding->settings['venue_neighborhood'] ?? null,
            'venue_phone' => $wedding->settings['venue_phone'] ?? null,
        ], $partnerData));
    }

    /**
     * Get the current wedding from session.
     */
    protected function getWedding(): ?Wedding
    {
        $weddingId = session('filament_wedding_id');
        
        if (!$weddingId) {
            return null;
        }

        return Wedding::find($weddingId);
    }

    /**
     * Get partner form data based on current status.
     */
    protected function getPartnerFormData(Wedding $wedding): array
    {
        $status = $this->getPartnerStatus($wedding);
        $data = ['partner_status' => $status];

        if ($status === self::PARTNER_STATUS_PARTNER_LINKED) {
            $partner = $this->getLinkedPartner($wedding);
            if ($partner) {
                $data['partner_name'] = $partner->name;
                $data['partner_email'] = $partner->email;
            }
        } elseif (in_array($status, [self::PARTNER_STATUS_INVITE_PENDING, self::PARTNER_STATUS_INVITE_EXPIRED, self::PARTNER_STATUS_INVITE_DECLINED])) {
            $invite = $this->getLatestInvite($wedding);
            if ($invite) {
                $data['partner_name'] = $invite->name;
                $data['partner_email'] = $invite->email;
            }
        }

        return $data;
    }

    /**
     * Determine the current partner status for the wedding.
     */
    public function getPartnerStatus(Wedding $wedding): string
    {
        // Check if wedding has a linked partner (another couple member)
        $currentUser = auth()->user();
        $partner = $wedding->couple()
            ->where('user_id', '!=', $currentUser->id)
            ->first();

        if ($partner) {
            return self::PARTNER_STATUS_PARTNER_LINKED;
        }

        // Check for existing invites
        $invite = $this->getLatestInvite($wedding);

        if (!$invite) {
            return self::PARTNER_STATUS_NO_PARTNER;
        }

        if ($invite->status === 'declined') {
            return self::PARTNER_STATUS_INVITE_DECLINED;
        }

        if ($invite->status === 'pending') {
            if ($invite->isExpired()) {
                return self::PARTNER_STATUS_INVITE_EXPIRED;
            }
            return self::PARTNER_STATUS_INVITE_PENDING;
        }

        return self::PARTNER_STATUS_NO_PARTNER;
    }

    /**
     * Get the linked partner user.
     */
    protected function getLinkedPartner(Wedding $wedding)
    {
        $currentUser = auth()->user();
        return $wedding->couple()
            ->where('user_id', '!=', $currentUser->id)
            ->first();
    }

    /**
     * Get the latest partner invite for the wedding.
     */
    protected function getLatestInvite(Wedding $wedding): ?PartnerInvite
    {
        return PartnerInvite::where('wedding_id', $wedding->id)
            ->whereIn('status', ['pending', 'declined'])
            ->latest()
            ->first();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getWeddingDateSection(),
                $this->getVenueSection(),
                $this->getPartnerSection(),
            ])
            ->statePath('data');
    }


    protected function getWeddingDateSection(): Section
    {
        return Section::make('Data do Casamento')
            ->description('Quando será o grande dia?')
            ->schema([
                DatePicker::make('wedding_date')
                    ->label('Data do Casamento')
                    ->placeholder('Selecione a data')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->minDate(now())
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    protected function getVenueSection(): Section
    {
        return Section::make('Local do Evento')
            ->description('Informações sobre o local do casamento (todos os campos são opcionais)')
            ->schema([
                TextInput::make('venue_name')
                    ->label('Nome do Local')
                    ->placeholder('Ex: Espaço Jardim das Flores')
                    ->maxLength(255),

                TextInput::make('venue_address')
                    ->label('Endereço')
                    ->placeholder('Rua, número')
                    ->maxLength(255),

                Grid::make(3)
                    ->schema([
                        TextInput::make('venue_neighborhood')
                            ->label('Bairro')
                            ->placeholder('Bairro')
                            ->maxLength(255),

                        TextInput::make('venue_city')
                            ->label('Cidade')
                            ->placeholder('Cidade')
                            ->maxLength(255),

                        TextInput::make('venue_state')
                            ->label('Estado')
                            ->placeholder('UF')
                            ->maxLength(2),
                    ]),

                TextInput::make('venue_phone')
                    ->label('Telefone de Contato')
                    ->placeholder('(00) 00000-0000')
                    ->tel()
                    ->maxLength(20),
            ]);
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

    protected function getPartnerSection(): Section
    {
        $wedding = $this->getWedding();
        $status = $wedding ? $this->getPartnerStatus($wedding) : self::PARTNER_STATUS_NO_PARTNER;

        return Section::make('Parceiro(a)')
            ->description($this->getPartnerSectionDescription($status))
            ->schema($this->getPartnerSectionSchema($status));
    }

    protected function getPartnerSectionDescription(string $status): string
    {
        return match ($status) {
            self::PARTNER_STATUS_PARTNER_LINKED => 'Seu(sua) parceiro(a) está vinculado(a) ao casamento',
            self::PARTNER_STATUS_INVITE_PENDING => 'Convite enviado - Aguardando aceite',
            self::PARTNER_STATUS_INVITE_EXPIRED => 'Convite expirado - Envie um novo convite',
            self::PARTNER_STATUS_INVITE_DECLINED => 'Convite recusado - Envie um novo convite',
            default => 'Convide seu(sua) parceiro(a) para participar do planejamento',
        };
    }

    protected function getPartnerSectionSchema(string $status): array
    {
        return match ($status) {
            self::PARTNER_STATUS_PARTNER_LINKED => $this->getLinkedPartnerSchema(),
            self::PARTNER_STATUS_INVITE_PENDING => $this->getPendingInviteSchema(),
            self::PARTNER_STATUS_INVITE_EXPIRED, self::PARTNER_STATUS_INVITE_DECLINED => $this->getExpiredOrDeclinedInviteSchema($status),
            default => $this->getNewInviteSchema(),
        };
    }

    protected function getLinkedPartnerSchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('partner_name')
                        ->label('Nome do(a) Parceiro(a)')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('partner_email')
                        ->label('E-mail do(a) Parceiro(a)')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Placeholder::make('partner_linked_info')
                ->label('')
                ->content(new HtmlString('
                    <div class="text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                        <strong>✓ Parceiro(a) vinculado(a)</strong> - Vocês podem planejar o casamento juntos!
                    </div>
                ')),
        ];
    }

    protected function getPendingInviteSchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('partner_name')
                        ->label('Nome do(a) Parceiro(a)')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('partner_email')
                        ->label('E-mail do(a) Parceiro(a)')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Placeholder::make('invite_pending_info')
                ->label('')
                ->content(new HtmlString('
                    <div class="text-sm text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg">
                        <strong>⏳ Aguardando aceite</strong> - O convite foi enviado e está aguardando resposta.
                    </div>
                ')),

            Actions::make([
                Action::make('resendInvite')
                    ->label('Reenviar Convite')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->action('resendInvite'),
            ]),
        ];
    }

    protected function getExpiredOrDeclinedInviteSchema(string $status): array
    {
        $message = $status === self::PARTNER_STATUS_INVITE_EXPIRED
            ? '<strong>⚠ Convite expirado</strong> - O convite anterior expirou. Preencha os dados abaixo para enviar um novo.'
            : '<strong>✗ Convite recusado</strong> - O convite anterior foi recusado. Preencha os dados abaixo para enviar um novo.';

        $bgClass = $status === self::PARTNER_STATUS_INVITE_EXPIRED
            ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400'
            : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400';

        return [
            Placeholder::make('invite_status_info')
                ->label('')
                ->content(new HtmlString("
                    <div class=\"text-sm {$bgClass} p-3 rounded-lg mb-4\">
                        {$message}
                    </div>
                ")),

            Grid::make(2)
                ->schema([
                    TextInput::make('partner_name')
                        ->label('Nome do(a) Parceiro(a)')
                        ->placeholder('Nome completo')
                        ->maxLength(255)
                        ->requiredWith('partner_email'),

                    TextInput::make('partner_email')
                        ->label('E-mail do(a) Parceiro(a)')
                        ->placeholder('email@exemplo.com')
                        ->email()
                        ->maxLength(255)
                        ->different('data.creator_email')
                        ->rules(['different:' . auth()->user()?->email])
                        ->validationMessages([
                            'different' => 'O e-mail do parceiro deve ser diferente do seu.',
                        ]),
                ]),
        ];
    }

    protected function getNewInviteSchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('partner_name')
                        ->label('Nome do(a) Parceiro(a)')
                        ->placeholder('Nome completo')
                        ->maxLength(255)
                        ->requiredWith('partner_email'),

                    TextInput::make('partner_email')
                        ->label('E-mail do(a) Parceiro(a)')
                        ->placeholder('email@exemplo.com')
                        ->email()
                        ->maxLength(255)
                        ->rules(['different:' . auth()->user()?->email])
                        ->validationMessages([
                            'different' => 'O e-mail do parceiro deve ser diferente do seu.',
                        ]),
                ]),

            Placeholder::make('partner_invite_info')
                ->label('')
                ->content(new HtmlString('
                    <div class="text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                        <strong>Nota:</strong> Um convite será enviado para o e-mail informado ao salvar. 
                        O(a) parceiro(a) precisará aceitar o convite para participar do planejamento.
                    </div>
                ')),
        ];
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
            // Update wedding settings
            $settingsService = app(WeddingSettingsServiceInterface::class);
            $settingsService->update($wedding, $data);

            // Handle partner invite if new partner data provided
            $this->handlePartnerInvite($wedding, $data);

            Notification::make()
                ->title('Configurações salvas!')
                ->body('As alterações foram salvas com sucesso.')
                ->success()
                ->send();

            // Refresh the form to show updated partner status
            $this->mount();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao salvar')
                ->body('Ocorreu um erro ao salvar as configurações. Tente novamente.')
                ->danger()
                ->send();

            report($e);
        }
    }

    protected function handlePartnerInvite(Wedding $wedding, array $data): void
    {
        $status = $this->getPartnerStatus($wedding);

        // Only send invite if:
        // 1. No partner linked
        // 2. No pending invite
        // 3. Partner email is provided
        if (in_array($status, [self::PARTNER_STATUS_NO_PARTNER, self::PARTNER_STATUS_INVITE_EXPIRED, self::PARTNER_STATUS_INVITE_DECLINED])) {
            if (!empty($data['partner_email']) && !empty($data['partner_name'])) {
                $inviteService = app(PartnerInviteServiceInterface::class);
                $inviteService->sendInvite(
                    $wedding,
                    auth()->user(),
                    $data['partner_email'],
                    $data['partner_name']
                );
            }
        }
    }

    public function resendInvite(): void
    {
        $wedding = $this->getWedding();

        if (!$wedding) {
            Notification::make()
                ->title('Erro')
                ->body('Casamento não encontrado.')
                ->danger()
                ->send();
            return;
        }

        $invite = $this->getLatestInvite($wedding);

        if (!$invite) {
            Notification::make()
                ->title('Erro')
                ->body('Nenhum convite encontrado para reenviar.')
                ->danger()
                ->send();
            return;
        }

        try {
            $inviteService = app(PartnerInviteServiceInterface::class);
            $inviteService->sendInvite(
                $wedding,
                auth()->user(),
                $invite->email,
                $invite->name
            );

            Notification::make()
                ->title('Convite reenviado!')
                ->body('Um novo convite foi enviado para ' . $invite->email)
                ->success()
                ->send();

            // Refresh the form
            $this->mount();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao reenviar')
                ->body('Ocorreu um erro ao reenviar o convite. Tente novamente.')
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

        // Check if user has "couple" role in the wedding
        return $user->weddings()
            ->where('wedding_id', $weddingId)
            ->wherePivot('role', 'couple')
            ->exists();
    }
}
