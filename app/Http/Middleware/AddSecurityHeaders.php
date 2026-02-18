<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! (bool) config('security.headers.enabled', true)) {
            return $response;
        }

        $this->setHeaderIfMissing(
            $response,
            'X-Frame-Options',
            (string) config('security.headers.x_frame_options', 'SAMEORIGIN')
        );
        $this->setHeaderIfMissing($response, 'X-Content-Type-Options', 'nosniff');
        $this->setHeaderIfMissing(
            $response,
            'Referrer-Policy',
            (string) config('security.headers.referrer_policy', 'strict-origin-when-cross-origin')
        );
        $this->setHeaderIfMissing(
            $response,
            'Permissions-Policy',
            (string) config('security.headers.permissions_policy', 'camera=(), microphone=(), geolocation=()')
        );

        $contentSecurityPolicy = (string) config('security.headers.content_security_policy', '');

        if ($contentSecurityPolicy !== '') {
            $this->setHeaderIfMissing($response, 'Content-Security-Policy', $contentSecurityPolicy);
        }

        if ($this->shouldSetHsts($request)) {
            $this->setHeaderIfMissing($response, 'Strict-Transport-Security', $this->buildHstsValue());
        }

        return $response;
    }

    protected function shouldSetHsts(Request $request): bool
    {
        if (! (bool) config('security.headers.hsts.enabled', false)) {
            return false;
        }

        return $request->isSecure()
            || strtolower((string) $request->header('X-Forwarded-Proto')) === 'https';
    }

    protected function buildHstsValue(): string
    {
        $maxAge = (int) config('security.headers.hsts.max_age', 31536000);
        $includeSubdomains = (bool) config('security.headers.hsts.include_subdomains', true);
        $preload = (bool) config('security.headers.hsts.preload', false);

        $hsts = "max-age={$maxAge}";

        if ($includeSubdomains) {
            $hsts .= '; includeSubDomains';
        }

        if ($preload) {
            $hsts .= '; preload';
        }

        return $hsts;
    }

    protected function setHeaderIfMissing(Response $response, string $name, string $value): void
    {
        if ($value === '' || $response->headers->has($name)) {
            return;
        }

        $response->headers->set($name, $value);
    }
}
