<?php

namespace App\Filament\Pages;

use App\Forms\Components\WeddingDatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

/**
 * Onboarding page for new users to configure their wedding.
 * 
 * This is a 4-step wizard that collects:
 * 1. Wedding date and time
 * 2. Couple data (partner info)
 * 3. Venue information
 * 4. Plan selection
 * 
 * Data is persisted in session to survive page refreshes.
 */
class Onboarding extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $view = 'filament.pages.onboarding-content';
    
    protected static string $layout = 'filament.pages.onboarding-layout';

    protected static ?string $title = 'Configuração Inicial';

    protected static ?string $slug = 'onboarding';

    protected static bool $shouldRegisterNavigation = false;

    private const SESSION_KEY = 'onboarding_data';

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();

        // If user has completed onboarding, redirect to dashboard
        if ($user && $user->hasCompletedOnboarding()) {
            redirect()->route('filament.admin.pages.dashboard');
            return;
        }

        // Restore data from session or use defaults
        $sessionData = session(self::SESSION_KEY, []);
        
        $this->form->fill(array_merge([
            'creator_name' => $user?->name,
            'plan' => 'basic',
        ], $sessionData));
    }

    /**
     * Called when form data is updated - persist to session.
     */
    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->persistToSession();
        }
    }

    /**
     * Persist current form data to session.
     */
    protected function persistToSession(): void
    {
        $data = $this->data ?? [];

        session([self::SESSION_KEY => $data]);
    }

    /**
     * Clear onboarding data from session.
     */
    public static function clearSession(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Save data to session.
     */
    public static function saveToSession(array $data): void
    {
        session([self::SESSION_KEY => $data]);
    }

    /**
     * Get data from session.
     */
    public static function getFromSession(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Dia Marcado')
                        ->label(fn (): string => $this->getWeddingStepLabel())
                        ->icon('heroicon-o-calendar')
                        ->schema($this->getWeddingDateSchema()),

                    Step::make('Dados do Casal')
                        ->icon('heroicon-o-heart')
                        ->schema($this->getCoupleDataSchema()),

                    Step::make('Local do Evento')
                        ->icon('heroicon-o-map-pin')
                        ->schema($this->getVenueDataSchema()),

                    Step::make('Escolha do Plano')
                        ->icon('heroicon-o-star')
                        ->schema($this->getPlanSchema()),
                ])
                ->skippable(false)
                ->persistStepInQueryString()
                ->submitAction(new HtmlString('
                    <button type="button" 
                        wire:click="complete" 
                        wire:loading.attr="disabled"
                        class="fi-btn fi-btn-size-md fi-btn-color-primary relative grid-flow-col items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold outline-none transition duration-75 focus-visible:ring-2 bg-primary-600 text-white hover:bg-primary-500 focus-visible:ring-primary-500/50 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus-visible:ring-primary-400/50">
                        <span wire:loading.remove wire:target="complete">Concluir</span>
                        <span wire:loading wire:target="complete">Processando...</span>
                    </button>
                ')),
            ])
            ->statePath('data');
    }

    protected function getWeddingDateSchema(): array
    {
        return [
            Grid::make([
                'default' => 1,
                'lg' => 12,
            ])
                ->schema([
                    WeddingDatePicker::make('wedding_date')
                        ->label('Data do casamento')
                        ->hiddenLabel()
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 12,
                        ]),
                ]),
        ];
    }

    protected function getWeddingStepLabel(): string
    {
        $date = data_get($this->data, 'wedding_date');

        if (! is_string($date) || blank($date)) {
            return 'Dia Marcado';
        }

        try {
            $formattedDate = Carbon::createFromFormat('Y-m-d', $date)->format('d/m/Y');
        } catch (\Throwable) {
            return 'Dia Marcado';
        }

        return $formattedDate;
    }

    protected function getCoupleDataSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'lg' => 2,
                    ])
                        ->schema([
                            TextInput::make('creator_name')
                                ->label('Seu nome')
                                ->placeholder('Nome completo')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('partner_name')
                                ->label('Nome do(a) parceiro(a)')
                                ->placeholder('Nome completo (opcional)')
                                ->maxLength(255),
                        ]),

                    Placeholder::make('partner_account_disclaimer')
                        ->label('')
                        ->content(new HtmlString('
                            <div class="text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                                <strong>Nota:</strong> Após concluir o onboarding, você poderá adicionar a conta do(a) parceiro(a)
                                na tela <strong>Usuários</strong> dentro da plataforma.
                            </div>
                        ')),
                ]),
        ];
    }

    protected function getVenueDataSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('venue_name')
                                ->label('Nome de Identificação')
                                ->placeholder('Ex: Espaço Jardim das Flores')
                                ->maxLength(255),

                            TextInput::make('venue_address')
                                ->label('Endereço')
                                ->placeholder('Rua, número')
                                ->maxLength(255),

                            TextInput::make('venue_neighborhood')
                                ->label('Bairro')
                                ->placeholder('Bairro')
                                ->maxLength(255),
                        ]),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('venue_city')
                                ->label('Cidade')
                                ->placeholder('Cidade')
                                ->maxLength(255),

                            Select::make('venue_state')
                                ->label('Estado')
                                ->placeholder('Selecione')
                                ->options([
                                    'AC' => 'Acre',
                                    'AL' => 'Alagoas',
                                    'AP' => 'Amapá',
                                    'AM' => 'Amazonas',
                                    'BA' => 'Bahia',
                                    'CE' => 'Ceará',
                                    'DF' => 'Distrito Federal',
                                    'ES' => 'Espírito Santo',
                                    'GO' => 'Goiás',
                                    'MA' => 'Maranhão',
                                    'MT' => 'Mato Grosso',
                                    'MS' => 'Mato Grosso do Sul',
                                    'MG' => 'Minas Gerais',
                                    'PA' => 'Pará',
                                    'PB' => 'Paraíba',
                                    'PR' => 'Paraná',
                                    'PE' => 'Pernambuco',
                                    'PI' => 'Piauí',
                                    'RJ' => 'Rio de Janeiro',
                                    'RN' => 'Rio Grande do Norte',
                                    'RS' => 'Rio Grande do Sul',
                                    'RO' => 'Rondônia',
                                    'RR' => 'Roraima',
                                    'SC' => 'Santa Catarina',
                                    'SP' => 'São Paulo',
                                    'SE' => 'Sergipe',
                                    'TO' => 'Tocantins',
                                ])
                                ->searchable(),

                            TextInput::make('venue_phone')
                                ->label('Telefone de Contato')
                                ->placeholder('(00) 00000-0000')
                                ->tel()
                                ->maxLength(20),
                        ]),
                ]),
        ];
    }

    protected function getPlanSchema(): array
    {
        return [
            Section::make()
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
                ]),
        ];
    }

    public function complete(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        // Prevent duplicate submission - check if user already completed onboarding
        $user->refresh();
        if ($user->hasCompletedOnboarding()) {
            redirect()->route('filament.admin.pages.dashboard');
            return;
        }

        try {
            // Use OnboardingService to complete the process
            $onboardingService = app(\App\Contracts\OnboardingServiceInterface::class);
            $wedding = $onboardingService->complete($user, $data);

            // Update Filament session with the new wedding ID
            session(['filament_wedding_id' => $wedding->id]);

            // Clear onboarding session data after successful completion
            self::clearSession();

            Notification::make()
                ->title('Configuração concluída!')
                ->body('Seu casamento foi criado com sucesso.')
                ->success()
                ->send();

            redirect()->route('filament.admin.pages.dashboard');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao concluir configuração')
                ->body('Ocorreu um erro. Por favor, tente novamente.')
                ->danger()
                ->send();

            report($e);
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Admin users don't need onboarding - but allow access if they navigate directly
        // Regular users can access if they haven't completed onboarding
        // Users who completed onboarding will be redirected by mount() method
        return true;
    }
}
