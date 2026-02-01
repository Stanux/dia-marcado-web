<?php

declare(strict_types=1);

namespace App\Services\Site;

use App\Contracts\Site\ContentSanitizerServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * Service for sanitizing user-provided content to prevent XSS attacks.
 */
class ContentSanitizerService implements ContentSanitizerServiceInterface
{
    /**
     * Event handler attributes to remove.
     */
    private const EVENT_HANDLERS = [
        'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover',
        'onmousemove', 'onmouseout', 'onmouseenter', 'onmouseleave',
        'onkeydown', 'onkeypress', 'onkeyup',
        'onfocus', 'onblur', 'onchange', 'onsubmit', 'onreset', 'onselect',
        'onload', 'onunload', 'onerror', 'onabort', 'onresize', 'onscroll',
        'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover',
        'ondragstart', 'ondrop',
        'oncopy', 'oncut', 'onpaste',
        'onanimationstart', 'onanimationend', 'onanimationiteration',
        'ontransitionend',
        'oncontextmenu', 'oninput', 'oninvalid', 'onsearch', 'ontoggle',
        'onwheel', 'ontouchstart', 'ontouchmove', 'ontouchend', 'ontouchcancel',
        'onpointerdown', 'onpointerup', 'onpointermove', 'onpointerenter',
        'onpointerleave', 'onpointerover', 'onpointerout', 'onpointercancel',
        'onstart', 'onfinish', 'onbounce', // marquee events
        'onbeforeprint', 'onafterprint', 'onbeforeunload', 'onhashchange',
        'onmessage', 'onoffline', 'ononline', 'onpagehide', 'onpageshow',
        'onpopstate', 'onstorage',
    ];

    /**
     * Allowed tags for rich text content.
     */
    private const ALLOWED_TAGS = ['b', 'strong', 'i', 'em', 'a', 'br', 'p', 'span'];

    /**
     * Allowed attributes for rich text content.
     */
    private const ALLOWED_ATTRIBUTES = ['href', 'class', 'style'];

