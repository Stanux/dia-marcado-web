<?php

namespace Tests\Feature\Property;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Models\Wedding;
use App\Services\AdminAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: user-role-management, Property 12: Log de Ações de Admin em Casamentos de Terceiros
 * 
 * For any admin action on a wedding that is not theirs, the system must create
 * an audit record with admin_id, wedding_id, action, and timestamp.
 * 
 * Validates: Requirements 5.4, 5.5
 */
class AdminAuditTest extends TestCase
{
    use RefreshDatabase;

    protected AdminAuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = new AdminAuditService();
    }

    /**
     * Property 12: Admin actions on third-party weddings are logged
     * @test
     */
    public function admin_actions_on_third_party_weddings_are_logged(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->create(['role' => 'admin']);
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);

            // Admin is NOT couple in this wedding
            $action = fake()->randomElement(['view', 'edit', 'delete', 'create_user']);
            $details = ['test_iteration' => $i];

            $log = $this->auditService->logAction($admin, $wedding, $action, $details);

            $this->assertNotNull($log, "Iteration {$i}: Log should be created");
            $this->assertEquals($admin->id, $log->admin_id);
            $this->assertEquals($wedding->id, $log->wedding_id);
            $this->assertEquals($action, $log->action);
            $this->assertEquals($details, $log->details);
            $this->assertNotNull($log->performed_at);

            // Cleanup
            $log->delete();
            $admin->delete();
            $wedding->delete();
        }
    }

    /**
     * Property 12: Admin actions on own wedding are NOT logged
     * @test
     */
    public function admin_actions_on_own_wedding_are_not_logged(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->create(['role' => 'admin']);
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);

            // Admin IS couple in this wedding
            $admin->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            $action = fake()->randomElement(['view', 'edit', 'delete', 'create_user']);

            $log = $this->auditService->logAction($admin, $wedding, $action);

            $this->assertNull($log, "Iteration {$i}: Log should NOT be created for own wedding");

            // Cleanup
            $admin->weddings()->detach();
            $admin->delete();
            $wedding->delete();
        }
    }

    /**
     * Property 12: Non-admin actions are NOT logged
     * @test
     */
    public function non_admin_actions_are_not_logged(): void
    {
        $roles = ['couple', 'organizer', 'guest'];

        for ($i = 0; $i < 100; $i++) {
            $role = $roles[array_rand($roles)];
            $user = User::factory()->create(['role' => $role]);
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);

            $action = fake()->randomElement(['view', 'edit', 'delete', 'create_user']);

            $log = $this->auditService->logAction($user, $wedding, $action);

            $this->assertNull($log, "Iteration {$i}: Log should NOT be created for non-admin ({$role})");

            // Cleanup
            $user->delete();
            $wedding->delete();
        }
    }

    /**
     * Test: Log contains correct timestamp
     * @test
     */
    public function log_contains_correct_timestamp(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $wedding = Wedding::create(['title' => 'Test Wedding']);

        $beforeTime = now()->subSecond();
        $log = $this->auditService->logAction($admin, $wedding, 'test_action');
        $afterTime = now()->addSecond();

        $this->assertNotNull($log);
        $this->assertTrue(
            $log->performed_at->greaterThanOrEqualTo($beforeTime),
            'Log timestamp should be >= before time'
        );
        $this->assertTrue(
            $log->performed_at->lessThanOrEqualTo($afterTime),
            'Log timestamp should be <= after time'
        );
    }

    /**
     * Test: Can retrieve logs for wedding
     * @test
     */
    public function can_retrieve_logs_for_wedding(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $wedding = Wedding::create(['title' => 'Test Wedding']);

        // Create multiple logs
        for ($i = 0; $i < 5; $i++) {
            $this->auditService->logAction($admin, $wedding, "action_{$i}");
        }

        $logs = $this->auditService->getLogsForWedding($wedding);

        $this->assertCount(5, $logs);
        foreach ($logs as $log) {
            $this->assertEquals($wedding->id, $log->wedding_id);
        }
    }

    /**
     * Test: Can retrieve logs for admin
     * @test
     */
    public function can_retrieve_logs_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create logs for multiple weddings
        for ($i = 0; $i < 5; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $this->auditService->logAction($admin, $wedding, "action_{$i}");
        }

        $logs = $this->auditService->getLogsForAdmin($admin);

        $this->assertCount(5, $logs);
        foreach ($logs as $log) {
            $this->assertEquals($admin->id, $log->admin_id);
        }
    }
}
