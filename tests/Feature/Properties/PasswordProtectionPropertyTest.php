<?php

namespace Tests\Feature\Properties;

use App\Models\SiteLayout;
use App\Models\Wedding;
use App\Services\Site\AccessTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Feature: wedding-site-builder, Property 13: Proteção por Senha
 * 
 * For any SiteLayout with a non-null access_token, public access without 
 * providing the correct token SHALL be denied. Access with the correct 
 * token SHALL be granted.
 * 
 * Validates: Requirements 5.5, 6.2
 */
class PasswordProtectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    private AccessTokenService $accessTokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accessTokenService = new AccessTokenService();
    }

    /**
     * Property test: Sites with access token deny access without correct token
     * @test
     */
    public function sites_with_access_token_deny_access_without_correct_token(): void
    {
        for ($i = 0; $i < 100; $i++) {
            Cache::flush();
            
            $wedding = Wedding::factory()->create();
            $site = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => 'test-site-' . $i,
                'draft_content' => ['version' => '1.0', 'sections' => []],
            ]);

            // Generate random password
            $password = Str::random(rand(4, 20));
            
            // Set the token
            $this->accessTokenService->setToken($site, $password);
            $site->refresh();

            // Verify that access_token is now set (hashed)
            $this->assertNotNull($site->access_token, "Site should have access_token set");

            // Generate a wrong password
            $wrongPassword = $password . '_wrong';

            // Verify that wrong password is denied
            $result = $this->accessTokenService->verify($site, $wrongPassword);
            $this->assertFalse($result, "Access with wrong password should be denied");
        }
    }

    /**
     * Property test: Sites with access token grant access with correct token
     * @test
     */
    public function sites_with_access_token_grant_access_with_correct_token(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $site = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => 'test-site-correct-' . $i,
                'draft_content' => ['version' => '1.0', 'sections' => []],
            ]);

            // Generate random password
            $password = Str::random(rand(4, 20));
            
            // Set the token
            $this->accessTokenService->setToken($site, $password);
            $site->refresh();

            // Verify that correct password is accepted
            $result = $this->accessTokenService->verify($site, $password);
            $this->assertTrue($result, "Access with correct password should be granted");
        }
    }

    /**
     * Property test: Sites without access token are public
     * @test
     */
    public function sites_without_access_token_are_public(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $site = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => 'public-site-' . $i,
                'draft_content' => ['version' => '1.0', 'sections' => []],
                'access_token' => null,
            ]);

            // Any token should work for public sites
            $randomToken = Str::random(rand(1, 50));
            $result = $this->accessTokenService->verify($site, $randomToken);
            
            $this->assertTrue($result, "Public sites (no access_token) should grant access");
        }
    }

    /**
     * @test
     */
    public function remove_token_makes_site_public(): void
    {
        $wedding = Wedding::factory()->create();
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'remove-token-test',
            'draft_content' => ['version' => '1.0', 'sections' => []],
        ]);

        // Set a token
        $password = 'secret123';
        $this->accessTokenService->setToken($site, $password);
        $site->refresh();

        // Verify token is set
        $this->assertNotNull($site->access_token);
        $this->assertFalse($this->accessTokenService->verify($site, 'wrong'));

        // Remove the token
        $this->accessTokenService->removeToken($site);
        $site->refresh();

        // Verify site is now public
        $this->assertNull($site->access_token);
        $this->assertTrue($this->accessTokenService->verify($site, 'anything'));
    }

    /**
     * @test
     */
    public function rate_limiting_blocks_after_max_attempts(): void
    {
        Cache::flush();
        
        $identifier = '192.168.1.100';
        
        // Initially not rate limited
        $this->assertFalse($this->accessTokenService->isRateLimited($identifier));

        // Record 5 failed attempts (default max)
        for ($i = 0; $i < 5; $i++) {
            $this->accessTokenService->recordFailedAttempt($identifier);
        }

        // Should now be rate limited
        $this->assertTrue($this->accessTokenService->isRateLimited($identifier));
    }

    /**
     * @test
     */
    public function rate_limiting_allows_before_max_attempts(): void
    {
        Cache::flush();
        
        $identifier = '192.168.1.101';
        
        // Record 4 failed attempts (one less than default max of 5)
        for ($i = 0; $i < 4; $i++) {
            $this->accessTokenService->recordFailedAttempt($identifier);
        }

        // Should not be rate limited yet
        $this->assertFalse($this->accessTokenService->isRateLimited($identifier));
    }

    /**
     * @test
     */
    public function different_identifiers_have_separate_rate_limits(): void
    {
        Cache::flush();
        
        $identifier1 = '192.168.1.200';
        $identifier2 = '192.168.1.201';

        // Rate limit first identifier
        for ($i = 0; $i < 5; $i++) {
            $this->accessTokenService->recordFailedAttempt($identifier1);
        }

        // First should be rate limited
        $this->assertTrue($this->accessTokenService->isRateLimited($identifier1));
        
        // Second should not be rate limited
        $this->assertFalse($this->accessTokenService->isRateLimited($identifier2));
    }
}
