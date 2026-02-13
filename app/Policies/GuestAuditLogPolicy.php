<?php

namespace App\Policies;

use App\Models\GuestAuditLog;
use App\Models\User;
use App\Policies\Concerns\ChecksGuestAccess;

class GuestAuditLogPolicy
{
    use ChecksGuestAccess;

    public function viewAny(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function view(User $user, GuestAuditLog $log): bool
    {
        return $this->canAccessGuests($user, $log->wedding);
    }
}
