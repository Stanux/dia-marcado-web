<?php

namespace App\Policies;

use App\Models\GuestCheckin;
use App\Models\User;
use App\Policies\Concerns\ChecksGuestAccess;

class GuestCheckinPolicy
{
    use ChecksGuestAccess;

    public function viewAny(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function view(User $user, GuestCheckin $checkin): bool
    {
        return $this->canAccessGuests($user, $checkin->guest?->wedding);
    }

    public function create(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function update(User $user, GuestCheckin $checkin): bool
    {
        return $this->canAccessGuests($user, $checkin->guest?->wedding);
    }

    public function delete(User $user, GuestCheckin $checkin): bool
    {
        return $this->canAccessGuests($user, $checkin->guest?->wedding);
    }
}
