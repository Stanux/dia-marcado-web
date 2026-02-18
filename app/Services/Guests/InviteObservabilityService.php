<?php

namespace App\Services\Guests;

use App\Models\GuestAuditLog;
use App\Models\GuestInvite;
use App\Models\GuestMessageLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InviteObservabilityService
{
    /**
     * @return Collection<int, array{
     *   occurred_at:\Illuminate\Support\Carbon|null,
     *   source:string,
     *   title:string,
     *   details:string
     * }>
     */
    public function timelineForInvite(GuestInvite $invite, int $limit = 50): Collection
    {
        $weddingId = $invite->household?->wedding_id;
        if (!$weddingId) {
            return collect();
        }

        $auditEvents = $this->auditEvents($invite, $weddingId);
        $messageEvents = $this->messageEvents($invite);

        $bootstrapEvent = collect([
            [
                'occurred_at' => $invite->created_at,
                'source' => 'invite',
                'title' => 'Convite criado',
                'details' => 'Canal: ' . $this->channelLabel($invite->channel),
            ],
        ]);

        return $bootstrapEvent
            ->merge($auditEvents)
            ->merge($messageEvents)
            ->sortByDesc(fn (array $event) => $event['occurred_at']?->getTimestamp() ?? 0)
            ->values()
            ->take(max(1, $limit));
    }

    public function timelineText(GuestInvite $invite, int $limit = 20): string
    {
        $timeline = $this->timelineForInvite($invite, $limit);

        if ($timeline->isEmpty()) {
            return 'Nenhum evento encontrado para este convite.';
        }

        return $timeline
            ->map(function (array $event): string {
                $occurredAt = $event['occurred_at']?->format('d/m/Y H:i') ?? 'sem data';
                $details = trim((string) ($event['details'] ?? ''));

                if ($details === '') {
                    return "[{$occurredAt}] {$event['title']}";
                }

                return "[{$occurredAt}] {$event['title']} - {$details}";
            })
            ->implode("\n");
    }

    /**
     * @return Collection<int, array{
     *   occurred_at:\Illuminate\Support\Carbon|null,
     *   source:string,
     *   title:string,
     *   details:string
     * }>
     */
    private function auditEvents(GuestInvite $invite, string $weddingId): Collection
    {
        return GuestAuditLog::withoutGlobalScopes()
            ->where('wedding_id', $weddingId)
            ->whereIn('action', [
                'guest.invite.created',
                'guest.invite.reissued',
                'guest.invite.revoked',
                'guest.invite.used',
                'guest.rsvp.public_submitted',
            ])
            ->whereRaw(
                $this->jsonEqualsInviteIdSql('guest_audit_logs', 'context', 'invite_id'),
                [(string) $invite->id]
            )
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function (GuestAuditLog $log): array {
                $context = $log->context ?? [];

                return [
                    'occurred_at' => $log->created_at,
                    'source' => 'audit',
                    'title' => $this->auditTitle($log->action),
                    'details' => $this->auditDetails($log->action, $context),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array{
     *   occurred_at:\Illuminate\Support\Carbon|null,
     *   source:string,
     *   title:string,
     *   details:string
     * }>
     */
    private function messageEvents(GuestInvite $invite): Collection
    {
        return GuestMessageLog::query()
            ->select([
                'guest_message_logs.*',
                'guest_messages.channel as message_channel',
            ])
            ->join('guest_messages', 'guest_messages.id', '=', 'guest_message_logs.message_id')
            ->whereRaw(
                $this->jsonEqualsInviteIdSql('guest_messages', 'payload', 'invite_id'),
                [(string) $invite->id]
            )
            ->orderByDesc('guest_message_logs.occurred_at')
            ->limit(100)
            ->get()
            ->map(function (GuestMessageLog $log): array {
                $metadata = $log->metadata ?? [];
                $channel = $log->getAttribute('message_channel');
                $contact = $metadata['contact'] ?? null;
                $error = $metadata['error'] ?? null;

                $detailsParts = array_filter([
                    'Canal: ' . $this->channelLabel($channel),
                    $contact ? 'Destino: ' . $contact : null,
                    $error ? 'Erro: ' . $error : null,
                ]);

                return [
                    'occurred_at' => $log->occurred_at ?? $log->created_at,
                    'source' => 'message',
                    'title' => $this->messageStatusTitle((string) $log->status),
                    'details' => implode(' | ', $detailsParts),
                ];
            })
            ->values();
    }

    private function auditTitle(string $action): string
    {
        return match ($action) {
            'guest.invite.created' => 'Convite criado (auditoria)',
            'guest.invite.reissued' => 'Convite reemitido',
            'guest.invite.revoked' => 'Convite revogado',
            'guest.invite.used' => 'Convite utilizado',
            'guest.rsvp.public_submitted' => 'RSVP público enviado',
            default => $action,
        };
    }

    private function auditDetails(string $action, array $context): string
    {
        return match ($action) {
            'guest.invite.created', 'guest.invite.reissued' => implode(' | ', array_filter([
                isset($context['channel']) ? 'Canal: ' . $this->channelLabel((string) $context['channel']) : null,
                isset($context['max_uses']) && $context['max_uses'] !== null ? 'Max usos: ' . $context['max_uses'] : 'Max usos: ilimitado',
                !empty($context['expires_at']) ? 'Expira: ' . (string) $context['expires_at'] : null,
            ])),
            'guest.invite.revoked' => !empty($context['reason']) ? 'Motivo: ' . $context['reason'] : 'Sem motivo informado',
            'guest.invite.used' => implode(' | ', array_filter([
                isset($context['uses_count']) ? 'Usos: ' . $context['uses_count'] : null,
                isset($context['max_uses']) && $context['max_uses'] !== null ? 'Max usos: ' . $context['max_uses'] : 'Max usos: ilimitado',
            ])),
            'guest.rsvp.public_submitted' => implode(' | ', array_filter([
                !empty($context['status']) ? 'Status RSVP: ' . $this->rsvpStatusLabel((string) $context['status']) : null,
                !empty($context['access_mode']) ? 'Acesso: ' . (string) $context['access_mode'] : null,
            ])),
            default => '',
        };
    }

    private function messageStatusTitle(string $status): string
    {
        return match ($status) {
            'sending' => 'Envio iniciado',
            'sent' => 'Envio concluído',
            'delivered' => 'Mensagem entregue',
            'clicked' => 'Link acessado',
            'failed' => 'Falha no envio',
            default => 'Status: ' . $status,
        };
    }

    private function channelLabel(?string $channel): string
    {
        return match ($channel) {
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
            default => ucfirst($channel ?: 'desconhecido'),
        };
    }

    private function rsvpStatusLabel(string $status): string
    {
        return match ($status) {
            'confirmed' => 'Confirmado',
            'declined' => 'Recusado',
            'maybe' => 'Talvez',
            'no_response' => 'Sem resposta',
            default => $status,
        };
    }

    private function jsonEqualsInviteIdSql(string $table, string $column, string $key): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql' => "{$table}.{$column}->>'{$key}' = ?",
            'mysql' => "JSON_UNQUOTE(JSON_EXTRACT({$table}.{$column}, '$.\"{$key}\"')) = ?",
            default => "json_extract({$table}.{$column}, '$.{$key}') = ?",
        };
    }
}
