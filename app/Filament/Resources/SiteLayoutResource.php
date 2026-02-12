<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteLayoutResource\Pages;
use App\Models\SiteLayout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament Resource for managing Site Layouts.
 * 
 * Extends WeddingScopedResource to automatically:
 * - Filter sites by the current wedding context
 * - Verify user has 'sites' module permission
 * - Inject wedding_id when creating new sites
 * 
 * @Requirements: 1.2
 */
class SiteLayoutResource extends WeddingScopedResource
{
    protected static ?string $model = SiteLayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?string $navigationLabel = 'Editor do Site';

    protected static ?string $module = 'sites';

    protected static ?string $modelLabel = 'Site';

    protected static ?string $pluralModelLabel = 'Sites';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configurações do Site')
                    ->schema([
                        Forms\Components\TextInput::make('slug')
                            ->label('URL do Site')
                            ->prefix(config('app.url') . '/site/')
                            ->required()
                            ->maxLength(100)
                            ->regex('/^[a-z0-9-]+$/')
                            ->unique(ignoreRecord: true)
                            ->helperText('Apenas letras minúsculas, números e hífens'),

                        Forms\Components\TextInput::make('custom_domain')
                            ->label('Domínio Personalizado')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://meusite.com.br')
                            ->helperText('Opcional: domínio próprio para o site'),

                        Forms\Components\TextInput::make('access_token')
                            ->label('Senha de Acesso')
                            ->password()
                            ->revealable()
                            ->maxLength(50)
                            ->helperText('Deixe em branco para site público'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('Publicado')
                            ->disabled()
                            ->helperText('Use o editor visual para publicar o site'),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publicado em')
                            ->disabled()
                            ->displayFormat('d/m/Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wedding.title')
                    ->label('Casamento')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('URL')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('URL copiada!')
                    ->formatStateUsing(fn (string $state): string => "/site/{$state}"),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publicado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Não publicado'),

                Tables\Columns\IconColumn::make('has_password')
                    ->label('Protegido')
                    ->getStateUsing(fn (SiteLayout $record): bool => $record->access_token !== null)
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Status')
                    ->placeholder('Todos')
                    ->trueLabel('Publicados')
                    ->falseLabel('Rascunhos'),

                Tables\Filters\TernaryFilter::make('has_password')
                    ->label('Proteção')
                    ->placeholder('Todos')
                    ->trueLabel('Com senha')
                    ->falseLabel('Públicos')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('access_token'),
                        false: fn (Builder $query) => $query->whereNull('access_token'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('view_site')
                    ->label('Ver Site')
                    ->icon('heroicon-o-eye')
                    ->url(fn (SiteLayout $record): string => route('public.site.show', ['slug' => $record->slug]))
                    ->openUrlInNewTab()
                    ->visible(fn (SiteLayout $record): bool => $record->is_published),

                Tables\Actions\Action::make('edit_visual')
                    ->label('Editor Visual')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (SiteLayout $record): string => route('filament.admin.pages.site-editor', ['site' => $record->id]))
                    ->color('primary'),

                Tables\Actions\EditAction::make()
                    ->label('Configurações'),
            ])
            ->bulkActions([
                // No bulk actions for sites
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Nenhum site criado')
            ->emptyStateDescription('Crie um site para o casamento usando o editor visual.')
            ->emptyStateIcon('heroicon-o-globe-alt');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteLayouts::route('/'),
            'edit' => Pages\EditSiteLayout::route('/{record}/edit'),
        ];
    }

    public static function getSlug(): string
    {
        return 'site-layouts';
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

        if (!$weddingId) {
            return null;
        }

        $count = static::getModel()::where('wedding_id', $weddingId)
            ->where('is_published', false)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
