<?php

namespace App\Policies;

use App\Models\GuestMessage;
use App\Models\User;
use App\Policies\Concerns\ChecksGuestAccess;

class GuestMessagePolicy
{
    use ChecksGuestAccess;

    public function viewAny(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function view(User $user, GuestMessage $message): bool
    {
        return $this->canAccessGuests($user, $message->wedding);
    }

    public function create(User $user): bool
    {
        return $this->canAccessGuests($user, null);
    }

    public function update(User $user, GuestMessage $message): bool
    {
        return $this->canAccessGuests($user, $message->wedding);
    }

    public function delete(User $user, GuestMessage $message): bool
    {
        return $this->canAccessGuests($user, $message->wedding);
    }
}
