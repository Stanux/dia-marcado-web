<?php

declare(strict_types=1);

namespace App\Contracts\Site;

use App\Models\SiteLayout;
use App\Services\Site\QAResult;
use App\Services\Site\ValidationResult;

/**
 * Interface for site validation service.
 * 
 * Provides methods for validating site content before publication,
 * checking accessibility compliance, and running QA checklists.
 */
interface SiteValidatorServiceInterface
{
    /**
     * Validate a site layout for publication.
     * 
     * Checks required fields, enabled sections, and content validity.
     *
     * @param SiteLayout $site The site layout to validate
     * @return ValidationResult The validation result with errors and warnings
     */
    public function validateForPublish(SiteLayout $site): ValidationResult;

    /**
     * Validate a specific section's content.
     * 
     * Checks section-specific required fields and content rules.
     *
     * @param string $section The section name (header, hero, etc.)
     * @param array $content The section content to validate
     * @return ValidationResult The validation result for the section
     */
    public function validateSection(string $section, array $content): ValidationResult;

    /**
     * Check content for accessibility issues.
     * 
     * Verifies WCAG compliance including color contrast and alt text.
     *
     * @param array $content The site content to check
     * @return array Array of accessibility warnings with suggestions
     */
    public function checkAccessibility(array $content): array;

    /**
     * Run the complete QA checklist on a site.
     * 
     * Performs comprehensive quality checks including:
     * - Images with alt text
     * - Valid links (HTTP/HTTPS)
     * - Required fields filled
     * - WCAG AA contrast compliance
     * - Resource size within threshold
     *
     * @param SiteLayout $site The site layout to check
     * @return QAResult The QA result with all check statuses
     */
    public function runQAChecklist(SiteLayout $site): QAResult;
}
