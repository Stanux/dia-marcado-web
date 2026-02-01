<?php

namespace Tests\Feature\Properties;

use App\Http\Middleware\EnsureOnboardingComplete;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Tests\TestCase;

/**
 * Feature: user-onboarding, Property 1: Controle de Acesso Baseado em Status de Onboarding
 * 
 * For any non-admin user:
 * - If the user has NOT completed onboarding, any attempt to access system pages 
 *   (except the onboarding page) SHALL result in a redirect to the onboarding page.
 * - If the user HAS completed onboarding, they SHALL have access to the dashboard.
 * - Admin users are exempt from onboarding requirements.
 * 
 * Validates: Requirements 1.1, 1.2, 1.4
 */
class OnboardingAccessControlPropertyTest extends TestCase
{
    use RefreshDatabase;

    private EnsureOnboardingComplete $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureOnboardingComplete();
        
        // Register test routes for the middleware tests
        $this->registerTestRoutes();
    }

    /**
     * Register test routes needed for middleware testing.
     */
    private function registerTestRoutes(): void
    {
        $router = app(Router::class);
        
        // Register onboarding route if not exists
        if (!$router->has('filament.admin.pages.onboarding')) {
            $router->get('/admin/onboarding', fn () => 'Onboarding')
                ->name('filament.admin.pages.onboarding');
        }
        
        // Register dashboard route if not exists
        if (!$router->has('filament.admin.pages.dashboard')) {
            $router->get('/admin', fn () => 'Dashboard')
                ->name('filament.admin.pages.dashboard');
        }
    }

    /**
     * Helper to create a mock request with a user and route.
     */
    private function createRequest(User $user, string $routeName): Request
    {
        $uri = $routeName === 'filament.admin.pages.onboarding' 
            ? '/admin/onboarding' 
            : '/admin/dashboard';
            
        $request = Request::create($uri, 'GET');
        $request->setUserResolver(fn () => $user);
        
        // Create a mock route
        $route = new Route(['GET'], $uri, []);
        $route->name($routeName);
        $request->setRouteResolver(fn () => $route);
        
        return $request;
    }

    /**
     * Property test: Users who haven't completed onboarding are redirected
     * 
     * For any non-admin user who has not completed onboarding,
     * accessing any non-onboarding route should redirect to onboarding.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function users_without_completed_onboarding_are_redirected(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingPending()->create();
            
            $request = $this->createRequest($user, 'filament.admin.pages.dashboard');
            
            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK');
            });
            
            $this->assertTrue(
                $response->isRedirect(),
                "User without completed onboarding should be redirected (iteration $i)"
            );
            
            $this->assertStringContainsString(
                'onboarding',
                $response->headers->get('Location') ?? '',
                "Redirect should be to onboarding page (iteration $i)"
            );
        }
    }

    /**
     * Property test: Users who completed onboarding can access dashboard
     * 
     * For any non-admin user who has completed onboarding,
     * accessing the dashboard should be allowed.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function users_with_completed_onboarding_can_access_dashboard(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            
            $request = $this->createRequest($user, 'filament.admin.pages.dashboard');
            
            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK');
            });
            
            $this->assertFalse(
                $response->isRedirect(),
                "User with completed onboarding should not be redirected (iteration $i)"
            );
            
            $this->assertEquals(
                'OK',
                $response->getContent(),
                "User with completed onboarding should access the page (iteration $i)"
            );
        }
    }

    /**
     * Property test: Admin users are exempt from onboarding
     * 
     * For any admin user, regardless of onboarding status,
     * they should be able to access any page.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_users_are_exempt_from_onboarding(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Test admin with onboarding not completed
            $adminNotCompleted = User::factory()->admin()->onboardingPending()->create();
            
            $request = $this->createRequest($adminNotCompleted, 'filament.admin.pages.dashboard');
            
            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK');
            });
            
            $this->assertFalse(
                $response->isRedirect(),
                "Admin without completed onboarding should not be redirected (iteration $i)"
            );
            
            // Test admin with onboarding completed
            $adminCompleted = User::factory()->admin()->onboardingCompleted()->create();
            
            $request2 = $this->createRequest($adminCompleted, 'filament.admin.pages.dashboard');
            
            $response2 = $this->middleware->handle($request2, function ($req) {
                return new Response('OK');
            });
            
            $this->assertFalse(
                $response2->isRedirect(),
                "Admin with completed onboarding should not be redirected (iteration $i)"
            );
        }
    }

    /**
     * Property test: Users without completed onboarding can access onboarding page
     * 
     * For any non-admin user who has not completed onboarding,
     * they should be able to access the onboarding page.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function users_without_onboarding_can_access_onboarding_page(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingPending()->create();
            
            $request = $this->createRequest($user, 'filament.admin.pages.onboarding');
            
            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK');
            });
            
            $this->assertFalse(
                $response->isRedirect(),
                "User without completed onboarding should access onboarding page (iteration $i)"
            );
            
            $this->assertEquals(
                'OK',
                $response->getContent(),
                "User without completed onboarding should access onboarding page content (iteration $i)"
            );
        }
    }

    /**
     * Property test: Users with completed onboarding are redirected away from onboarding page
     * 
     * For any non-admin user who has completed onboarding,
     * accessing the onboarding page should redirect to dashboard.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function users_with_completed_onboarding_are_redirected_from_onboarding_page(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            
            $request = $this->createRequest($user, 'filament.admin.pages.onboarding');
            
            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK');
            });
            
            $this->assertTrue(
                $response->isRedirect(),
                "User with completed onboarding should be redirected from onboarding page (iteration $i)"
            );
        }
    }
}
