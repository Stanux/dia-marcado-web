<?php

namespace App\Services\Guests;

use App\Models\GuestAuditLog;

class GuestAuditLogService
{
    public function record(
        string $weddingId,
        string $action,
        array $context = [],
        ?string $actorId = null,
    ): void {
        GuestAuditLog::withoutGlobalScopes()->create([
            'wedding_id' => $weddingId,
            'actor_id' => $actorId,
            'action' => $action,
            'context' => $context,
        ]);
    }
}
