<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

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
            'ziggy' => fn () => [
                ...(new \Tighten\Ziggy\Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'appEnv' => app()->environment(),
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
