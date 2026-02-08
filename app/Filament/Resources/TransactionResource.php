<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

/**
 * Filament Resource for viewing Transactions.
 * 
 * Read-only resource for viewing gift purchase transactions
 * with filtering and CSV export capabilities.
 * 
 * @Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 11.3
 */
class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?string $navigationLabel = 'Recebimentos';

    protected static ?string $modelLabel = 'Recebimento';

    protected static ?string $pluralModelLabel = 'Recebimentos';

    protected static ?int $navigationSort = 4; // Após Itens de Presente (3), antes de Editor do Site (5)

    public static function form(Form $form): Form
    {
        // Read-only form for viewing transaction details
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Transação')
                    ->schema([
                        Forms\Components\TextInput::make('internal_id')
                            ->label('ID Interno')
                            ->disabled(),

                        Forms\Components\TextInput::make('pagseguro_transaction_id')
                            ->label('ID PagSeguro')
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->disabled()
                            ->badge(),

                        Forms\Components\TextInput::make('payment_method')
                            ->label('Método de Pagamento')
                            ->disabled()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'credit_card' => 'Cartão de Crédito',
                                'pix' => 'PIX',
                                default => $state,
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Presente')
                    ->schema([
                        Forms\Components\TextInput::make('giftItem.name')
                            ->label('Nome do Presente')
                            ->disabled(),

                        Forms\Components\TextInput::make('original_unit_price')
                            ->label('Preço Original')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? 'R$ ' . number_format($state / 100, 2, ',', '.') : '-'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Valores Financeiros')
                    ->schema([
                        Forms\Components\TextInput::make('gross_amount')
                            ->label('Valor Bruto')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? 'R$ ' . number_format($state / 100, 2, ',', '.') : '-'),

                        Forms\Components\TextInput::make('fee_percentage')
                            ->label('Taxa (%)')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . '%' : '-'),

                        Forms\Components\TextInput::make('fee_amount')
                            ->label('Valor da Taxa')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? 'R$ ' . number_format($state / 100, 2, ',', '.') : '-'),

                        Forms\Components\TextInput::make('net_amount_couple')
                            ->label('Valor Líquido (Noivos)')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? 'R$ ' . number_format($state / 100, 2, ',', '.') : '-'),

                        Forms\Components\TextInput::make('platform_amount')
                            ->label('Valor Plataforma')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? 'R$ ' . number_format($state / 100, 2, ',', '.') : '-'),

                        Forms\Components\TextInput::make('fee_modality')
                            ->label('Modalidade de Taxa')
                            ->disabled()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'couple_pays' => 'Noivos Pagam',
                                'guest_pays' => 'Convidados Pagam',
                                default => $state,
                            }),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Datas')
                    ->schema([
                        Forms\Components\TextInput::make('created_at')
                            ->label('Criado em')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? $state->format('d/m/Y H:i:s') : '-'),

                        Forms\Components\TextInput::make('confirmed_at')
                            ->label('Confirmado em')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? $state->format('d/m/Y H:i:s') : '-'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informações Adicionais')
                    ->schema([
                        Forms\Components\Textarea::make('error_message')
                            ->label('Mensagem de Erro')
                            ->disabled()
                            ->rows(2)
                            ->visible(fn ($record) => !empty($record?->error_message)),

                        Forms\Components\Textarea::make('pagseguro_response')
                            ->label('Resposta PagSeguro (JSON)')
                            ->disabled()
                            ->rows(4)
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-')
                            ->visible(fn ($record) => !empty($record?->pagseguro_response)),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('internal_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->limit(15),

                Tables\Columns\TextColumn::make('giftItem.name')
                    ->label('Presente')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('gross_amount')
                    ->label('Valor')
                    ->money('BRL', divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_amount_couple')
                    ->label('Líquido Noivos')
                    ->money('BRL', divideBy: 100)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'credit_card' => 'Cartão',
                        'pix' => 'PIX',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'credit_card' => 'info',
                        'pix' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'failed' => 'Falhou',
                        'refunded' => 'Reembolsado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('confirmed_at')
                    ->label('Confirmado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'failed' => 'Falhou',
                        'refunded' => 'Reembolsado',
                    ])
                    ->multiple(),

                SelectFilter::make('payment_method')
                    ->label('Método de Pagamento')
                    ->options([
                        'credit_card' => 'Cartão de Crédito',
                        'pix' => 'PIX',
                    ])
                    ->multiple(),

                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Data Inicial'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Data Final'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Criado a partir de ' . \Carbon\Carbon::parse($data['created_from'])->format('d/m/Y'))
                                ->removeField('created_from');
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Criado até ' . \Carbon\Carbon::parse($data['created_until'])->format('d/m/Y'))
                                ->removeField('created_until');
                        }

                        return $indicators;
                    }),

                Filter::make('confirmed_at')
                    ->form([
                        Forms\Components\DatePicker::make('confirmed_from')
                            ->label('Confirmado a partir de'),
                        Forms\Components\DatePicker::make('confirmed_until')
                            ->label('Confirmado até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['confirmed_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('confirmed_at', '>=', $date),
                            )
                            ->when(
                                $data['confirmed_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('confirmed_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['confirmed_from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Confirmado a partir de ' . \Carbon\Carbon::parse($data['confirmed_from'])->format('d/m/Y'))
                                ->removeField('confirmed_from');
                        }

                        if ($data['confirmed_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Confirmado até ' . \Carbon\Carbon::parse($data['confirmed_until'])->format('d/m/Y'))
                                ->removeField('confirmed_until');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Exportar CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($records) {
                        return static::exportToCsv($records);
                    }),
            ])
            ->headerActions([
                Action::make('export_all')
                    ->label('Exportar Tudo (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $query = static::getEloquentQuery();
                        $records = $query->with('giftItem')->get();
                        return static::exportToCsv($records);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Export transactions to CSV
     */
    protected static function exportToCsv($records)
    {
        $filename = 'transacoes_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'ID Interno',
                'ID PagSeguro',
                'Presente',
                'Preço Original',
                'Valor Bruto',
                'Taxa (%)',
                'Valor Taxa',
                'Líquido Noivos',
                'Valor Plataforma',
                'Modalidade Taxa',
                'Método Pagamento',
                'Status',
                'Criado em',
                'Confirmado em',
                'Erro',
            ], ';');

            // Data rows
            foreach ($records as $transaction) {
                fputcsv($file, [
                    $transaction->internal_id,
                    $transaction->pagseguro_transaction_id ?? '',
                    $transaction->giftItem?->name ?? '',
                    number_format($transaction->original_unit_price / 100, 2, ',', '.'),
                    number_format($transaction->gross_amount / 100, 2, ',', '.'),
                    number_format($transaction->fee_percentage, 2, ',', '.'),
                    number_format($transaction->fee_amount / 100, 2, ',', '.'),
                    number_format($transaction->net_amount_couple / 100, 2, ',', '.'),
                    number_format($transaction->platform_amount / 100, 2, ',', '.'),
                    $transaction->fee_modality === 'couple_pays' ? 'Noivos Pagam' : 'Convidados Pagam',
                    $transaction->payment_method === 'credit_card' ? 'Cartão de Crédito' : 'PIX',
                    match ($transaction->status) {
                        'pending' => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'failed' => 'Falhou',
                        'refunded' => 'Reembolsado',
                        default => $transaction->status,
                    },
                    $transaction->created_at?->format('d/m/Y H:i:s') ?? '',
                    $transaction->confirmed_at?->format('d/m/Y H:i:s') ?? '',
                    $transaction->error_message ?? '',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
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

    // Disable create and edit
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
