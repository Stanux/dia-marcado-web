<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeddingVendorResource\Pages;
use App\Models\VendorCategory;
use App\Models\Wedding;
use App\Models\WeddingVendor;
use App\Services\Planning\WeddingVendorService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class WeddingVendorResource extends WeddingScopedResource
{
    protected static ?string $model = WeddingVendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Fornecedores';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?string $module = 'tasks';

    protected static ?string $modelLabel = 'Fornecedor';

    protected static ?string $pluralModelLabel = 'Fornecedores';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('document')
                    ->label('CPF/CNPJ')
                    ->required()
                    ->maxLength(20),

                Forms\Components\TextInput::make('phone')
                    ->label('Telefone')
                    ->maxLength(30),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),

                Forms\Components\TextInput::make('website')
                    ->label('Site')
                    ->maxLength(255),

                Forms\Components\Select::make('categories')
                    ->label('Categorias')
                    ->relationship('categories', 'name')
                    ->options(VendorCategory::orderBy('sort')->pluck('name', 'id'))
                    ->multiple()
                    ->required(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('document')
                    ->label('CPF/CNPJ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Categorias')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeddingVendors::route('/'),
            'create' => Pages\CreateWeddingVendor::route('/create'),
            'edit' => Pages\EditWeddingVendor::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with('categories');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['wedding_id'] = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $categoryIds = Arr::pull($data, 'categories', []);

        $wedding = Wedding::findOrFail($data['wedding_id']);

        return app(WeddingVendorService::class)->createOrUpdateForWedding($wedding, array_merge($data, [
            'category_ids' => $categoryIds,
        ]));
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $categoryIds = Arr::pull($data, 'categories', []);

        $record->update($data);

        $record->categories()->sync($categoryIds);

        return $record;
    }
}
