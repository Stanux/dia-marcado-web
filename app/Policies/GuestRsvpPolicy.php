<?php

namespace App\Policies;

use App\Models\GuestRsvp;
use App\Models\User;
use App\Policies\Concerns\ChecksGuestAccess;

class GuestRsvpPolicy
{
    use ChecksGuestAccess;

    public function create(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function viewAny(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function view(User $user, GuestRsvp $rsvp): bool
    {
        return $this->canAccessGuests($user, $rsvp->guest?->wedding);
    }

    public function update(User $user, GuestRsvp $rsvp): bool
    {
        return $this->canAccessGuests($user, $rsvp->guest?->wedding);
    }

    public function delete(User $user, GuestRsvp $rsvp): bool
    {
        return $this->canAccessGuests($user, $rsvp->guest?->wedding);
    }
}
