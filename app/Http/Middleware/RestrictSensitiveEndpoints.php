<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictSensitiveEndpoints
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = trim($request->path(), '/');

        if ($this->isIgnitionPath($path) && ! $this->canAccessIgnition()) {
            abort(404);
        }

        if ($this->isMonitoringPath($path) && ! $this->canAccessMonitoring($request)) {
            abort(404);
        }

        return $next($request);
    }

    protected function isIgnitionPath(string $path): bool
    {
        return str_starts_with($path, '_ignition');
    }

    protected function canAccessIgnition(): bool
    {
        return (bool) config('security.debug.enable_ignition_routes', false)
            && app()->environment(['local', 'development'])
            && (bool) config('app.debug');
    }

    protected function isMonitoringPath(string $path): bool
    {
        return in_array($path, ['health', 'ready', 'up'], true);
    }

    protected function canAccessMonitoring(Request $request): bool
    {
        if ((bool) config('security.monitoring.expose_publicly', false)) {
            return true;
        }

        if (app()->environment(['local', 'development'])) {
            return true;
        }

        $token = (string) config('security.monitoring.token', '');
        $tokenHeader = (string) config('security.monitoring.token_header', 'X-Health-Token');

        if ($token !== '' && hash_equals($token, (string) $request->header($tokenHeader))) {
            return true;
        }

        $allowedIps = config('security.monitoring.allowed_ips', []);
        $ipAddress = $request->ip();

        return is_array($allowedIps)
            && is_string($ipAddress)
            && in_array($ipAddress, $allowedIps, true);
    }
}
