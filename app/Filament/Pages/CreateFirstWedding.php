<?php

namespace App\Filament\Pages;

use App\Services\WeddingService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Page for new users (Noivos) to create their first wedding.
 * This page is shown when a user has no weddings associated.
 */
class CreateFirstWedding extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static string $view = 'filament.pages.create-first-wedding';

    protected static ?string $title = 'Criar seu Casamento';

    protected static ?string $slug = 'criar-casamento';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();

        // If user already has weddings, redirect to dashboard
        if ($user && $user->weddings()->exists()) {
            redirect()->route('filament.admin.pages.dashboard');
            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label('Nome do Casamento')
                    ->placeholder('Ex: Casamento João e Maria')
                    ->required()
                    ->maxLength(255),

                DatePicker::make('date')
                    ->label('Data do Casamento')
                    ->placeholder('Selecione a data')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        $weddingService = app(WeddingService::class);
        $wedding = $weddingService->createWedding($user, $data);

        // Set the wedding as current
        $user->current_wedding_id = $wedding->id;
        $user->save();
        session(['filament_wedding_id' => $wedding->id]);

        Notification::make()
            ->title('Casamento criado com sucesso!')
            ->success()
            ->send();

        redirect()->route('filament.admin.pages.dashboard');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // Only show this page if user has no weddings
        return !$user->weddings()->exists();
    }
}
