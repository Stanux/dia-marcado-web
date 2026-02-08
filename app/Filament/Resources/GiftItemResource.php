<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftItemResource\Pages;
use App\Forms\Components\MediaGalleryPicker;
use App\Models\GiftItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament Resource for managing Gift Items.
 * 
 * Allows couples to manage their gift registry catalog.
 * 
 * @Requirements: 1.4, 1.5, 1.6, 1.7, 11.3
 */
class GiftItemResource extends Resource
{
    protected static ?string $model = GiftItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?string $navigationLabel = 'Itens de Presente';

    protected static ?string $modelLabel = 'Item de Presente';

    protected static ?string $pluralModelLabel = 'Itens de Presente';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Presente')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nome do presente'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Descrição detalhada do presente'),

                        MediaGalleryPicker::make('photo_url')
                            ->label('Foto do Presente')
                            ->imageMaxWidth(800)
                            ->imageMaxHeight(800)
                            ->buttonLabel('Selecionar da Galeria (máx. 800×800px)')
                            ->helperText('Escolha uma imagem da galeria. Imagens maiores serão ajustadas automaticamente.'),

                        Forms\Components\TextInput::make('price')
                            ->label('Preço (R$)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->step(0.01)
                            ->prefix('R$')
                            ->helperText('Preço mínimo: R$ 1,00')
                            ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : null)
                            ->formatStateUsing(fn ($state) => $state ? $state / 100 : null),

                        Forms\Components\TextInput::make('quantity_available')
                            ->label('Quantidade Disponível')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(1)
                            ->helperText('Quantidade disponível para compra'),

                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Habilitado')
                            ->default(true)
                            ->helperText('Desabilite para ocultar o item da lista pública'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Valores Originais')
                    ->description('Estes valores são preservados para permitir restauração')
                    ->schema([
                        Forms\Components\TextInput::make('original_name')
                            ->label('Nome Original')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('original_description')
                            ->label('Descrição Original')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(2),

                        Forms\Components\TextInput::make('original_price')
                            ->label('Preço Original (R$)')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state) => $state ? 'R$ ' . number_format($state / 100, 2, ',', '.') : '-'),

                        Forms\Components\TextInput::make('original_quantity')
                            ->label('Quantidade Original')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_url')
                    ->label('Foto')
                    ->width(60)
                    ->height(60)
                    ->defaultImageUrl(url('/images/gift-placeholder.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('price')
                    ->label('Preço')
                    ->money('BRL', divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_available')
                    ->label('Disponível')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 2 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('quantity_sold')
                    ->label('Vendidos')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Habilitado')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Habilitado')
                    ->placeholder('Todos')
                    ->trueLabel('Apenas habilitados')
                    ->falseLabel('Apenas desabilitados'),

                Tables\Filters\Filter::make('sold_out')
                    ->label('Esgotados')
                    ->query(fn (Builder $query): Builder => $query->where('quantity_available', 0)),

                Tables\Filters\Filter::make('available')
                    ->label('Disponíveis')
                    ->query(fn (Builder $query): Builder => $query->where('quantity_available', '>', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('restore')
                    ->label('Restaurar Original')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Restaurar valores originais')
                    ->modalDescription('Tem certeza que deseja restaurar este item aos valores originais? As alterações atuais serão perdidas.')
                    ->action(function (GiftItem $record) {
                        $record->restoreOriginal();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Item restaurado')
                            ->success()
                            ->body('O item foi restaurado aos valores originais.')
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enable')
                        ->label('Habilitar selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('disable')
                        ->label('Desabilitar selecionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => false]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if ($user && !$user->isAdmin()) {
            $wedding = $user->currentWedding;
            if ($wedding) {
                $query->where('wedding_id', $wedding->id);
            } else {
                $query->whereRaw('1 = 0'); // No results if no wedding context
            }
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGiftItems::route('/'),
            'create' => Pages\CreateGiftItem::route('/create'),
            'edit' => Pages\EditGiftItem::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Admin can always access
        if ($user->isAdmin()) {
            return true;
        }

        // Others need wedding context and couple/organizer role
        $wedding = $user->currentWedding;
        if (!$wedding) {
            return false;
        }

        $role = $user->roleIn($wedding);
        return in_array($role, ['couple', 'organizer']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
