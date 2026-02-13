<?php

namespace App\Policies;

use App\Models\GuestEvent;
use App\Models\User;
use App\Policies\Concerns\ChecksGuestAccess;

class GuestEventPolicy
{
    use ChecksGuestAccess;

    public function viewAny(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function view(User $user, GuestEvent $event): bool
    {
        return $this->canAccessGuests($user, $event->wedding);
    }

    public function create(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function update(User $user, GuestEvent $event): bool
    {
        return $this->canAccessGuests($user, $event->wedding);
    }

    public function delete(User $user, GuestEvent $event): bool
    {
        return $this->canAccessGuests($user, $event->wedding);
    }
}
