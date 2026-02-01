<?php

namespace App\Services\Site;

use App\Contracts\Site\AccessTokenServiceInterface;
use App\Models\SiteLayout;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Service for managing site access tokens (password protection).
 * 
 * Handles token hashing, verification, and rate limiting
 * for public site access.
 */
class AccessTokenService implements AccessTokenServiceInterface
{
    /**
     * Cache key prefix for rate limiting.
     */
    private const RATE_LIMIT_PREFIX = 'site_auth_attempts:';

    /**
     * Default rate limit attempts.
     */
    private const DEFAULT_RATE_LIMIT_ATTEMPTS = 5;

    /**
     * Default rate limit minutes.
     */
    private const DEFAULT_RATE_LIMIT_MINUTES = 15;

    /**
     * Set an access token (password) for a site.
     * The token will be hashed before storage.
     *
     * @param SiteLayout $site The site to protect
     * @param string $token The plain text token/password
     * @return void
     */
    public function setToken(SiteLayout $site, string $token): void
    {
        $site->access_token = Hash::make($token);
        $site->save();
    }

    /**
     * Remove the access token from a site, making it public.
     *
     * @param SiteLayout $site The site to make public
     * @return void
     */
    public function removeToken(SiteLayout $site): void
    {
        $site->access_token = null;
        $site->save();
    }

    /**
     * Verify if a provided token matches the site's access token.
     * Returns true if site has no token (public) or token matches.
     * Records failed attempts for rate limiting.
     *
     * @param SiteLayout $site The site to verify access for
     * @param string $token The token to verify
     * @return bool True if access is granted, false otherwise
     */
    public function verify(SiteLayout $site, string $token): bool
    {
        // If site has no access token, it's public
        if ($site->access_token === null) {
            return true;
        }

        // Verify the token against the stored hash
        $isValid = Hash::check($token, $site->access_token);

        return $isValid;
    }

    /**
     * Check if an identifier (IP address) is rate limited.
     *
     * @param string $identifier The identifier to check (typically IP address)
     * @return bool True if rate limited, false otherwise
     */
    public function isRateLimited(string $identifier): bool
    {
        $cacheKey = self::RATE_LIMIT_PREFIX . $identifier;
        $attempts = Cache::get($cacheKey, 0);
        $maxAttempts = $this->getMaxAttempts();

        return $attempts >= $maxAttempts;
    }

    /**
     * Record a failed authentication attempt for rate limiting.
     *
     * @param string $identifier The identifier to record (typically IP address)
     * @return void
     */
    public function recordFailedAttempt(string $identifier): void
    {
        $cacheKey = self::RATE_LIMIT_PREFIX . $identifier;
        $ttlSeconds = $this->getRateLimitMinutes() * 60;

        $attempts = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $attempts + 1, $ttlSeconds);
    }

    /**
     * Get the maximum number of attempts before rate limiting.
     *
     * @return int The maximum attempts
     */
    private function getMaxAttempts(): int
    {
        return SystemConfig::get('site.rate_limit_attempts', self::DEFAULT_RATE_LIMIT_ATTEMPTS);
    }

    /**
     * Get the rate limit duration in minutes.
     *
     * @return int The rate limit minutes
     */
    private function getRateLimitMinutes(): int
    {
        return SystemConfig::get('site.rate_limit_minutes', self::DEFAULT_RATE_LIMIT_MINUTES);
    }
}
