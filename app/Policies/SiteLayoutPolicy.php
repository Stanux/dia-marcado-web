<?php

namespace App\Policies;

use App\Models\SiteLayout;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for SiteLayout model access control.
 * 
 * Controls access based on user roles:
 * - Admin: Full access to all sites
 * - Couple: Full access to their wedding's site
 * - Organizer with 'sites' permission: Can view and edit, but not publish
 * - Guest: No access
 */
class SiteLayoutPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any site layouts.
     * 
     * Access granted to:
     * - Admin users
     * - Couple members in the current wedding
     * - Organizers with 'sites' permission in the current wedding
     */
    public function viewAny(User $user): bool
    {
        // Admin can view all
        if ($user->isAdmin()) {
            return true;
        }

        // Get current wedding context
        $wedding = $user->currentWedding;
        if (!$wedding) {
            return false;
        }

        // Couple can view
        if ($user->isCoupleIn($wedding)) {
            return true;
        }

        // Organizer with 'sites' permission can view
        if ($user->isOrganizerIn($wedding) && $user->hasPermissionIn($wedding, 'sites')) {
            return true;
        }

        // Guest and others cannot view
        return false;
    }

    /**
     * Determine whether the user can view the site layout.
     * 
     * Access granted to:
     * - Admin users
     * - Couple members in the site's wedding
     * - Organizers with 'sites' permission in the site's wedding
     */
    public function view(User $user, SiteLayout $site): bool
    {
        // Admin can view all
        if ($user->isAdmin()) {
            return true;
        }

        // Get the wedding associated with the site
        $wedding = $site->wedding;
        if (!$wedding) {
            return false;
        }

        // Couple can view their wedding's site
        if ($user->isCoupleIn($wedding)) {
            return true;
        }

        // Organizer with 'sites' permission can view
        if ($user->isOrganizerIn($wedding) && $user->hasPermissionIn($wedding, 'sites')) {
            return true;
        }

        // Guest and others cannot view
        return false;
    }

    /**
     * Determine whether the user can create site layouts.
     * 
     * Access granted to:
     * - Admin users
     * - Couple members
     */
    public function create(User $user): bool
    {
        // Admin can create
        if ($user->isAdmin()) {
            return true;
        }

        // Get current wedding context
        $wedding = $user->currentWedding;
        if (!$wedding) {
            return false;
        }

        // Couple can create
        if ($user->isCoupleIn($wedding)) {
            return true;
        }

        // Others cannot create
        return false;
    }

    /**
     * Determine whether the user can update the site layout.
     * 
     * Same rules as view().
     */
    public function update(User $user, SiteLayout $site): bool
    {
        return $this->view($user, $site);
    }

    /**
     * Determine whether the user can publish the site layout.
     * 
     * Access granted to:
     * - Admin users
     * - Couple members in the site's wedding
     * 
     * Organizers cannot publish even with 'sites' permission.
     */
    public function publish(User $user, SiteLayout $site): bool
    {
        // Admin can publish
        if ($user->isAdmin()) {
            return true;
        }

        // Get the wedding associated with the site
        $wedding = $site->wedding;
        if (!$wedding) {
            return false;
        }

        // Couple can publish their wedding's site
        if ($user->isCoupleIn($wedding)) {
            return true;
        }

        // Organizers cannot publish (even with 'sites' permission)
        // Guest and others cannot publish
        return false;
    }

    /**
     * Determine whether the user can delete the site layout.
     * 
     * Access granted to:
     * - Admin users
     * - Couple members in the site's wedding
     */
    public function delete(User $user, SiteLayout $site): bool
    {
        // Admin can delete
        if ($user->isAdmin()) {
            return true;
        }

        // Get the wedding associated with the site
        $wedding = $site->wedding;
        if (!$wedding) {
            return false;
        }

        // Couple can delete their wedding's site
        if ($user->isCoupleIn($wedding)) {
            return true;
        }

        // Others cannot delete
        return false;
    }

    /**
     * Determine whether the user can restore the site layout.
     * 
     * Same rules as delete().
     */
    public function restore(User $user, SiteLayout $site): bool
    {
        return $this->delete($user, $site);
    }

    /**
     * Determine whether the user can permanently delete the site layout.
     * 
     * Same rules as delete().
     */
    public function forceDelete(User $user, SiteLayout $site): bool
    {
        return $this->delete($user, $site);
    }
}
