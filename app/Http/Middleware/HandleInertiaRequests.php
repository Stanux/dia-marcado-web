<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'weddingId' => $weddingId,
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'ziggy' => fn () => $this->buildZiggyPayload($request, $user),
        ];
    }

    /**
     * Build the Ziggy payload and avoid leaking private route names to guests.
     *
     * @param  mixed  $user
     * @return array<string, mixed>
     */
    protected function buildZiggyPayload(Request $request, mixed $user): array
    {
        $ziggy = (new Ziggy())->toArray();

        if (! $user) {
            $ziggy['routes'] = collect($ziggy['routes'] ?? [])
                ->only($this->publicRouteNames())
                ->all();
        }

        $ziggy['location'] = $request->url();

        return $ziggy;
    }

    /**
     * @return array<int, string>
     */
    protected function publicRouteNames(): array
    {
        return [
            'health',
            'ready',
            'login',
            'filament.admin.auth.login',
            'filament.admin.auth.login.post',
            'filament.admin.auth.simple-login',
            'auth.google.redirect',
            'auth.google.callback',
            'convite.show',
            'convite.accept.new',
            'convite.accept.existing',
            'convite.decline',
            'public.site.show',
            'public.site.authenticate',
            'public.site.calendar',
        ];
    }

    /**
     * Handle unauthenticated requests.
     */
    public function onUnauthenticated(Request $request): void
    {
        // For Inertia requests, redirect to login
        if ($request->header('X-Inertia')) {
            $request->session()->put('url.intended', $request->url());
        }
    }
}
