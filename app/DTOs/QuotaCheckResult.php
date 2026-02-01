<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object representing the result of a quota check.
 * 
 * Used to determine if a user can upload files based on their plan limits,
 * providing a reason if upload is blocked and an upgrade message for
 * basic plan users who have reached their quota.
 * 
 * @see Requirements 4.3, 4.4, 5.4
 */
readonly class QuotaCheckResult
{
    /**
     * Create a new QuotaCheckResult instance.
     *
     * @param bool $canUpload Whether the upload is allowed
     * @param string|null $reason Reason why upload is blocked (null if allowed)
     * @param string|null $upgradeMessage Message suggesting plan upgrade (for basic plan users at limit)
     */
    public function __construct(
        public bool $canUpload,
        public ?string $reason = null,
        public ?string $upgradeMessage = null,
    ) {}
}
