<?php

namespace App\Services;

use App\Contracts\PartnerInviteServiceInterface;
use App\Models\PartnerInvite;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\ExistingUserInviteNotification;
use App\Notifications\NewUserInviteNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Service for managing partner invitations.
 */
class PartnerInviteService implements PartnerInviteServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function sendInvite(
        Wedding $wedding,
        User $inviter,
        string $partnerEmail,
        string $partnerName
    ): PartnerInvite {
        // Check if email already exists in the platform
        $existingUser = User::where('email', $partnerEmail)->first();

        // Get the previous wedding if user exists and is linked to one
        $previousWedding = null;
        if ($existingUser) {
            $previousWedding = $existingUser->weddings()
                ->wherePivot('role', 'couple')
                ->first();
        }

        // Create the invitation
        $invite = PartnerInvite::create([
            'wedding_id' => $wedding->id,
            'inviter_id' => $inviter->id,
            'email' => $partnerEmail,
            'name' => $partnerName,
            'token' => Str::random(64),
            'status' => 'pending',
            'existing_user_id' => $existingUser?->id,
            'previous_wedding_id' => $previousWedding?->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Send the appropriate notification
        $this->sendNotification($invite, $inviter);

        return $invite;
    }

    /**
     * Send the appropriate notification based on whether the user exists.
     */
    protected function sendNotification(PartnerInvite $invite, User $inviter): void
    {
        if ($invite->isForExistingUser()) {
            // Send notification to existing user
            $invite->existingUser->notify(new ExistingUserInviteNotification($invite, $inviter));
        } else {
            // Send notification to email (new user)
            Notification::route('mail', $invite->email)
                ->notify(new NewUserInviteNotification($invite, $inviter));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function acceptInvite(PartnerInvite $invite, User $user): void
    {
        DB::transaction(function () use ($invite, $user) {
            // If user was linked to another wedding as couple, unlink them
            if ($invite->previous_wedding_id) {
                $user->weddings()->detach($invite->previous_wedding_id);
            }

            // Link user to the new wedding as couple
            $invite->wedding->users()->attach($user->id, [
                'role' => 'couple',
                'permissions' => [],
            ]);

            // Update user's current wedding
            $user->update([
                'current_wedding_id' => $invite->wedding_id,
                'onboarding_completed' => true,
            ]);

            // Mark invitation as accepted
            $invite->markAsAccepted();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function declineInvite(PartnerInvite $invite): void
    {
        $invite->markAsDeclined();
    }

    /**
     * {@inheritdoc}
     */
    public function findByToken(string $token): ?PartnerInvite
    {
        return PartnerInvite::where('token', $token)
            ->valid()
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function hasPendingInvite(Wedding $wedding, string $email): bool
    {
        return PartnerInvite::where('wedding_id', $wedding->id)
            ->where('email', $email)
            ->pending()
            ->exists();
    }
}
