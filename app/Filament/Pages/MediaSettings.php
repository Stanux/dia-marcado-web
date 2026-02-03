<?php

namespace App\Filament\Pages;

use App\Models\SystemConfig;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Filament Page for managing Media Settings.
 * 
 * Only accessible by Admin users.
 * Manages media upload configuration via SystemConfig.
 * 
 * @Requirements: 3.1, 3.2, 3.3
 */
class MediaSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Mídia';

    protected static ?string $navigationLabel = 'Configurações de Mídia';

    protected static ?string $title = 'Configurações de Mídia';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.media-settings';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'max_image_width' => SystemConfig::get('media.max_image_width', 4096),
            'max_image_height' => SystemConfig::get('media.max_image_height', 4096),
            'max_image_size_mb' => round(SystemConfig::get('media.max_image_size', 10485760) / 1024 / 1024),
            'max_video_size_mb' => round(SystemConfig::get('media.max_video_size', 104857600) / 1024 / 1024),
            'allowed_extensions' => SystemConfig::get('site.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']),
            'blocked_extensions' => SystemConfig::get('site.blocked_extensions', ['exe', 'bat', 'sh', 'php', 'js', 'html', 'svg']),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dimensões de Imagem')
                    ->description('Imagens maiores serão redimensionadas automaticamente')
                    ->schema([
                        Forms\Components\TextInput::make('max_image_width')
                            ->label('Largura Máxima (pixels)')
                            ->required()
                            ->numeric()
                            ->minValue(100)
                            ->maxValue(10000)
                            ->default(4096)
                            ->suffix('px'),

                        Forms\Components\TextInput::make('max_image_height')
                            ->label('Altura Máxima (pixels)')
                            ->required()
                            ->numeric()
                            ->minValue(100)
                            ->maxValue(10000)
                            ->default(4096)
                            ->suffix('px'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Limites de Tamanho')
                    ->description('Arquivos maiores serão rejeitados')
                    ->schema([
                        Forms\Components\TextInput::make('max_image_size_mb')
                            ->label('Tamanho Máximo de Imagem')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(10)
                            ->suffix('MB'),

                        Forms\Components\TextInput::make('max_video_size_mb')
                            ->label('Tamanho Máximo de Vídeo')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->default(100)
                            ->suffix('MB'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Extensões de Arquivo')
                    ->schema([
                        Forms\Components\TagsInput::make('allowed_extensions')
                            ->label('Extensões Permitidas')
                            ->required()
                            ->placeholder('Adicionar extensão')
                            ->helperText('Extensões de arquivo que podem ser enviadas'),

                        Forms\Components\TagsInput::make('blocked_extensions')
                            ->label('Extensões Bloqueadas')
                            ->placeholder('Adicionar extensão')
                            ->helperText('Extensões que serão sempre rejeitadas (tem prioridade sobre permitidas)'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Save dimension settings
        SystemConfig::set('media.max_image_width', (int) $data['max_image_width']);
        SystemConfig::set('media.max_image_height', (int) $data['max_image_height']);

        // Save size settings (convert MB to bytes)
        SystemConfig::set('media.max_image_size', (int) $data['max_image_size_mb'] * 1024 * 1024);
        SystemConfig::set('media.max_video_size', (int) $data['max_video_size_mb'] * 1024 * 1024);

        // Save extension settings
        SystemConfig::set('site.allowed_extensions', $data['allowed_extensions']);
        SystemConfig::set('site.blocked_extensions', $data['blocked_extensions'] ?? []);

        Notification::make()
            ->title('Configurações salvas')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
