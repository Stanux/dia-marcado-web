<?php

declare(strict_types=1);

namespace App\Contracts\Site;

use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\Wedding;
use App\Services\Site\ValidationResult;
use Illuminate\Http\UploadedFile;

/**
 * Interface for media upload service.
 * 
 * Provides methods to upload, validate, optimize, and manage media files
 * for wedding sites.
 */
interface MediaUploadServiceInterface
{
    /**
     * Upload a file for a site layout.
     * 
     * Validates the file, saves it to storage, scans for malware,
     * optimizes images, and creates a SiteMedia record.
     *
     * @param UploadedFile $file The uploaded file
     * @param SiteLayout $site The site layout to associate the media with
     * @return SiteMedia The created media record
     * @throws \App\Exceptions\MediaUploadException If upload fails
     */
    public function upload(UploadedFile $file, SiteLayout $site): SiteMedia;

    /**
     * Validate an uploaded file before processing.
     * 
     * Checks:
     * - Extension against allowed/blocked lists
     * - File size against maximum limit
     * - MIME type matches extension
     *
     * @param UploadedFile $file The file to validate
     * @return ValidationResult The validation result with any errors/warnings
     */
    public function validateFile(UploadedFile $file): ValidationResult;

    /**
     * Optimize an image file by generating variants.
     * 
     * Generates:
     * - WebP version
     * - 2x version (if original is large enough)
     * - Thumbnail (300x300)
     * - Compressed original (85% quality)
     *
     * @param string $path The path to the image file
     * @return array Array of variant paths ['webp' => '...', '2x' => '...', 'thumbnail' => '...']
     */
    public function optimizeImage(string $path): array;

    /**
     * Scan a file for malware.
     * 
     * Uses ClamAV if available, otherwise performs basic magic byte verification.
     *
     * @param string $path The path to the file to scan
     * @return bool True if file is safe, false if suspicious
     */
    public function scanForMalware(string $path): bool;

    /**
     * Get the total storage usage for a wedding.
     *
     * @param Wedding $wedding The wedding to check
     * @return int Total storage used in bytes
     */
    public function getStorageUsage(Wedding $wedding): int;

    /**
     * Delete a media file and its variants.
     * 
     * Removes the file from storage and deletes the database record.
     *
     * @param SiteMedia $media The media to delete
     * @return bool True if deletion was successful
     */
    public function delete(SiteMedia $media): bool;
}
