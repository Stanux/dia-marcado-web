<?php

declare(strict_types=1);

namespace App\Contracts\Site;

/**
 * Interface for content sanitization service.
 * 
 * Provides methods to sanitize user-provided content to prevent XSS attacks
 * and other security vulnerabilities.
 */
interface ContentSanitizerServiceInterface
{
    /**
     * Sanitize a string by removing dangerous content.
     * 
     * Removes:
     * - Script tags and their content
     * - Event handler attributes (onclick, onerror, onload, etc.)
     * - JavaScript URLs (href="javascript:...")
     * 
     * @param string $content The content to sanitize
     * @return string The sanitized content
     */
    public function sanitize(string $content): string;

    /**
     * Sanitize rich text content allowing only safe HTML tags.
     * 
     * Allows only: b, strong, i, em, a, br, p, span
     * Allows attributes: href (http/https only), class, style (limited)
     * 
     * @param string $content The rich text content to sanitize
     * @return string The sanitized rich text
     */
    public function sanitizeRichText(string $content): string;

    /**
     * Recursively sanitize all string values in an array.
     * 
     * @param array $content The array to sanitize
     * @return array The sanitized array
     */
    public function sanitizeArray(array $content): array;
}
