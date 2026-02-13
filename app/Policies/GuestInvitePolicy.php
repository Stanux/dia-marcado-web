<?php

namespace App\Policies;

use App\Models\GuestInvite;
use App\Models\User;
use App\Policies\Concerns\ChecksGuestAccess;

class GuestInvitePolicy
{
    use ChecksGuestAccess;

    public function viewAny(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function view(User $user, GuestInvite $invite): bool
    {
        return $this->canAccessGuests($user, $invite->household?->wedding);
    }

    public function create(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function update(User $user, GuestInvite $invite): bool
    {
        return $this->canAccessGuests($user, $invite->household?->wedding);
    }

    public function delete(User $user, GuestInvite $invite): bool
    {
        return $this->canAccessGuests($user, $invite->household?->wedding);
    }
}
