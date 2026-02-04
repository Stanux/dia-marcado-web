<?php

declare(strict_types=1);

namespace App\Services\Site;

use App\Contracts\Site\SiteValidatorServiceInterface;
use App\Models\SiteLayout;
use App\Models\SystemConfig;

/**
 * Service for validating site content before publication.
 * 
 * Provides validation for required fields, accessibility compliance,
 * and comprehensive QA checklists.
 */
class SiteValidatorService implements SiteValidatorServiceInterface
{
    /**
     * Minimum WCAG AA contrast ratio for normal text.
     */
    private const WCAG_AA_CONTRAST_RATIO = 4.5;

    /**
     * Minimum WCAG AA contrast ratio for large text.
     */
    private const WCAG_AA_LARGE_TEXT_CONTRAST_RATIO = 3.0;

    /**
     * {@inheritdoc}
     */
    public function validateForPublish(SiteLayout $site): ValidationResult
    {
        $result = ValidationResult::success();
        $content = $site->draft_content ?? [];

        // Check meta.title is not empty
        $metaTitle = $content['meta']['title'] ?? '';
        if (empty(trim($metaTitle))) {
            $result->addError('O título do site (meta.title) é obrigatório');
        }

        // Check that at least header or hero is enabled
        $sections = $content['sections'] ?? [];
        $headerEnabled = $sections['header']['enabled'] ?? false;
        $heroEnabled = $sections['hero']['enabled'] ?? false;

        if (!$headerEnabled && !$heroEnabled) {
            $result->addError('Pelo menos uma seção (Header ou Hero) deve estar habilitada');
        }

        // Validate each enabled section
        foreach ($sections as $sectionName => $sectionContent) {
            if (($sectionContent['enabled'] ?? false) === true) {
                $sectionResult = $this->validateSection($sectionName, $sectionContent);
                $result->merge($sectionResult);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function validateSection(string $section, array $content): ValidationResult
    {
        $result = ValidationResult::success();

        if (!($content['enabled'] ?? false)) {
            return $result;
        }

        switch ($section) {
            case 'header':
                $result = $this->validateHeaderSection($content);
                break;
            case 'hero':
                $result = $this->validateHeroSection($content);
                break;
            case 'saveTheDate':
                $result = $this->validateSaveTheDateSection($content);
                break;
            case 'photoGallery':
                $result = $this->validatePhotoGallerySection($content);
                break;
            case 'footer':
                $result = $this->validateFooterSection($content);
                break;
        }

        return $result;
    }


    /**
     * {@inheritdoc}
     */
    public function checkAccessibility(array $content): array
    {
        $warnings = [];

        // Check images for alt text
        $warnings = array_merge($warnings, $this->checkImagesForAltText($content));

        // Check color contrast
        $warnings = array_merge($warnings, $this->checkColorContrast($content));

        return $warnings;
    }

    /**
     * {@inheritdoc}
     */
    public function runQAChecklist(SiteLayout $site): QAResult
    {
        $result = new QAResult();
        $content = $site->draft_content ?? [];

        // Check 1: Images with alt text
        $this->checkImagesAltTextQA($content, $result);

        // Check 2: Links valid (HTTP/HTTPS)
        $this->checkLinksValidQA($content, $result);

        // Check 3: Required fields filled
        $this->checkRequiredFieldsQA($content, $result);

        // Check 4: WCAG AA contrast
        $this->checkContrastQA($content, $result);

        // Check 5: Resource size within threshold
        $this->checkResourceSizeQA($site, $result);

        return $result;
    }

    /**
     * Validate header section content.
     */
    private function validateHeaderSection(array $content): ValidationResult
    {
        $result = ValidationResult::success();

        $title = $content['title'] ?? '';
        if (empty(trim($title))) {
            $result->addError('Header: O título não pode estar vazio quando a seção está habilitada');
        }

        // Validate logo alt text only if using image logo
        $logoType = $content['logo']['type'] ?? 'image';
        if ($logoType === 'image') {
            $logoUrl = $content['logo']['url'] ?? '';
            $logoAlt = $content['logo']['alt'] ?? '';
            if (!empty($logoUrl) && empty(trim($logoAlt))) {
                $result->addWarning('Header: O logotipo deve ter texto alternativo (alt) para acessibilidade');
            }
        }

        return $result;
    }

    /**
     * Validate hero section content.
     */
    private function validateHeroSection(array $content): ValidationResult
    {
        $result = ValidationResult::success();

        $mediaUrl = $content['media']['url'] ?? '';
        $title = $content['title'] ?? '';

        if (empty(trim($mediaUrl)) && empty(trim($title))) {
            $result->addError('Hero: Deve ter uma mídia (imagem/vídeo) ou título definido');
        }

        return $result;
    }

    /**
     * Validate Save the Date section content.
     */
    private function validateSaveTheDateSection(array $content): ValidationResult
    {
        $result = ValidationResult::success();

        $showMap = $content['showMap'] ?? false;
        if ($showMap) {
            $lat = $content['mapCoordinates']['lat'] ?? null;
            $lng = $content['mapCoordinates']['lng'] ?? null;

            if ($lat === null || $lng === null) {
                $result->addError('Save the Date: Coordenadas do mapa são obrigatórias quando o mapa está habilitado');
            } elseif (!$this->isValidLatitude($lat) || !$this->isValidLongitude($lng)) {
                $result->addError('Save the Date: Coordenadas do mapa são inválidas');
            }
        }

        return $result;
    }

    /**
     * Validate photo gallery section content.
     */
    private function validatePhotoGallerySection(array $content): ValidationResult
    {
        $result = ValidationResult::success();

        // Only validate if section is enabled
        if (!($content['enabled'] ?? false)) {
            return $result;
        }

        $albums = $content['albums'] ?? [];
        $photosWithoutAlt = [];

        foreach ($albums as $albumName => $album) {
            $photos = $album['photos'] ?? [];
            foreach ($photos as $index => $photo) {
                $alt = $photo['alt'] ?? '';
                if (empty(trim($alt))) {
                    $photosWithoutAlt[] = "{$albumName}[{$index}]";
                }
            }
        }

        if (!empty($photosWithoutAlt)) {
            $result->addWarning(
                'Galeria de Fotos: As seguintes fotos não têm texto alternativo: ' . 
                implode(', ', array_slice($photosWithoutAlt, 0, 5)) .
                (count($photosWithoutAlt) > 5 ? ' e mais ' . (count($photosWithoutAlt) - 5) : '')
            );
        }

        return $result;
    }

    /**
     * Validate footer section content.
     */
    private function validateFooterSection(array $content): ValidationResult
    {
        $result = ValidationResult::success();

        $showPrivacyPolicy = $content['showPrivacyPolicy'] ?? false;
        $privacyPolicyUrl = $content['privacyPolicyUrl'] ?? '';

        if ($showPrivacyPolicy && empty(trim($privacyPolicyUrl))) {
            $result->addError('Footer: URL da política de privacidade é obrigatória quando habilitada');
        }

        if (!empty($privacyPolicyUrl) && !$this->isValidUrl($privacyPolicyUrl)) {
            $result->addError('Footer: URL da política de privacidade é inválida');
        }

        return $result;
    }


    /**
     * Check all images in content for alt text.
     */
    private function checkImagesForAltText(array $content): array
    {
        $warnings = [];
        $sections = $content['sections'] ?? [];

        // Check header logo
        if (($sections['header']['enabled'] ?? false)) {
            $logoType = $sections['header']['logo']['type'] ?? 'image';
            // Only check alt text if using image logo
            if ($logoType === 'image') {
                $logoUrl = $sections['header']['logo']['url'] ?? '';
                $logoAlt = $sections['header']['logo']['alt'] ?? '';
                if (!empty($logoUrl) && empty(trim($logoAlt))) {
                    $warnings[] = [
                        'type' => 'missing_alt',
                        'section' => 'header',
                        'element' => 'logo',
                        'message' => 'O logotipo do cabeçalho não tem texto alternativo',
                        'suggestion' => 'Adicione uma descrição do logotipo para leitores de tela',
                    ];
                }
            }
        }

        // Check hero media
        if (($sections['hero']['enabled'] ?? false)) {
            $mediaType = $sections['hero']['media']['type'] ?? 'image';
            $mediaUrl = $sections['hero']['media']['url'] ?? '';
            if ($mediaType === 'image' && !empty($mediaUrl)) {
                // Hero images should have alt via title/subtitle
                $title = $sections['hero']['title'] ?? '';
                if (empty(trim($title))) {
                    $warnings[] = [
                        'type' => 'missing_alt',
                        'section' => 'hero',
                        'element' => 'media',
                        'message' => 'A imagem do hero não tem descrição textual',
                        'suggestion' => 'Adicione um título ao hero para descrever a imagem',
                    ];
                }
            }
        }

        // Check photo gallery (only if enabled)
        if (($sections['photoGallery']['enabled'] ?? false)) {
            $albums = $sections['photoGallery']['albums'] ?? [];
            foreach ($albums as $albumName => $album) {
                $photos = $album['photos'] ?? [];
                foreach ($photos as $index => $photo) {
                    $alt = $photo['alt'] ?? '';
                    if (empty(trim($alt))) {
                        $warnings[] = [
                            'type' => 'missing_alt',
                            'section' => 'photoGallery',
                            'element' => "albums.{$albumName}.photos[{$index}]",
                            'message' => "Foto {$index} no álbum '{$albumName}' não tem texto alternativo",
                            'suggestion' => 'Adicione uma descrição da foto para acessibilidade',
                        ];
                    }
                }
            }
        }

        return $warnings;
    }

    /**
     * Check color contrast in content.
     */
    private function checkColorContrast(array $content): array
    {
        $warnings = [];
        $theme = $content['theme'] ?? [];
        $sections = $content['sections'] ?? [];

        // Check header contrast
        if (($sections['header']['enabled'] ?? false)) {
            $bgColor = $sections['header']['style']['backgroundColor'] ?? '#ffffff';
            $textColor = $theme['primaryColor'] ?? '#000000';
            
            $ratio = $this->calculateContrastRatio($textColor, $bgColor);
            if ($ratio < self::WCAG_AA_CONTRAST_RATIO) {
                $warnings[] = [
                    'type' => 'low_contrast',
                    'section' => 'header',
                    'ratio' => round($ratio, 2),
                    'required' => self::WCAG_AA_CONTRAST_RATIO,
                    'message' => "Contraste insuficiente no cabeçalho ({$ratio}:1, mínimo {self::WCAG_AA_CONTRAST_RATIO}:1)",
                    'suggestion' => 'Ajuste as cores do texto ou fundo para melhorar a legibilidade',
                ];
            }
        }

        // Check footer contrast
        if (($sections['footer']['enabled'] ?? false)) {
            $bgColor = $sections['footer']['style']['backgroundColor'] ?? '#333333';
            $textColor = $sections['footer']['style']['textColor'] ?? '#ffffff';
            
            $ratio = $this->calculateContrastRatio($textColor, $bgColor);
            if ($ratio < self::WCAG_AA_CONTRAST_RATIO) {
                $warnings[] = [
                    'type' => 'low_contrast',
                    'section' => 'footer',
                    'ratio' => round($ratio, 2),
                    'required' => self::WCAG_AA_CONTRAST_RATIO,
                    'message' => "Contraste insuficiente no rodapé ({$ratio}:1, mínimo {self::WCAG_AA_CONTRAST_RATIO}:1)",
                    'suggestion' => 'Ajuste as cores do texto ou fundo para melhorar a legibilidade',
                ];
            }
        }

        return $warnings;
    }

    /**
     * QA Check: Images with alt text.
     */
    private function checkImagesAltTextQA(array $content, QAResult $result): void
    {
        $warnings = $this->checkImagesForAltText($content);
        $missingAlt = array_filter($warnings, fn($w) => $w['type'] === 'missing_alt');

        if (empty($missingAlt)) {
            $result->addPassedCheck(
                'images_alt_text',
                'Todas as imagens têm texto alternativo',
                null
            );
        } else {
            $count = count($missingAlt);
            $result->addFailedCheck(
                'images_alt_text',
                "{$count} imagem(ns) sem texto alternativo",
                $missingAlt[0]['section'] ?? null
            );
        }
    }

    /**
     * QA Check: Valid links.
     */
    private function checkLinksValidQA(array $content, QAResult $result): void
    {
        $invalidLinks = $this->findInvalidLinks($content);

        if (empty($invalidLinks)) {
            $result->addPassedCheck(
                'valid_links',
                'Todos os links são válidos (HTTP/HTTPS)',
                null
            );
        } else {
            $count = count($invalidLinks);
            $result->addFailedCheck(
                'valid_links',
                "{$count} link(s) inválido(s) encontrado(s)",
                $invalidLinks[0]['section'] ?? null
            );
        }
    }


    /**
     * QA Check: Required fields filled.
     */
    private function checkRequiredFieldsQA(array $content, QAResult $result): void
    {
        $validationResult = $this->validateForPublishContent($content);

        if ($validationResult->isValid()) {
            $result->addPassedCheck(
                'required_fields',
                'Todos os campos obrigatórios estão preenchidos',
                null
            );
        } else {
            $errors = $validationResult->getErrors();
            $result->addFailedCheck(
                'required_fields',
                $errors[0] ?? 'Campos obrigatórios não preenchidos',
                null
            );
        }
    }

    /**
     * QA Check: WCAG AA contrast.
     */
    private function checkContrastQA(array $content, QAResult $result): void
    {
        $warnings = $this->checkColorContrast($content);
        $lowContrast = array_filter($warnings, fn($w) => $w['type'] === 'low_contrast');

        if (empty($lowContrast)) {
            $result->addPassedCheck(
                'wcag_contrast',
                'Contraste de cores atende WCAG AA',
                null
            );
        } else {
            $count = count($lowContrast);
            $result->addWarningCheck(
                'wcag_contrast',
                "{$count} seção(ões) com contraste abaixo do recomendado",
                $lowContrast[0]['section'] ?? null
            );
        }
    }

    /**
     * QA Check: Resource size within threshold.
     */
    private function checkResourceSizeQA(SiteLayout $site, QAResult $result): void
    {
        $threshold = SystemConfig::get('site.performance_threshold', 5242880); // 5MB default
        $sizeData = $this->calculateTotalResourceSize($site);
        $totalSize = $sizeData['total'];

        if ($totalSize <= $threshold) {
            $sizeMB = round($totalSize / 1048576, 2);
            $result->addPassedCheck(
                'resource_size',
                "Tamanho total dos recursos: {$sizeMB}MB",
                null
            );
        } else {
            $sizeMB = round($totalSize / 1048576, 2);
            $thresholdMB = round($threshold / 1048576, 2);
            
            // Build detailed message with top heavy files
            $message = "Tamanho total ({$sizeMB}MB) excede o recomendado ({$thresholdMB}MB)";
            
            if (!empty($sizeData['top_files'])) {
                $message .= "\n\nArquivos maiores:";
                foreach ($sizeData['top_files'] as $file) {
                    $fileSizeMB = round($file['size'] / 1048576, 2);
                    $message .= "\n• {$file['name']} ({$fileSizeMB}MB)";
                }
            }
            
            $result->addWarningCheck(
                'resource_size',
                $message,
                null
            );
        }
    }

    /**
     * Validate content for publish (without SiteLayout model).
     */
    private function validateForPublishContent(array $content): ValidationResult
    {
        $result = ValidationResult::success();

        // Check meta.title is not empty (can be auto-filled from wedding data)
        $metaTitle = $content['meta']['title'] ?? '';
        if (empty(trim($metaTitle))) {
            // This is a soft warning - the system can auto-fill this
            $result->addWarning('O título do site será preenchido automaticamente com os nomes dos noivos');
        }

        // Check that at least header or hero is enabled
        $sections = $content['sections'] ?? [];
        $headerEnabled = $sections['header']['enabled'] ?? false;
        $heroEnabled = $sections['hero']['enabled'] ?? false;

        if (!$headerEnabled && !$heroEnabled) {
            $result->addError('Pelo menos uma seção (Header ou Hero) deve estar habilitada');
        }

        return $result;
    }

    /**
     * Find invalid links in content.
     */
    private function findInvalidLinks(array $content): array
    {
        $invalidLinks = [];
        $sections = $content['sections'] ?? [];

        // Check header navigation links
        if (($sections['header']['enabled'] ?? false)) {
            $navigation = $sections['header']['navigation'] ?? [];
            foreach ($navigation as $index => $navItem) {
                $target = $navItem['target'] ?? '';
                $type = $navItem['type'] ?? 'anchor';
                if ($type === 'url' && !empty($target) && !$this->isValidUrl($target)) {
                    $invalidLinks[] = [
                        'section' => 'header',
                        'element' => "navigation[{$index}]",
                        'url' => $target,
                    ];
                }
            }

            // Check action button
            $actionTarget = $sections['header']['actionButton']['target'] ?? '';
            if (!empty($actionTarget) && !$this->isAnchorOrValidUrl($actionTarget)) {
                $invalidLinks[] = [
                    'section' => 'header',
                    'element' => 'actionButton',
                    'url' => $actionTarget,
                ];
            }
        }

        // Check hero CTA links
        if (($sections['hero']['enabled'] ?? false)) {
            $ctaPrimary = $sections['hero']['ctaPrimary']['target'] ?? '';
            if (!empty($ctaPrimary) && !$this->isAnchorOrValidUrl($ctaPrimary)) {
                $invalidLinks[] = [
                    'section' => 'hero',
                    'element' => 'ctaPrimary',
                    'url' => $ctaPrimary,
                ];
            }

            $ctaSecondary = $sections['hero']['ctaSecondary']['target'] ?? '';
            if (!empty($ctaSecondary) && !$this->isAnchorOrValidUrl($ctaSecondary)) {
                $invalidLinks[] = [
                    'section' => 'hero',
                    'element' => 'ctaSecondary',
                    'url' => $ctaSecondary,
                ];
            }
        }

        // Check footer privacy policy URL
        if (($sections['footer']['enabled'] ?? false)) {
            $privacyUrl = $sections['footer']['privacyPolicyUrl'] ?? '';
            if (!empty($privacyUrl) && !$this->isValidUrl($privacyUrl)) {
                $invalidLinks[] = [
                    'section' => 'footer',
                    'element' => 'privacyPolicyUrl',
                    'url' => $privacyUrl,
                ];
            }

            // Check social links
            $socialLinks = $sections['footer']['socialLinks'] ?? [];
            foreach ($socialLinks as $index => $link) {
                $url = $link['url'] ?? '';
                if (!empty($url) && !$this->isValidUrl($url)) {
                    $invalidLinks[] = [
                        'section' => 'footer',
                        'element' => "socialLinks[{$index}]",
                        'url' => $url,
                    ];
                }
            }
        }

        return $invalidLinks;
    }


    /**
     * Calculate total resource size for a site.
     * Only counts media that will actually be published (used in draft_content).
     * 
     * @return array ['total' => int, 'top_files' => array]
     */
    private function calculateTotalResourceSize(SiteLayout $site): array
    {
        $content = $site->draft_content ?? [];
        $sections = $content['sections'] ?? [];
        
        // Collect all media URLs used in enabled sections
        $usedMediaUrls = [];
        
        // Check header logo
        if (($sections['header']['enabled'] ?? false)) {
            $logoUrl = $sections['header']['logo']['url'] ?? '';
            if (!empty($logoUrl)) {
                $usedMediaUrls[] = $logoUrl;
            }
        }
        
        // Check hero media
        if (($sections['hero']['enabled'] ?? false)) {
            $mediaUrl = $sections['hero']['media']['url'] ?? '';
            if (!empty($mediaUrl)) {
                $usedMediaUrls[] = $mediaUrl;
            }
            $fallbackUrl = $sections['hero']['media']['fallback'] ?? '';
            if (!empty($fallbackUrl)) {
                $usedMediaUrls[] = $fallbackUrl;
            }
        }
        
        // Check photo gallery (only if enabled)
        if (($sections['photoGallery']['enabled'] ?? false)) {
            $albums = $sections['photoGallery']['albums'] ?? [];
            foreach ($albums as $album) {
                $photos = $album['photos'] ?? [];
                foreach ($photos as $photo) {
                    $photoUrl = is_array($photo) ? ($photo['url'] ?? '') : $photo;
                    if (!empty($photoUrl)) {
                        $usedMediaUrls[] = $photoUrl;
                    }
                }
            }
        }
        
        // Remove duplicates
        $usedMediaUrls = array_unique($usedMediaUrls);
        
        if (empty($usedMediaUrls)) {
            return [
                'total' => 0,
                'top_files' => [],
            ];
        }
        
        // Get media records that match the used URLs
        $media = $site->media()
            ->select('id', 'original_name', 'path', 'disk', 'size')
            ->get();
        
        $totalSize = 0;
        $filesWithSize = [];
        
        foreach ($media as $item) {
            // Check if this media is actually used in the content
            $mediaUrl = \Storage::disk($item->disk)->url($item->path);
            $isUsed = false;
            
            foreach ($usedMediaUrls as $usedUrl) {
                if (str_contains($usedUrl, $item->path) || $usedUrl === $mediaUrl) {
                    $isUsed = true;
                    break;
                }
            }
            
            if (!$isUsed) {
                continue;
            }
            
            // Use size from database if available, otherwise get from file
            $fileSize = $item->size;
            
            if (!$fileSize || $fileSize <= 0) {
                try {
                    $fileSize = \Storage::disk($item->disk)->size($item->path);
                } catch (\Exception $e) {
                    $fileSize = 0;
                }
            }
            
            $totalSize += $fileSize;
            
            $filesWithSize[] = [
                'id' => $item->id,
                'name' => $item->original_name,
                'size' => $fileSize,
            ];
        }
        
        // Sort by size descending and get top 5
        usort($filesWithSize, function($a, $b) {
            return $b['size'] <=> $a['size'];
        });
        
        $topFiles = array_slice($filesWithSize, 0, 5);
        
        return [
            'total' => $totalSize,
            'top_files' => $topFiles,
        ];
    }

    /**
     * Check if a URL is valid (HTTP or HTTPS).
     */
    private function isValidUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $parsed = parse_url($url);
        if ($parsed === false) {
            return false;
        }

        $scheme = $parsed['scheme'] ?? '';
        return in_array(strtolower($scheme), ['http', 'https']);
    }

    /**
     * Check if a target is an anchor or valid URL.
     */
    private function isAnchorOrValidUrl(string $target): bool
    {
        if (empty($target)) {
            return true;
        }

        // Anchors start with #
        if (str_starts_with($target, '#')) {
            return true;
        }

        return $this->isValidUrl($target);
    }

    /**
     * Check if latitude is valid.
     */
    private function isValidLatitude($lat): bool
    {
        if (!is_numeric($lat)) {
            return false;
        }
        return $lat >= -90 && $lat <= 90;
    }

    /**
     * Check if longitude is valid.
     */
    private function isValidLongitude($lng): bool
    {
        if (!is_numeric($lng)) {
            return false;
        }
        return $lng >= -180 && $lng <= 180;
    }

    /**
     * Calculate contrast ratio between two colors.
     * 
     * Uses WCAG 2.0 formula for relative luminance.
     *
     * @param string $foreground Foreground color (hex)
     * @param string $background Background color (hex)
     * @return float Contrast ratio
     */
    private function calculateContrastRatio(string $foreground, string $background): float
    {
        $l1 = $this->getRelativeLuminance($foreground);
        $l2 = $this->getRelativeLuminance($background);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Calculate relative luminance of a color.
     * 
     * @param string $hexColor Hex color code
     * @return float Relative luminance (0-1)
     */
    private function getRelativeLuminance(string $hexColor): float
    {
        $rgb = $this->hexToRgb($hexColor);
        
        $r = $this->linearize($rgb['r'] / 255);
        $g = $this->linearize($rgb['g'] / 255);
        $b = $this->linearize($rgb['b'] / 255);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Convert hex color to RGB.
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6) {
            return ['r' => 0, 'g' => 0, 'b' => 0];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Linearize sRGB color component.
     */
    private function linearize(float $value): float
    {
        if ($value <= 0.03928) {
            return $value / 12.92;
        }
        return pow(($value + 0.055) / 1.055, 2.4);
    }
}
