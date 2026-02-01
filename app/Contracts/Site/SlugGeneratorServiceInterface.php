<?php

namespace App\Contracts\Site;

use App\Models\Wedding;

/**
 * Interface for slug generation service.
 */
interface SlugGeneratorServiceInterface
{
    /**
     * Generate a slug based on the wedding's couple members.
     *
     * @param Wedding $wedding The wedding to generate slug for
     * @return string The generated slug
     */
    public function generate(Wedding $wedding): string;

    /**
     * Ensure the slug is unique in the database.
     * If the slug already exists, append a numeric suffix.
     *
     * @param string $slug The base slug to check
     * @return string A unique slug
     */
    public function ensureUnique(string $slug): string;

    /**
     * Normalize text for use in a slug.
     * Converts to lowercase, removes accents, replaces special chars with hyphens.
     *
     * @param string $text The text to normalize
     * @return string The normalized slug-safe text
     */
    public function normalize(string $text): string;
}
