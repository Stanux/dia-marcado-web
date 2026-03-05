<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeddingGuestResource\Pages;
use App\Models\WeddingGuest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WeddingGuestResource extends WeddingScopedResource
{
    protected static ?string $model = WeddingGuest::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Convidados';

    protected static ?string $navigationGroup = null;

    protected static ?string $module = 'guests';

    protected static ?string $modelLabel = 'Convidado';

    protected static ?string $pluralModelLabel = 'Convidados';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Convidado')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nickname')
                            ->label('Apelido')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone')
                            ->placeholder('(00) 00000-0000')
                            ->mask('(99) 99999-9999')
                            ->maxLength(15),

                        Forms\Components\Select::make('primary_contact_id')
                            ->label('Contato Principal')
                            ->options(fn (?WeddingGuest $record): array => WeddingGuest::query()
                                ->primaryContacts()
                                ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->rule(function (?WeddingGuest $record): \Closure {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $selectedPrimary = WeddingGuest::query()->find($value);
                                    if (!$selectedPrimary) {
                                        return;
                                    }

                                    if ($record && (string) $record->getKey() === (string) $selectedPrimary->getKey()) {
                                        $fail('O contato principal não pode ser o próprio convidado.');
                                        return;
                                    }

                                    if ($selectedPrimary->primary_contact_id !== null) {
                                        $fail('O contato definido como Principal já está cadastrado como convidado.');
                                    }
                                };
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable()
                            ->helperText('Se vazio, este registro também é um contato principal.'),

                        Forms\Components\Select::make('side')
                            ->label('Lado')
                            ->options([
                                'bride' => 'Noiva',
                                'groom' => 'Noivo',
                                'both' => 'Ambos',
                            ])
                            ->default('both')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_child')
                            ->label('Criança')
                            ->inline(false)
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->inline(false)
                            ->default(true),
                    ])
                    ->columns(4),
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

                Tables\Columns\TextColumn::make('primaryContact.name')
                    ->label('Contato Principal')
                    ->placeholder('Contato principal')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return static::applySortByPrimaryContactThenName($query, $direction);
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('side')
                    ->label('Lado')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'bride' => 'Noiva',
                        'groom' => 'Noivo',
                        default => 'Ambos',
                    }),

                Tables\Columns\IconColumn::make('is_child')
                    ->label('Criança')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('side')
                    ->label('Lado')
                    ->options([
                        'bride' => 'Noiva',
                        'groom' => 'Noivo',
                        'both' => 'Ambos',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort(fn (Builder $query, string $direction): Builder => static::applySortByPrimaryContactThenName($query, $direction));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeddingGuests::route('/'),
            'create' => Pages\CreateWeddingGuest::route('/create'),
            'edit' => Pages\EditWeddingGuest::route('/{record}/edit'),
        ];
    }

    public static function getSlug(): string
    {
        return 'guests-v2';
    }

    private static function applySortByPrimaryContactThenName(Builder $query, string $direction): Builder
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        $joins = $query->getQuery()->joins ?? [];
        $alreadyJoined = collect($joins)
            ->contains(fn ($join): bool => (string) ($join->table ?? '') === 'wedding_guests as primary_contacts');

        if (!$alreadyJoined) {
            $query->leftJoin(
                'wedding_guests as primary_contacts',
                'primary_contacts.id',
                '=',
                'wedding_guests.primary_contact_id'
            );
        }

        return $query
            ->orderByRaw('COALESCE(primary_contacts.name, wedding_guests.name) ' . $direction)
            ->orderBy('wedding_guests.name', $direction)
            ->select('wedding_guests.*');
    }
}
