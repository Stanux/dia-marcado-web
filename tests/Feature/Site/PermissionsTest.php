<?php

namespace Tests\Feature\Site;

use App\Models\SiteLayout;
use App\Models\User;
use App\Models\Wedding;
use App\Services\Site\SiteContentSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Integration tests for site permissions.
 * 
 * @Requirements: 1.1-1.5
 */
class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function admin_can_access_any_wedding_site(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $admin = User::factory()->admin()->create();
        $admin->current_wedding_id = $wedding->id;
        $admin->save();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'admin-access-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        Sanctum::actingAs($admin);

        // Act
        $response = $this->getJson("/api/sites/{$site->id}", [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function admin_can_create_site_for_any_wedding(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $admin = User::factory()->admin()->create();
        $admin->current_wedding_id = $wedding->id;
        $admin->save();

        Sanctum::actingAs($admin);

        // Act
        $response = $this->postJson('/api/sites', [], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(201);
    }

    /**
     * @test
     */
    public function admin_can_publish_any_site(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $admin = User::factory()->admin()->create();
        $admin->current_wedding_id = $wedding->id;
        $admin->save();

        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Site de Casamento';

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'admin-publish-test',
            'draft_content' => $content,
        ]);

        Sanctum::actingAs($admin);

        // Act
        $response = $this->postJson("/api/sites/{$site->id}/publish", [], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function couple_can_access_their_wedding_site(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding->id;
        $couple->save();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'couple-access-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        Sanctum::actingAs($couple);

        // Act
        $response = $this->getJson("/api/sites/{$site->id}", [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function couple_can_create_site_for_their_wedding(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding->id;
        $couple->save();

        Sanctum::actingAs($couple);

        // Act
        $response = $this->postJson('/api/sites', [], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(201);
    }

    /**
     * @test
     */
    public function couple_can_publish_their_site(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding->id;
        $couple->save();

        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Site de Casamento';

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'couple-publish-test',
            'draft_content' => $content,
        ]);

        Sanctum::actingAs($couple);

        // Act
        $response = $this->postJson("/api/sites/{$site->id}/publish", [], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function couple_cannot_access_another_wedding_site(): void
    {
        // Arrange
        $wedding1 = Wedding::factory()->create();
        $wedding2 = Wedding::factory()->create();
        
        $couple = User::factory()->create();
        $wedding1->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding1->id;
        $couple->save();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding2->id,
            'slug' => 'other-wedding-site',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        Sanctum::actingAs($couple);

        // Act
        $response = $this->getJson("/api/sites/{$site->id}", [
            'X-Wedding-ID' => $wedding2->id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function organizer_with_sites_permission_can_view_site(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $organizer = User::factory()->create();
        $wedding->users()->attach($organizer->id, [
            'role' => 'organizer',
            'permissions' => json_encode(['sites']),
        ]);
        $organizer->current_wedding_id = $wedding->id;
        $organizer->save();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'organizer-view-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        Sanctum::actingAs($organizer);

        // Act
        $response = $this->getJson("/api/sites/{$site->id}", [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function organizer_with_sites_permission_can_update_draft(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $organizer = User::factory()->create();
        $wedding->users()->attach($organizer->id, [
            'role' => 'organizer',
            'permissions' => json_encode(['sites']),
        ]);
        $organizer->current_wedding_id = $wedding->id;
        $organizer->save();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'organizer-update-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        Sanctum::actingAs($organizer);

        $newContent = $site->draft_content;
        $newContent['sections']['header']['title'] = 'Updated by Organizer';

        // Act
        $response = $this->putJson("/api/sites/{$site->id}/draft", [
            'content' => $newContent,
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function organizer_with_sites_permission_cannot_publish(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $organizer = User::factory()->create();
        $wedding->users()->attach($organizer->id, [
            'role' => 'organizer',
            'permissions' => json_encode(['sites']),
        ]);
        $organizer->current_wedding_id = $wedding->id;
        $organizer->save();

        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Site de Casamento';

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'organizer-publish-test',
            'draft_content' => $content,
        ]);

        Sanctum::actingAs($organizer);

        // Act
        $response = $this->postJson("/api/sites/{$site->id}/publish", [], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function organizer_without_sites_permission_cannot_access(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $organizer = User::factory()->create();
        $wedding->users()->attach($organizer->id, [
            'role' => 'organizer',
            'permissions' => json_encode(['tasks']), // No 'sites' permission
        ]);
        $organizer->current_wedding_id = $wedding->id;
        $organizer->save();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'organizer-no-permission-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        Sanctum::actingAs($organizer);

        // Act
        $response = $this->getJson("/api/sites/{$site->id}", [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function guest_cannot_access_site_api(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $guest = User::factory()->create();
        $wedding->users()->attach($guest->id, ['role' => 'guest']);
        $guest->current_wedding_id = $wedding->id;
        $guest->save();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'guest-access-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        Sanctum::actingAs($guest);

        // Act
        $response = $this->getJson("/api/sites/{$site->id}", [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function guest_cannot_create_site(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $guest = User::factory()->create();
        $wedding->users()->attach($guest->id, ['role' => 'guest']);
        $guest->current_wedding_id = $wedding->id;
        $guest->save();

        Sanctum::actingAs($guest);

        // Act
        $response = $this->postJson('/api/sites', [], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_api(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'unauth-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        // Act (no authentication)
        $response = $this->getJson("/api/sites/{$site->id}", [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function user_without_wedding_context_cannot_access(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        // User is not attached to any wedding

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'no-context-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson("/api/sites/{$site->id}");

        // Assert
        $response->assertStatus(401);
    }
}
