<?php

namespace App\Filament\Pages;

use App\Filament\Resources\UserResource;
use App\Contracts\WeddingSettingsServiceInterface;
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
 * - Partner linkage guidance via Users management
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
            'wedding_time' => $this->normalizeWeddingTimeForForm($wedding->settings['wedding_time'] ?? null),
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
        $settings = is_array($wedding->settings ?? null) ? $wedding->settings : [];
        $data = [
            'partner_status' => $status,
            'partner_name_draft' => $settings['partner_name_draft'] ?? null,
        ];

        if ($status === self::PARTNER_STATUS_PARTNER_LINKED) {
            $partner = $this->getLinkedPartner($wedding);
            if ($partner) {
                $data['partner_name'] = $partner->name;
                $data['partner_email'] = $partner->email;
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
        if (!$currentUser) {
            return self::PARTNER_STATUS_NO_PARTNER;
        }

        $partner = $wedding->couple()
            ->where('user_id', '!=', $currentUser->id)
            ->first();

        if ($partner) {
            return self::PARTNER_STATUS_PARTNER_LINKED;
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
            ->description('Defina a data e o horário do evento. Ambos são opcionais neste momento.')
            ->schema([
                Grid::make([
                    'default' => 1,
                    'md' => 2,
                ])
                    ->schema([
                        DatePicker::make('wedding_date')
                            ->label('Data do Casamento')
                            ->placeholder('Selecione a data')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(now()),

                        TextInput::make('wedding_time')
                            ->label('Horário do Evento')
                            ->placeholder('18:00')
                            ->maxLength(5)
                            ->rule('date_format:H:i')
                            ->helperText('Formato 24h (HH:MM). Ex: 18:00'),
                    ]),
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
            default => 'Crie a conta do(a) parceiro(a) em Usuários para liberar acesso colaborativo',
        };
    }

    protected function getPartnerSectionSchema(string $status): array
    {
        return match ($status) {
            self::PARTNER_STATUS_PARTNER_LINKED => $this->getLinkedPartnerSchema(),
            default => $this->getUnlinkedPartnerSchema(),
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

    protected function getUnlinkedPartnerSchema(): array
    {
        return [
            TextInput::make('partner_name_draft')
                ->label('Nome do(a) parceiro(a) (opcional)')
                ->placeholder('Nome completo')
                ->maxLength(255)
                ->helperText('Este nome é usado como rascunho do site até criar a conta em Usuários.'),

            Placeholder::make('partner_link_process')
                ->label('')
                ->content(new HtmlString('
                    <div class="text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                        <strong>Próximo passo:</strong> Crie a conta do(a) parceiro(a) na tela de Usuários
                        com o tipo <strong>Noivo(a)</strong> para acesso ao planejamento.
                    </div>
                ')),

            Actions::make([
                Action::make('createPartnerUser')
                    ->label('Ir para Usuários')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->url(UserResource::getUrl('create')),
            ]),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        // Ensure date/time keys are always present so clearing the field persists as null.
        $data['wedding_date'] = $data['wedding_date'] ?? null;
        $data['wedding_time'] = $data['wedding_time'] ?? null;

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

    private function normalizeWeddingTimeForForm(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if (preg_match('/^(?<hour>\d{2}):(?<minute>\d{2})(?::\d{2})?$/', $value, $matches) !== 1) {
            return null;
        }

        $hour = (int) $matches['hour'];
        $minute = (int) $matches['minute'];

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
