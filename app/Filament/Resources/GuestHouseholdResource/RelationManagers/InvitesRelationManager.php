<?php

namespace App\Filament\Resources\GuestHouseholdResource\RelationManagers;

use App\Models\GuestInvite;
use App\Services\GuestInviteNotificationService;
use App\Services\Guests\InviteBulkActionService;
use App\Services\Guests\InviteManagementService;
use App\Services\Guests\InviteObservabilityService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InvitesRelationManager extends RelationManager
{
    protected static string $relationship = 'invites';

    protected static ?string $title = 'Convites';

    protected static ?bool $hasMaxUsesColumn = null;
    protected static ?bool $hasUsesCountColumn = null;
    protected static ?bool $hasRevokedAtColumn = null;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('guest_id')
                    ->label('Convidado específico (opcional)')
                    ->relationship('guest', 'name')
                    ->searchable()
                    ->native(false),
                Forms\Components\Select::make('channel')
                    ->label('Canal')
                    ->options([
                        'email' => 'Email',
                        'whatsapp' => 'WhatsApp',
                        'sms' => 'SMS',
                    ])
                    ->required()
                    ->default('email')
                    ->native(false),
                Forms\Components\TextInput::make('expires_in_days')
                    ->label('Expira em (dias)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(365),
                Forms\Components\TextInput::make('max_uses')
                    ->label('Máximo de usos')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(1000)
                    ->visible(fn (): bool => self::hasMaxUsesColumn()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Gerado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('guest.name')
                    ->label('Convidado')
                    ->placeholder('Núcleo inteiro')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'email' => 'Email',
                        'whatsapp' => 'WhatsApp',
                        'sms' => 'SMS',
                        default => 'Desconhecido',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'email' => 'info',
                        'whatsapp' => 'success',
                        'sms' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'sent' => 'Enviado',
                        'delivered' => 'Entregue',
                        'opened' => 'Aberto',
                        'expired' => 'Expirado',
                        'revoked' => 'Revogado',
                        default => $state ?: 'Desconhecido',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'opened' => 'success',
                        'delivered' => 'success',
                        'sent' => 'info',
                        'expired' => 'warning',
                        'revoked' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('uses_summary')
                    ->label('Usos')
                    ->state(fn (GuestInvite $record): string => $this->formatUses($record))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sem expiração')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('used_at')
                    ->label('Último uso')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca usado')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'sent' => 'Enviado',
                        'delivered' => 'Entregue',
                        'opened' => 'Aberto',
                        'expired' => 'Expirado',
                        'revoked' => 'Revogado',
                    ]),
                Tables\Filters\SelectFilter::make('channel')
                    ->label('Canal')
                    ->options([
                        'email' => 'Email',
                        'whatsapp' => 'WhatsApp',
                        'sms' => 'SMS',
                    ]),
                Tables\Filters\Filter::make('delivery_failed')
                    ->label('Falha de envio')
                    ->query(fn (Builder $query): Builder => $this->scopeDeliveryFailed($query)),
                Tables\Filters\Filter::make('reissued')
                    ->label('Reemitidos')
                    ->query(fn (Builder $query): Builder => $this->scopeAuditAction($query, 'guest.invite.reissued')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('createInvite')
                    ->label('Novo convite')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('guest_id')
                            ->label('Convidado específico (opcional)')
                            ->options(fn (): array => $this->getOwnerRecord()
                                ->guests()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->native(false),
                        Forms\Components\Select::make('channel')
                            ->label('Canal')
                            ->options([
                                'email' => 'Email',
                                'whatsapp' => 'WhatsApp',
                                'sms' => 'SMS',
                            ])
                            ->required()
                            ->default('email')
                            ->native(false),
                        Forms\Components\TextInput::make('expires_in_days')
                            ->label('Expira em (dias)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365),
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Máximo de usos')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->visible(fn (): bool => self::hasMaxUsesColumn()),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $invite = app(InviteManagementService::class)->createForHousehold(
                                household: $this->getOwnerRecord(),
                                data: $data,
                                actorId: auth()->id(),
                            );
                        } catch (\InvalidArgumentException $exception) {
                            Notification::make()
                                ->title('Erro ao gerar convite')
                                ->danger()
                                ->body($exception->getMessage())
                                ->send();
                            return;
                        }

                        $result = app(GuestInviteNotificationService::class)->send($invite);
                        if (!$result['ok']) {
                            Notification::make()
                                ->title('Convite gerado, mas não enviado')
                                ->warning()
                                ->body($result['message'])
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Convite criado e enviado')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('copyLink')
                    ->label('Copiar link')
                    ->icon('heroicon-o-clipboard')
                    ->action(function (GuestInvite $record): void {
                        $siteSlug = $this->getOwnerRecord()->wedding?->siteLayout?->slug;
                        $link = $siteSlug
                            ? url('/site/' . $siteSlug . '?token=' . $record->token)
                            : url('/site?token=' . $record->token);

                        Notification::make()
                            ->title('Link do convite')
                            ->success()
                            ->body($link)
                            ->send();
                    }),
                Tables\Actions\Action::make('timeline')
                    ->label('Histórico')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->action(function (GuestInvite $record): void {
                        $timelineText = app(InviteObservabilityService::class)->timelineText($record, 15);

                        Notification::make()
                            ->title('Linha do tempo do convite')
                            ->body($timelineText)
                            ->persistent()
                            ->send();
                    }),
                Tables\Actions\Action::make('reissue')
                    ->label('Reemitir')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('expires_in_days')
                            ->label('Expira em (dias)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365),
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Máximo de usos')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->visible(fn (): bool => self::hasMaxUsesColumn()),
                    ])
                    ->action(function (GuestInvite $record, array $data): void {
                        $invite = app(InviteManagementService::class)->reissue(
                            invite: $record,
                            data: $data,
                            actorId: auth()->id(),
                        );

                        $result = app(GuestInviteNotificationService::class)->send($invite);
                        if (!$result['ok']) {
                            Notification::make()
                                ->title('Convite reemitido, mas não enviado')
                                ->warning()
                                ->body($result['message'])
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Convite reemitido e enviado')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('revoke')
                    ->label('Revogar')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo')
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->visible(fn (GuestInvite $record): bool => self::hasRevokedAtColumn() && $record->status !== 'revoked')
                    ->action(function (GuestInvite $record, array $data): void {
                        try {
                            app(InviteManagementService::class)->revoke(
                                invite: $record,
                                reason: $data['reason'] ?? null,
                                actorId: auth()->id(),
                            );
                        } catch (\InvalidArgumentException $exception) {
                            Notification::make()
                                ->title('Erro ao revogar convite')
                                ->danger()
                                ->body($exception->getMessage())
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Convite revogado')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkReissue')
                        ->label('Reemitir selecionados')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('expires_in_days')
                                ->label('Expira em (dias)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(365),
                            Forms\Components\TextInput::make('max_uses')
                                ->label('Máximo de usos')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(1000)
                                ->visible(fn (): bool => self::hasMaxUsesColumn()),
                        ])
                        ->action(function (EloquentCollection $records, array $data): void {
                            $summary = app(InviteBulkActionService::class)->reissue(
                                invites: $records,
                                data: $data,
                                actorId: auth()->id(),
                            );

                            $notification = Notification::make()
                                ->title('Reemissão em lote concluída')
                                ->body(
                                    "{$summary['reissued']} convite(s) reemitido(s), {$summary['sent']} enviado(s)" .
                                        ($summary['failed_to_send'] > 0 ? " e {$summary['failed_to_send']} com falha de envio." : '.') .
                                        ($summary['failed'] > 0 ? " {$summary['failed']} falharam ao processar." : '')
                                );

                            if ($summary['failed'] > 0) {
                                $notification->warning();
                            } else {
                                $notification->success();
                            }

                            $notification->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('bulkRevoke')
                        ->label('Revogar selecionados')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->visible(fn (): bool => self::hasRevokedAtColumn())
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo')
                                ->maxLength(500)
                                ->rows(3),
                        ])
                        ->action(function (EloquentCollection $records, array $data): void {
                            $summary = app(InviteBulkActionService::class)->revoke(
                                invites: $records,
                                reason: $data['reason'] ?? null,
                                actorId: auth()->id(),
                            );

                            $notification = Notification::make()
                                ->title('Revogação em lote concluída')
                                ->body(
                                    "{$summary['revoked']} convite(s) revogado(s)" .
                                        ($summary['already_revoked'] > 0 ? ", {$summary['already_revoked']} já revogado(s)" : '') .
                                        ($summary['failed'] > 0 ? " e {$summary['failed']} com falha." : '.')
                                );

                            if ($summary['failed'] > 0) {
                                $notification->warning();
                            } else {
                                $notification->success();
                            }

                            $notification->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function scopeDeliveryFailed(Builder $query): Builder
    {
        $messageInviteExpression = self::jsonInviteIdExpression('guest_messages', 'payload');

        return $query->where(function (Builder $failureQuery) use ($messageInviteExpression): void {
            $failureQuery
                ->whereExists(function (QueryBuilder $subQuery) use ($messageInviteExpression): void {
                    $subQuery
                        ->selectRaw('1')
                        ->from('guest_messages')
                        ->where('guest_messages.status', 'failed')
                        ->whereRaw($messageInviteExpression);
                })
                ->orWhereExists(function (QueryBuilder $subQuery) use ($messageInviteExpression): void {
                    $subQuery
                        ->selectRaw('1')
                        ->from('guest_message_logs')
                        ->join('guest_messages', 'guest_messages.id', '=', 'guest_message_logs.message_id')
                        ->where('guest_message_logs.status', 'failed')
                        ->whereRaw($messageInviteExpression);
                });
        });
    }

    private function scopeAuditAction(Builder $query, string $action): Builder
    {
        $auditInviteExpression = self::jsonInviteIdExpression('guest_audit_logs', 'context');

        return $query->whereExists(function (QueryBuilder $subQuery) use ($action, $auditInviteExpression): void {
            $subQuery
                ->selectRaw('1')
                ->from('guest_audit_logs')
                ->where('guest_audit_logs.action', $action)
                ->whereRaw($auditInviteExpression);
        });
    }

    private function formatUses(GuestInvite $record): string
    {
        if (!self::hasUsesCountColumn()) {
            return '-';
        }

        $usesCount = (int) ($record->uses_count ?? 0);
        $maxUses = $record->max_uses;

        if ($maxUses === null || $maxUses === '') {
            return "{$usesCount}/ilimitado";
        }

        return "{$usesCount}/{$maxUses}";
    }

    private static function jsonInviteIdExpression(string $table, string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql' => "{$table}.{$column}->>'invite_id' = CAST(guest_invites.id AS text)",
            'mysql' => "JSON_UNQUOTE(JSON_EXTRACT({$table}.{$column}, '$.\"invite_id\"')) = guest_invites.id",
            default => "json_extract({$table}.{$column}, '$.invite_id') = guest_invites.id",
        };
    }

    private static function hasMaxUsesColumn(): bool
    {
        if (self::$hasMaxUsesColumn === null) {
            self::$hasMaxUsesColumn = Schema::hasColumn('guest_invites', 'max_uses');
        }

        return self::$hasMaxUsesColumn;
    }

    private static function hasUsesCountColumn(): bool
    {
        if (self::$hasUsesCountColumn === null) {
            self::$hasUsesCountColumn = Schema::hasColumn('guest_invites', 'uses_count');
        }

        return self::$hasUsesCountColumn;
    }

    private static function hasRevokedAtColumn(): bool
    {
        if (self::$hasRevokedAtColumn === null) {
            self::$hasRevokedAtColumn = Schema::hasColumn('guest_invites', 'revoked_at');
        }

        return self::$hasRevokedAtColumn;
    }
}