    /**
     * {@inheritdoc}
     */
    public function sanitize(string $content): string
    {
        $original = $content;
        
        // Remove script tags and their content (case-insensitive, handles multiline)
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
        
        // Remove script tags without closing tag
        $content = preg_replace('/<script\b[^>]*>/is', '', $content);
        
        // Remove event handler attributes
        foreach (self::EVENT_HANDLERS as $handler) {
            // Match attribute with double quotes
            $content = preg_replace('/\s*' . $handler . '\s*=\s*"[^"]*"/is', '', $content);
            // Match attribute with single quotes
            $content = preg_replace('/\s*' . $handler . '\s*=\s*\'[^\']*\'/is', '', $content);
            // Match attribute without quotes
            $content = preg_replace('/\s*' . $handler . '\s*=\s*[^\s>]*/is', '', $content);
        }
        
        // Remove javascript: URLs in href and src attributes (including quotes)
        $content = preg_replace('/\s*href\s*=\s*"javascript:[^"]*"/is', '', $content);
        $content = preg_replace('/\s*href\s*=\s*\'javascript:[^\']*\'/is', '', $content);
        $content = preg_replace('/\s*src\s*=\s*"javascript:[^"]*"/is', '', $content);
        $content = preg_replace('/\s*src\s*=\s*\'javascript:[^\']*\'/is', '', $content);
        
        // Remove data: URLs that could contain scripts (data:text/html is dangerous)
        $content = preg_replace('/\s*href\s*=\s*"data:[^"]*"/is', '', $content);
        $content = preg_replace('/\s*href\s*=\s*\'data:[^\']*\'/is', '', $content);
        $content = preg_replace('/\s*src\s*=\s*"data:text\/html[^"]*"/is', '', $content);
        $content = preg_replace('/\s*src\s*=\s*\'data:text\/html[^\']*\'/is', '', $content);
        
        // Remove vbscript: URLs
        $content = preg_replace('/\s*href\s*=\s*"vbscript:[^"]*"/is', '', $content);
        $content = preg_replace('/\s*href\s*=\s*\'vbscript:[^\']*\'/is', '', $content);
        
        // Log if content was modified (potential injection attempt)
        if ($content !== $original) {
            Log::warning('Content sanitization detected potential injection attempt', [
                'original_length' => strlen($original),
                'sanitized_length' => strlen($content),
            ]);
        }
        
        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function sanitizeRichText(string $content): string
    {
        // First apply basic sanitization
        $content = $this->sanitize($content);
        
        // Build allowed tags string for strip_tags
        $allowedTagsString = '<' . implode('><', self::ALLOWED_TAGS) . '>';
        
        // Strip all tags except allowed ones
        $content = strip_tags($content, $allowedTagsString);
        
        // Clean up attributes on remaining tags
        $content = $this->cleanAttributes($content);
        
        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function sanitizeArray(array $content): array
    {
        $result = [];
        
        foreach ($content as $key => $value) {
            if (is_string($value)) {
                $result[$key] = $this->sanitize($value);
            } elseif (is_array($value)) {
                $result[$key] = $this->sanitizeArray($value);
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }

    /**
     * Clean attributes on HTML tags, keeping only allowed ones.
     */
    private function cleanAttributes(string $content): string
    {
        // Match all HTML tags with attributes
        return preg_replace_callback(
            '/<(\w+)([^>]*)>/i',
            function ($matches) {
                $tag = strtolower($matches[1]);
                $attributes = $matches[2];
                
                if (!in_array($tag, self::ALLOWED_TAGS)) {
                    return $matches[0];
                }
                
                // Extract and filter attributes
                $cleanedAttributes = $this->filterAttributes($attributes, $tag);
                
                return '<' . $tag . $cleanedAttributes . '>';
            },
            $content
        );
    }

    /**
     * Filter attributes, keeping only allowed ones with safe values.
     */
    private function filterAttributes(string $attributes, string $tag): string
    {
        $result = [];
        
        // Match attributes with double quotes
        preg_match_all('/(\w+)\s*=\s*"([^"]*)"/i', $attributes, $doubleQuoted, PREG_SET_ORDER);
        // Match attributes with single quotes
        preg_match_all('/(\w+)\s*=\s*\'([^\']*)\'/i', $attributes, $singleQuoted, PREG_SET_ORDER);
        
        $allAttributes = array_merge($doubleQuoted, $singleQuoted);
        
        foreach ($allAttributes as $attr) {
            $name = strtolower($attr[1]);
            $value = $attr[2];
            
            if (!in_array($name, self::ALLOWED_ATTRIBUTES)) {
                continue;
            }
            
            // Special handling for href - only allow http/https
            if ($name === 'href') {
                $value = trim($value);
                if (!preg_match('/^https?:\/\//i', $value) && !preg_match('/^#/', $value) && !preg_match('/^mailto:/i', $value)) {
                    continue;
                }
                // Double-check no javascript in href
                if (preg_match('/javascript:/i', $value)) {
                    continue;
                }
            }
            
            // Special handling for style - limit to safe properties
            if ($name === 'style') {
                $value = $this->sanitizeStyleAttribute($value);
                if (empty($value)) {
                    continue;
                }
            }
            
            $result[] = $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
        
        return empty($result) ? '' : ' ' . implode(' ', $result);
    }

    /**
     * Sanitize style attribute, allowing only safe CSS properties.
     */
    private function sanitizeStyleAttribute(string $style): string
    {
        $allowedProperties = [
            'color', 'background-color', 'font-size', 'font-weight', 'font-style',
            'text-align', 'text-decoration', 'margin', 'padding', 'border',
            'margin-top', 'margin-bottom', 'margin-left', 'margin-right',
            'padding-top', 'padding-bottom', 'padding-left', 'padding-right',
        ];
        
        $result = [];
        $declarations = explode(';', $style);
        
        foreach ($declarations as $declaration) {
            $declaration = trim($declaration);
            if (empty($declaration)) {
                continue;
            }
            
            $parts = explode(':', $declaration, 2);
            if (count($parts) !== 2) {
                continue;
            }
            
            $property = strtolower(trim($parts[0]));
            $value = trim($parts[1]);
            
            if (!in_array($property, $allowedProperties)) {
                continue;
            }
            
            // Check for dangerous values (url, expression, etc.)
            if (preg_match('/url\s*\(|expression\s*\(|javascript:|behavior:/i', $value)) {
                continue;
            }
            
            $result[] = $property . ': ' . $value;
        }
        
        return implode('; ', $result);
    }
}
