<?php

namespace App\Contracts\Site;

use App\Models\SiteLayout;

/**
 * Interface for site access token management service.
 * 
 * Handles password protection for public site access,
 * including token hashing, verification, and rate limiting.
 */
interface AccessTokenServiceInterface
{
    /**
     * Set an access token (password) for a site.
     * The token will be hashed before storage.
     *
     * @param SiteLayout $site The site to protect
     * @param string $token The plain text token/password
     * @return void
     */
    public function setToken(SiteLayout $site, string $token): void;

    /**
     * Remove the access token from a site, making it public.
     *
     * @param SiteLayout $site The site to make public
     * @return void
     */
    public function removeToken(SiteLayout $site): void;

    /**
     * Verify if a provided token matches the site's access token.
     * Returns true if site has no token (public) or token matches.
     * Records failed attempts for rate limiting.
     *
     * @param SiteLayout $site The site to verify access for
     * @param string $token The token to verify
     * @return bool True if access is granted, false otherwise
     */
    public function verify(SiteLayout $site, string $token): bool;

    /**
     * Check if an identifier (IP address) is rate limited.
     *
     * @param string $identifier The identifier to check (typically IP address)
     * @return bool True if rate limited, false otherwise
     */
    public function isRateLimited(string $identifier): bool;

    /**
     * Record a failed authentication attempt for rate limiting.
     *
     * @param string $identifier The identifier to record (typically IP address)
     * @return void
     */
    public function recordFailedAttempt(string $identifier): void;
}
