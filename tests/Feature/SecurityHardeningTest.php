<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    #[Test]
    public function guest_inertia_payload_does_not_expose_internal_route_inventory_or_app_env(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringNotContainsString('"appEnv"', $content);
        $this->assertStringNotContainsString('filament.admin.resources.users.index', $content);
        $this->assertStringContainsString('public.site.show', $content);
    }

    #[Test]
    public function monitoring_endpoints_require_token_when_not_public(): void
    {
        Config::set('security.monitoring.expose_publicly', false);
        Config::set('security.monitoring.token', 'test-monitor-token');
        Config::set('security.monitoring.token_header', 'X-Health-Token');

        $this->get('/health')->assertNotFound();
        $this->get('/ready')->assertNotFound();
        $this->get('/up')->assertNotFound();

        $this->withHeader('X-Health-Token', 'test-monitor-token')
            ->get('/health')
            ->assertOk();

        $readyResponse = $this->withHeader('X-Health-Token', 'test-monitor-token')
            ->get('/ready');

        $this->assertContains($readyResponse->getStatusCode(), [200, 503]);

        $this->withHeader('X-Health-Token', 'test-monitor-token')
            ->get('/up')
            ->assertOk();
    }

    #[Test]
    public function ignition_routes_are_blocked_when_security_flag_is_disabled(): void
    {
        Config::set('security.debug.enable_ignition_routes', false);

        $this->get('/_ignition/health-check')->assertNotFound();
    }

    #[Test]
    public function fallback_filament_login_post_route_is_rate_limited(): void
    {
        $route = app('router')->getRoutes()->getByName('filament.admin.auth.login.post');

        $this->assertNotNull($route);
        $this->assertContains('throttle:filament-login', $route->gatherMiddleware());
    }

    #[Test]
    public function security_headers_are_added_to_application_responses(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
