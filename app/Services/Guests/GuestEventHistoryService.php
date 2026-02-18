<?php

namespace App\Services\Guests;

use App\Models\GuestAuditLog;
use App\Models\GuestEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GuestEventHistoryService
{
    /**
     * @return Collection<int, array{
     *   occurred_at:\Illuminate\Support\Carbon|null,
     *   title:string,
     *   details:string,
     *   actor:?string
     * }>
     */
    public function timelineForEvent(GuestEvent $event, int $limit = 30): Collection
    {
        return GuestAuditLog::withoutGlobalScopes()
            ->with('actor:id,name')
            ->where('wedding_id', $event->wedding_id)
            ->whereIn('action', [
                'guest.event.created',
                'guest.event.updated',
                'guest.event.deleted',
            ])
            ->whereRaw(
                $this->jsonEqualsEventIdSql('guest_audit_logs', 'context', 'event_id'),
                [(string) $event->id],
            )
            ->orderByDesc('created_at')
            ->limit(max(1, min($limit, 200)))
            ->get()
            ->map(function (GuestAuditLog $log): array {
                $context = is_array($log->context) ? $log->context : [];

                return [
                    'occurred_at' => $log->created_at,
                    'title' => $this->titleForAction($log->action),
                    'details' => $this->detailsForAction($log->action, $context),
                    'actor' => $log->actor?->name,
                ];
            })
            ->values();
    }

    public function timelineText(GuestEvent $event, int $limit = 20): string
    {
        $events = $this->timelineForEvent($event, $limit);

        if ($events->isEmpty()) {
            return 'Nenhum histórico encontrado para este evento.';
        }

        return $events->map(function (array $row): string {
            $when = $row['occurred_at']?->format('d/m/Y H:i') ?? 'sem data';
            $actor = !empty($row['actor']) ? ' | por ' . $row['actor'] : '';
            $details = trim((string) ($row['details'] ?? ''));

            if ($details === '') {
                return "[{$when}] {$row['title']}{$actor}";
            }

            return "[{$when}] {$row['title']}{$actor} - {$details}";
        })->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function detailsForAction(string $action, array $context): string
    {
        return match ($action) {
            'guest.event.created' => implode(' | ', array_filter([
                !empty($context['slug']) ? 'Slug: ' . $context['slug'] : null,
                array_key_exists('is_active', $context) ? ('Ativo: ' . ($context['is_active'] ? 'sim' : 'nao')) : null,
                isset($context['questions_count']) ? 'Perguntas: ' . (int) $context['questions_count'] : null,
            ])),
            'guest.event.updated' => implode(' | ', array_filter([
                !empty($context['changed_fields']) && is_array($context['changed_fields'])
                    ? ('Campos: ' . implode(', ', $context['changed_fields']))
                    : null,
                isset($context['questions_before_count'], $context['questions_after_count'])
                    ? ('Perguntas: ' . (int) $context['questions_before_count'] . ' -> ' . (int) $context['questions_after_count'])
                    : null,
                array_key_exists('is_active', $context)
                    ? ('Ativo: ' . ($context['is_active'] ? 'sim' : 'nao'))
                    : null,
            ])),
            'guest.event.deleted' => implode(' | ', array_filter([
                !empty($context['slug']) ? 'Slug: ' . $context['slug'] : null,
            ])),
            default => '',
        };
    }

    private function titleForAction(string $action): string
    {
        return match ($action) {
            'guest.event.created' => 'Evento criado',
            'guest.event.updated' => 'Evento atualizado',
            'guest.event.deleted' => 'Evento removido',
            default => $action,
        };
    }

    private function jsonEqualsEventIdSql(string $table, string $column, string $key): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql' => "{$table}.{$column}->>'{$key}' = ?",
            'mysql' => "JSON_UNQUOTE(JSON_EXTRACT({$table}.{$column}, '$.\"{$key}\"')) = ?",
            default => "json_extract({$table}.{$column}, '$.{$key}') = ?",
        };
    }
}
