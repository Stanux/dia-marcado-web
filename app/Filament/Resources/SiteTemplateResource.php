<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteTemplateResource\Pages;
use App\Models\SiteLayout;
use App\Models\SiteTemplate;
use App\Models\Wedding;
use App\Services\Site\SiteContentSchema;
use App\Services\Site\TemplatePlanAccessService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament Resource for managing Site Templates.
 */
class SiteTemplateResource extends Resource
{
    protected static ?string $model = SiteTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Templates';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?string $modelLabel = 'Template';

    protected static ?string $pluralModelLabel = 'Templates';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Template')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(140)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->maxLength(500),

                        Forms\Components\TextInput::make('thumbnail')
                            ->label('Thumbnail (URL)')
                            ->url()
                            ->maxLength(500),

                        Forms\Components\Select::make('template_category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(120),
                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->maxLength(120)
                                    ->unique('template_categories', 'slug'),
                                Forms\Components\Textarea::make('description')
                                    ->label('Descrição')
                                    ->rows(3),
                            ]),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Público')
                            ->helperText('Templates públicos ficam disponíveis para todos os usuários')
                            ->default(true)
                            ->disabled(fn () => !auth()->user()?->isAdmin())
                            ->visible(fn () => auth()->user()?->isAdmin()),
                    ]),

                Forms\Components\Section::make('Conteúdo JSON')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('Conteúdo do template')
                            ->rows(16)
                            ->required()
                            ->default(fn () => json_encode(SiteContentSchema::getDefaultContent(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state)
                            ->dehydrateStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return $state;
                                }

                                if (!is_string($state) || trim($state) === '') {
                                    return [];
                                }

                                $decoded = json_decode($state, true);

                                return is_array($decoded) ? $decoded : [];
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->placeholder('Sem categoria')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Público')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('wedding.title')
                    ->label('Proprietário')
                    ->placeholder('Sistema')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Visibilidade')
                    ->placeholder('Todos')
                    ->trueLabel('Públicos')
                    ->falseLabel('Privados'),

                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('Tipo')
                    ->placeholder('Todos')
                    ->trueLabel('Sistema')
                    ->falseLabel('Personalizados')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('wedding_id'),
                        false: fn (Builder $query) => $query->whereNotNull('wedding_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('apply')
                    ->label('Aplicar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Aplicar Template')
                    ->modalDescription(fn (SiteTemplate $record) => "Escolha como aplicar o template \"{$record->name}\".")
                    ->form([
                        Forms\Components\Select::make('mode')
                            ->label('Modo de aplicação')
                            ->options([
                                'merge' => 'Merge (preserva mídias atuais)',
                                'overwrite' => 'Alteração total (sobrescreve tudo)',
                            ])
                            ->default('merge')
                            ->required(),
                    ])
                    ->modalSubmitActionLabel('Aplicar Template')
                    ->action(function (SiteTemplate $record, array $data) {
                        $user = auth()->user();
                        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');
                        
                        if (!$weddingId) {
                            Notification::make()
                                ->title('Erro')
                                ->body('Nenhum casamento selecionado.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $site = SiteLayout::where('wedding_id', $weddingId)->first();
                        
                        if (!$site) {
                            Notification::make()
                                ->title('Erro')
                                ->body('Você precisa criar um site primeiro.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $wedding = Wedding::find($weddingId);
                        if (!$wedding) {
                            Notification::make()
                                ->title('Erro')
                                ->body('Casamento não encontrado.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $templateAccess = app(TemplatePlanAccessService::class);
                        if (!$templateAccess->canApplyTemplate($wedding, $record, $user?->isAdmin() === true)) {
                            Notification::make()
                                ->title('Template bloqueado')
                                ->body('Este template não está disponível no plano atual.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $siteBuilder = app(\App\Contracts\Site\SiteBuilderServiceInterface::class);
                        $siteBuilder->applyTemplate($site, $record, (string) ($data['mode'] ?? 'merge'), $user);

                        Notification::make()
                            ->title('Template aplicado!')
                            ->body("O template \"{$record->name}\" foi aplicado ao seu site.")
                            ->success()
                            ->send();
                    })
                    ->visible(function () {
                        $user = auth()->user();
                        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');
                        return $weddingId && SiteLayout::where('wedding_id', $weddingId)->exists();
                    }),

                Tables\Actions\Action::make('visual_editor')
                    ->label('Editor Visual')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (SiteTemplate $record): string => static::templateEditorUrl($record))
                    ->visible(fn () => auth()->user()?->isAdmin() ?? false),

                Tables\Actions\Action::make('view_site')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (SiteTemplate $record): ?string => $record->slug
                        ? route('public.site.template.preview', ['slug' => $record->slug])
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn (SiteTemplate $record): bool => (bool) $record->slug && $record->is_public && $record->wedding_id === null),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->isAdmin() ?? false),
            ])
            ->bulkActions([])
            ->defaultSort('name', 'asc')
            ->emptyStateHeading('Nenhum template disponível')
            ->emptyStateDescription('Os templates do sistema serão exibidos aqui.')
            ->emptyStateIcon('heroicon-o-squares-2x2');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteTemplates::route('/'),
            'create' => Pages\CreateSiteTemplate::route('/create'),
            'edit' => Pages\EditSiteTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

        if ($user?->isAdmin()) {
            return parent::getEloquentQuery()->with('category');
        }

        // Show public templates and templates owned by current wedding
        return parent::getEloquentQuery()
            ->with('category')
            ->where(function (Builder $query) use ($weddingId) {
                $query->where('is_public', true)
                    ->orWhereNull('wedding_id'); // System templates
                
                if ($weddingId) {
                    $query->orWhere('wedding_id', $weddingId);
                }
            });
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // Only allow editing own templates (not system templates)
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');
        
        return $record->wedding_id === $weddingId || $user?->isAdmin();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // Only allow deleting own templates (not system templates)
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');
        
        return $record->wedding_id === $weddingId || $user?->isAdmin();
    }

    public static function templateEditorUrl(SiteTemplate $template): string
    {
        return route('site-templates.editor', ['template' => $template->id]);
    }
}
