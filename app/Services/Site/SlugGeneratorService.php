<?php

namespace App\Services\Site;

use App\Contracts\Site\SlugGeneratorServiceInterface;
use App\Models\SiteLayout;
use App\Models\Wedding;
use Illuminate\Support\Str;

/**
 * Service for generating unique slugs for wedding sites.
 */
class SlugGeneratorService implements SlugGeneratorServiceInterface
{
    /**
     * Maximum length for generated slugs.
     */
    private const MAX_SLUG_LENGTH = 100;

    /**
     * Generate a slug based on the wedding's couple members.
     *
     * @param Wedding $wedding The wedding to generate slug for
     * @return string The generated unique slug
     */
    public function generate(Wedding $wedding): string
    {
        $coupleMembers = $wedding->couple()->get();
        $names = $coupleMembers->pluck('name')->filter()->values();

        $baseSlug = $this->buildSlugFromNames($names->toArray());
        
        return $this->ensureUnique($baseSlug);
    }

    /**
     * Build a slug from an array of names.
     *
     * @param array $names Array of couple member names
     * @return string The base slug
     */
    private function buildSlugFromNames(array $names): string
    {
        $count = count($names);

        if ($count === 0) {
            return $this->normalize('casamento-' . Str::random(6));
        }

        if ($count === 1) {
            $firstName = $this->extractFirstName($names[0]);
            return $this->normalize("casamento-{$firstName}");
        }

        if ($count === 2) {
            $firstName1 = $this->extractFirstName($names[0]);
            $firstName2 = $this->extractFirstName($names[1]);
            return $this->normalize("{$firstName1}-e-{$firstName2}");
        }

        // 3+ people
        $firstName1 = $this->extractFirstName($names[0]);
        $firstName2 = $this->extractFirstName($names[1]);
        return $this->normalize("{$firstName1}-{$firstName2}-e-outros");
    }

    /**
     * Extract the first name from a full name.
     *
     * @param string $fullName The full name
     * @return string The first name
     */
    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? $fullName;
    }

    /**
     * Ensure the slug is unique in the database.
     *
     * @param string $slug The base slug to check
     * @return string A unique slug
     */
    public function ensureUnique(string $slug): string
    {
        $originalSlug = $slug;
        $counter = 2;

        while ($this->slugExists($slug)) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists in the database.
     *
     * @param string $slug The slug to check
     * @return bool True if exists, false otherwise
     */
    private function slugExists(string $slug): bool
    {
        return SiteLayout::withoutGlobalScopes()
            ->where('slug', $slug)
            ->exists();
    }

    /**
     * Normalize text for use in a slug.
     *
     * @param string $text The text to normalize
     * @return string The normalized slug-safe text
     */
    public function normalize(string $text): string
    {
        // Convert to lowercase
        $slug = Str::lower($text);

        // Remove accents using Laravel's ascii helper
        $slug = Str::ascii($slug);

        // Replace spaces and special characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        // Remove duplicate hyphens
        $slug = preg_replace('/-+/', '-', $slug);

        // Trim hyphens from start and end
        $slug = trim($slug, '-');

        // Limit length
        if (strlen($slug) > self::MAX_SLUG_LENGTH) {
            $slug = substr($slug, 0, self::MAX_SLUG_LENGTH);
            // Remove trailing hyphen if we cut in the middle of a word
            $slug = rtrim($slug, '-');
        }

        return $slug;
    }
}
