<?php

namespace App\Filament\Resources\GuestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RsvpsRelationManager extends RelationManager
{
    protected static string $relationship = 'rsvps';

    protected static ?string $title = 'RSVPs';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('event_id')
                    ->label('Evento')
                    ->relationship('event', 'name')
                    ->required()
                    ->native(false),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'confirmed' => 'Confirmado',
                        'declined' => 'Recusado',
                        'maybe' => 'Talvez',
                        'no_response' => 'Sem resposta',
                    ])
                    ->required()
                    ->native(false),

                Forms\Components\KeyValue::make('responses')
                    ->label('Respostas')
                    ->addButtonLabel('Adicionar resposta'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event.name')
                    ->label('Evento')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'confirmed' => 'Confirmado',
                        'declined' => 'Recusado',
                        'maybe' => 'Talvez',
                        'no_response' => 'Sem resposta',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'declined' => 'danger',
                        'maybe' => 'warning',
                        'no_response' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('responded_at')
                    ->label('Respondido em')
                    ->dateTime('d/m/Y H:i')
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
