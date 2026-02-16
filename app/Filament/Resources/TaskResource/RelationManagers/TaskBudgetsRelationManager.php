<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use App\Models\VendorCategory;
use App\Models\Wedding;
use App\Models\WeddingVendor;
use App\Services\Planning\WeddingVendorService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TaskBudgetsRelationManager extends RelationManager
{
    protected static string $relationship = 'budgets';

    protected static ?string $title = 'Orçamentos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('wedding_vendor_id')
                    ->label('Fornecedor')
                    ->options(function () {
                        $task = $this->getOwnerRecord();

                        if (!$task) {
                            return [];
                        }

                        return WeddingVendor::where('wedding_id', $task->wedding_id)
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->createOptionForm([
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
                        Forms\Components\Select::make('category_ids')
                            ->label('Categorias')
                            ->options(VendorCategory::orderBy('sort')->pluck('name', 'id'))
                            ->multiple()
                            ->required(),
                    ])
                    ->createOptionUsing(function (array $data): string {
                        $task = $this->getOwnerRecord();

                        if (!$task) {
                            throw new \RuntimeException('Tarefa não encontrada para criação do fornecedor.');
                        }

                        $wedding = Wedding::find($task->wedding_id);

                        if (!$wedding) {
                            throw new \RuntimeException('Casamento não encontrado para criação do fornecedor.');
                        }

                        $vendor = app(WeddingVendorService::class)->createOrUpdateForWedding($wedding, $data);

                        return $vendor->id;
                    }),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'negotiation' => 'Em negociação',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                    ])
                    ->default('negotiation')
                    ->required(),

                Forms\Components\TextInput::make('value')
                    ->label('Valor')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('R$'),

                Forms\Components\DatePicker::make('valid_until')
                    ->label('Validade'),

                Forms\Components\Textarea::make('notes')
                    ->label('Observações')
                    ->rows(3),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('weddingVendor.name')
                    ->label('Fornecedor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'negotiation' => 'Em negociação',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'negotiation' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->money('BRL', true),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Validade')
                    ->date('d/m/Y')
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $task = $this->getOwnerRecord();

        if ($task) {
            $data['wedding_id'] = $task->wedding_id;
        }

        return $data;
    }

    public function canCreate(): bool
    {
        $task = $this->getOwnerRecord();

        if ($task?->plan && $task->plan->isArchived() && !auth()->user()?->isAdmin()) {
            return false;
        }

        return parent::canCreate();
    }

    public function canEdit(Model $record): bool
    {
        $task = $this->getOwnerRecord();

        if ($task?->plan && $task->plan->isArchived() && !auth()->user()?->isAdmin()) {
            return false;
        }

        return parent::canEdit($record);
    }

    public function canDelete(Model $record): bool
    {
        $task = $this->getOwnerRecord();

        if ($task?->plan && $task->plan->isArchived() && !auth()->user()?->isAdmin()) {
            return false;
        }

        return parent::canDelete($record);
    }
}
