<?php

namespace App\Filament\Resources\GuestHouseholdResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class GuestsRelationManager extends RelationManager
{
    protected static string $relationship = 'guests';

    protected static ?string $title = 'Convidados';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone')
                    ->label('Telefone')
                    ->maxLength(30),

                Forms\Components\TextInput::make('nickname')
                    ->label('Apelido')
                    ->maxLength(100),

                Forms\Components\Select::make('role_in_household')
                    ->label('Papel no núcleo')
                    ->options([
                        'head' => 'Responsável',
                        'spouse' => 'Cônjuge',
                        'child' => 'Filho(a)',
                        'plus_one' => 'Acompanhante',
                        'other' => 'Outro',
                    ])
                    ->native(false),

                Forms\Components\Toggle::make('is_child')
                    ->label('Criança')
                    ->inline(false),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'declined' => 'Recusado',
                        'maybe' => 'Talvez',
                    ])
                    ->default('pending')
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('Observações')
                    ->rows(2)
                    ->maxLength(2000),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'declined' => 'Recusado',
                        'maybe' => 'Talvez',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'declined' => 'danger',
                        'maybe' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
