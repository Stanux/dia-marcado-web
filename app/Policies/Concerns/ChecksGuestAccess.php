<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Models\Wedding;
use App\Services\PermissionService;

trait ChecksGuestAccess
{
    protected function canAccessGuests(User $user, ?Wedding $wedding): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$wedding) {
            $wedding = $user->currentWedding;
        }

        if (!$wedding) {
            $weddingId = $user->current_wedding_id ?? session('filament_wedding_id');
            if ($weddingId) {
                $wedding = Wedding::find($weddingId);
            }
        }

        if (!$wedding) {
            return false;
        }

        return app(PermissionService::class)->canAccess($user, 'guests', $wedding);
    }
}
