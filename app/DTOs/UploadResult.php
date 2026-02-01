<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\SiteMedia;
use Illuminate\Support\Str;

/**
 * Data Transfer Object representing the result of a single file upload.
 * 
 * Used to communicate the outcome of an individual file upload within
 * a batch, including success/failure status, the created media record
 * (if successful), and error details (if failed).
 * 
 * @see Requirements 1.4, 1.5
 */
readonly class UploadResult
{
    /**
     * Create a new UploadResult instance.
     *
     * @param string $mediaId Unique identifier for the media record
     * @param bool $success Whether the upload was successful
     * @param SiteMedia|null $media The created media record (null if upload failed)
     * @param string|null $error Error message describing the failure (null if successful)
     */
    public function __construct(
        public string $mediaId,
        public bool $success,
        public ?SiteMedia $media = null,
        public ?string $error = null,
    ) {}

    /**
     * Create a successful upload result.
     *
     * @param string $mediaId The ID of the created media record
     * @param SiteMedia $media The created media record
     * @return self
     */
    public static function success(string $mediaId, SiteMedia $media): self
    {
        return new self(
            mediaId: $mediaId,
            success: true,
            media: $media,
            error: null,
        );
    }

    /**
     * Create a failed upload result.
     *
     * @param string|null $mediaId The ID of the failed media record (if any)
     * @param string $error The error message describing the failure
     * @return self
     */
    public static function failure(?string $mediaId, string $error): self
    {
        return new self(
            mediaId: $mediaId ?? Str::uuid()->toString(),
            success: false,
            media: null,
            error: $error,
        );
    }
}
