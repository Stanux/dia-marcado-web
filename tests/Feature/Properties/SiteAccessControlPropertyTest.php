<?php

namespace Tests\Feature\Properties;

use App\Models\SiteLayout;
use App\Models\User;
use App\Models\Wedding;
use App\Policies\SiteLayoutPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: wedding-site-builder, Property 1: Controle de Acesso por Perfil
 * 
 * For any user attempting to access the Sites module, access SHALL be granted if and only if:
 * - User is Admin, OR
 * - User is Couple in the wedding, OR
 * - User is Organizer with 'sites' permission in the wedding
 * 
 * All other users (Guests, Organizers without permission) SHALL receive 403 Forbidden.
 * 
 * Validates: Requirements 1.4, 1.5
 */
class SiteAccessControlPropertyTest extends TestCase
{
    use RefreshDatabase;

    private SiteLayoutPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SiteLayoutPolicy();
    }

    /**
     * Property test: Admin users can access any site
     * @test
     */
    public function admin_users_can_access_any_site(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->admin()->create();
            $wedding = Wedding::factory()->create();
            $site = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => 'admin-test-site-' . $i,
                'draft_content' => ['version' => '1.0', 'sections' => []],
            ]);

            // Admin can view any site
            $this->assertTrue(
                $this->policy->viewAny($admin),
                "Admin should be able to view any sites"
            );
            $this->assertTrue(
                $this->policy->view($admin, $site),
                "Admin should be able to view specific site"
            );
            $this->assertTrue(
                $this->policy->create($admin),
                "Admin should be able to create sites"
            );
            $this->assertTrue(
                $this->policy->update($admin, $site),
                "Admin should be able to update any site"
            );
            $this->assertTrue(
                $this->policy->publish($admin, $site),
                "Admin should be able to publish any site"
            );
            $this->assertTrue(
                $this->policy->delete($admin, $site),
                "Admin should be able to delete any site"
            );
        }
    }

    /**
     * Property test: Couple can access their own wedding's site
     * @test
     */
    public function couple_can_access_their_own_wedding_site(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $coupleUser = User::factory()->create([
                'current_wedding_id' => $wedding->id,
            ]);
            
            // Attach user as couple to the wedding
            $wedding->users()->attach($coupleUser->id, [
                'role' => 'couple',
                'permissions' => [],
            ]);

            $site = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => 'couple-test-site-' . $i,
                'draft_content' => ['version' => '1.0', 'sections' => []],
            ]);

            // Couple can access their wedding's site
            $this->assertTrue(
                $this->policy->viewAny($coupleUser),
                "Couple should be able to view sites"
            );
            $this->assertTrue(
                $this->policy->view($coupleUser, $site),
                "Couple should be able to view their wedding's site"
            );
            $this->assertTrue(
                $this->policy->create($coupleUser),
                "Couple should be able to create sites"
            );
            $this->assertTrue(
                $this->policy->update($coupleUser, $site),
                "Couple should be able to update their wedding's site"
            );
            $this->assertTrue(
                $this->policy->publish($coupleUser, $site),
                "Couple should be able to publish their wedding's site"
            );
            $this->assertTrue(
                $this->policy->delete($coupleUser, $site),
                "Couple should be able to delete their wedding's site"
            );
        }
    }

    /**
     * Property test: Couple cannot access another wedding's site
     * @test
     */
    public function couple_cannot_access_another_wedding_site(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Create two weddings
            $ownWedding = Wedding::factory()->create();
            $otherWedding = Wedding::factory()->create();
            
            $coupleUser = User::factory()->create([
                'current_wedding_id' => $ownWedding->id,
            ]);
            
            // Attach user as couple to their own wedding
            $ownWedding->users()->attach($coupleUser->id, [
                'role' => 'couple',
                'permissions' => [],
            ]);

            // Create site for the other wedding
            $otherSite = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $otherWedding->id,
                'slug' => 'other-wedding-site-' . $i,
                'draft_content' => ['version' => '1.0', 'sections' => []],
            ]);

            // Couple cannot access another wedding's site
            $this->assertFalse(
                $this->policy->view($coupleUser, $otherSite),
                "Couple should not be able to view another wedding's site"
            );
            $this->assertFalse(
                $this->policy->update($coupleUser, $otherSite),
                "Couple should not be able to update another wedding's site"
            );
            $this->assertFalse(
                $this->policy->publish($coupleUser, $otherSite),
                "Couple should not be able to publish another wedding's site"
            );
            $this->assertFalse(
                $this->policy->delete($coupleUser, $otherSite),
                "Couple should not be able to delete another wedding's site"
            );
        }
    }

    /**
     * Property test: Organizer with 'sites' permission can view and update but not publish
     * @test
     */
    public function organizer_with_sites_permission_can_view_and_update_but_not_publish(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $organizer = User::factory()->create([
                'current_wedding_id' => $wedding->id,
            ]);
            
            // Attach user as organizer with 'sites' permission
            $wedding->users()->attach($organizer->id, [
                'role' => 'organizer',
                'permissions' => ['sites'],
            ]);

            $site = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => 'organizer-with-perm-site-' . $i,
                'draft_content' => ['version' => '1.0', 'sections' => []],
            ]);

            // Organizer with permission can view and update
            $this->assertTrue(
                $this->policy->viewAny($organizer),
                "Organizer with 'sites' permission should be able to view sites"
            );
            $this->assertTrue(
                $this->policy->view($organizer, $site),
                "Organizer with 'sites' permission should be able to view specific site"
            );
            $this->assertTrue(
                $this->policy->update($organizer, $site),
                "Organizer with 'sites' permission should be able to update site"
            );
            
            // Organizer cannot publish or delete
            $this->assertFalse(
                $this->policy->publish($organizer, $site),
                "Organizer should not be able to publish site"
            );
            $this->assertFalse(
                $this->policy->delete($organizer, $site),
                "Organizer should not be able to delete site"
            );
            $this->assertFalse(
                $this->policy->create($organizer),
                "Organizer should not be able to create sites"
            );
        }
    }

    /**
     * Property test: Organizer without 'sites' permission cannot access sites
     * @test
     */
    public function organizer_without_sites_permission_cannot_access_sites(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $organizer = User::factory()->create([
                'current_wedding_id' => $wedding->id,
            ]);
            
            // Attach user as organizer without 'sites' permission
            $otherPermissions = ['tasks', 'guests', 'finance'];
            $wedding->users()->attach($organizer->id, [
                'role' => 'organizer',
                'permissions' => $otherPermissions,
            ]);

            $site = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => 'organizer-no-perm-site-' . $i,
                'draft_content' => ['version' => '1.0', 'sections' => []],
            ]);

            // Organizer without permission cannot access
            $this->assertFalse(
                $this->policy->viewAny($organizer),
                "Organizer without 'sites' permission should not be able to view sites"
            );
            $this->assertFalse(
                $this->policy->view($organizer, $site),
                "Organizer without 'sites' permission should not be able to view specific site"
            );
            $this->assertFalse(
                $this->policy->update($organizer, $site),
                "Organizer without 'sites' permission should not be able to update site"
            );
            $this->assertFalse(
                $this->policy->publish($organizer, $site),
                "Organizer without 'sites' permission should not be able to publish site"
            );
            $this->assertFalse(
                $this->policy->delete($organizer, $site),
                "Organizer without 'sites' permission should not be able to delete site"
            );
        }
    }

    /**
     * Property test: Guest cannot access sites
     * @test
     */
    public function guest_cannot_access_sites(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $guest = User::factory()->create([
                'current_wedding_id' => $wedding->id,
            ]);
            
            // Attach user as guest
            $wedding->users()->attach($guest->id, [
                'role' => 'guest',
                'permissions' => [],
            ]);

            $site = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => 'guest-test-site-' . $i,
                'draft_content' => ['version' => '1.0', 'sections' => []],
            ]);

            // Guest cannot access any site operations
            $this->assertFalse(
                $this->policy->viewAny($guest),
                "Guest should not be able to view sites"
            );
            $this->assertFalse(
                $this->policy->view($guest, $site),
                "Guest should not be able to view specific site"
            );
            $this->assertFalse(
                $this->policy->create($guest),
                "Guest should not be able to create sites"
            );
            $this->assertFalse(
                $this->policy->update($guest, $site),
                "Guest should not be able to update site"
            );
            $this->assertFalse(
                $this->policy->publish($guest, $site),
                "Guest should not be able to publish site"
            );
            $this->assertFalse(
                $this->policy->delete($guest, $site),
                "Guest should not be able to delete site"
            );
        }
    }

    /**
     * Property test: User without wedding context cannot access sites
     * @test
     */
    public function user_without_wedding_context_cannot_access_sites(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // User without current_wedding_id
            $user = User::factory()->create([
                'current_wedding_id' => null,
            ]);

            $wedding = Wedding::factory()->create();
            $site = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => 'no-context-site-' . $i,
                'draft_content' => ['version' => '1.0', 'sections' => []],
            ]);

            // User without wedding context cannot access
            $this->assertFalse(
                $this->policy->viewAny($user),
                "User without wedding context should not be able to view sites"
            );
            $this->assertFalse(
                $this->policy->view($user, $site),
                "User without wedding context should not be able to view specific site"
            );
            $this->assertFalse(
                $this->policy->create($user),
                "User without wedding context should not be able to create sites"
            );
        }
    }
}
