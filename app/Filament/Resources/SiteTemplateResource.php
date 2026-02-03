<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteTemplateResource\Pages;
use App\Models\SiteLayout;
use App\Models\SiteTemplate;
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

    protected static ?int $navigationSort = 5;

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

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->maxLength(500),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Público')
                            ->helperText('Templates públicos ficam disponíveis para todos os usuários')
                            ->disabled(fn () => !auth()->user()?->isAdmin()),
                    ]),
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
                    ->modalDescription(fn (SiteTemplate $record) => "Deseja aplicar o template \"{$record->name}\" ao seu site? O conteúdo atual será mesclado com o template.")
                    ->modalSubmitActionLabel('Aplicar Template')
                    ->action(function (SiteTemplate $record) {
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

                        $siteBuilder = app(\App\Contracts\Site\SiteBuilderServiceInterface::class);
                        $siteBuilder->applyTemplate($site, $record);

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

                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
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
            'view' => Pages\ViewSiteTemplate::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

        // Show public templates and templates owned by current wedding
        return parent::getEloquentQuery()
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
        return false; // Templates are created via "Save as Template" in editor
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
}
