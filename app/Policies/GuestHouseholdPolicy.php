<?php

namespace App\Policies;

use App\Models\GuestHousehold;
use App\Models\User;
use App\Policies\Concerns\ChecksGuestAccess;

class GuestHouseholdPolicy
{
    use ChecksGuestAccess;

    public function viewAny(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function view(User $user, GuestHousehold $household): bool
    {
        return $this->canAccessGuests($user, $household->wedding);
    }

    public function create(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function update(User $user, GuestHousehold $household): bool
    {
        return $this->canAccessGuests($user, $household->wedding);
    }

    public function delete(User $user, GuestHousehold $household): bool
    {
        return $this->canAccessGuests($user, $household->wedding);
    }
}
