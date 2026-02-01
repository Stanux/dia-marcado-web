<?php

namespace App\Contracts;

use App\Models\PartnerInvite;
use App\Models\User;
use App\Models\Wedding;

/**
 * Interface for managing partner invitations.
 * 
 * Handles sending invites to partners, accepting/declining invites,
 * and managing the partner linking process.
 */
interface PartnerInviteServiceInterface
{
    /**
     * Send an invitation to a partner.
     * 
     * Determines if the partner email exists in the platform and sends
     * the appropriate type of invitation (new user or existing user).
     *
     * @param Wedding $wedding The wedding to invite the partner to
     * @param User $inviter The user sending the invitation
     * @param string $partnerEmail The partner's email address
     * @param string $partnerName The partner's name
     * @return PartnerInvite The created invitation
     */
    public function sendInvite(
        Wedding $wedding,
        User $inviter,
        string $partnerEmail,
        string $partnerName
    ): PartnerInvite;

    /**
     * Accept a partner invitation.
     * 
     * Links the user to the wedding as a couple member.
     * If the user was previously linked to another wedding, they will be unlinked.
     *
     * @param PartnerInvite $invite The invitation to accept
     * @param User $user The user accepting the invitation
     * @return void
     */
    public function acceptInvite(PartnerInvite $invite, User $user): void;

    /**
     * Decline a partner invitation.
     * 
     * Marks the invitation as declined without linking the user.
     *
     * @param PartnerInvite $invite The invitation to decline
     * @return void
     */
    public function declineInvite(PartnerInvite $invite): void;

    /**
     * Find a valid invitation by token.
     *
     * @param string $token The invitation token
     * @return PartnerInvite|null The invitation if found and valid, null otherwise
     */
    public function findByToken(string $token): ?PartnerInvite;

    /**
     * Check if an email already has a pending invitation for a wedding.
     *
     * @param Wedding $wedding The wedding to check
     * @param string $email The email to check
     * @return bool True if a pending invitation exists
     */
    public function hasPendingInvite(Wedding $wedding, string $email): bool;
}
