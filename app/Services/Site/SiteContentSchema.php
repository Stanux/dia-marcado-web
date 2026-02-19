<?php

namespace App\Services\Site;

/**
 * SiteContentSchema
 * 
 * Defines the standard JSON structure for wedding site content.
 * Provides default content and validation methods.
 */
class SiteContentSchema
{
    public const VERSION = '1.0';

    /**
     * Required section keys that must exist in valid content.
     */
    public const REQUIRED_SECTIONS = [
        'header',
        'hero',
        'saveTheDate',
        'giftRegistry',
        'rsvp',
        'photoGallery',
        'footer',
    ];

    /**
     * Get the default content structure for a new site.
     *
     * @return array The complete default content structure
     */
    public static function getDefaultContent(): array
    {
        return [
            'version' => self::VERSION,
            'sections' => [
                'header' => self::getHeaderSection(),
                'hero' => self::getHeroSection(),
                'saveTheDate' => self::getSaveTheDateSection(),
                'giftRegistry' => self::getGiftRegistrySection(),
                'rsvp' => self::getRsvpSection(),
                'photoGallery' => self::getPhotoGallerySection(),
                'footer' => self::getFooterSection(),
            ],
            'meta' => [
                'title' => '',
                'description' => '',
                'ogImage' => '',
                'canonical' => '',
            ],
            'theme' => [
                'primaryColor' => '#d4a574',
                'secondaryColor' => '#8b7355',
                'fontFamily' => 'Playfair Display',
                'fontSize' => '16px',
            ],
        ];
    }

    /**
     * Get the default header section structure.
     */
    public static function getHeaderSection(): array
    {
        return [
            'enabled' => true,
            'logo' => [
                'type' => 'image', // 'image' ou 'text'
                'url' => '',
                'alt' => '',
                'text' => [
                    'initials' => ['', ''],
                    'connector' => '&',
                ],
            ],
            'title' => '',
            'subtitle' => '',
            'showDate' => true,
            'navigation' => [],
            'actionButton' => [
                'label' => '',
                'target' => '',
                'style' => 'primary',
                'icon' => null,
            ],
            'style' => [
                'height' => '80px',
                'alignment' => 'center',
                'backgroundColor' => '#ffffff',
                'sticky' => false,
                'overlay' => [
                    'enabled' => false,
                    'opacity' => 0.3,
                ],
            ],
        ];
    }

    /**
     * Get the default hero section structure.
     */
    public static function getHeroSection(): array
    {
        return [
            'enabled' => true,
            'media' => [
                'type' => 'image',
                'url' => '',
                'fallback' => '',
                'autoplay' => true,
                'loop' => true,
            ],
            'title' => '',
            'subtitle' => '',
            'ctaPrimary' => [
                'label' => '',
                'target' => '',
            ],
            'ctaSecondary' => [
                'label' => '',
                'target' => '',
            ],
            'layout' => 'full-bleed',
            'style' => [
                'overlay' => [
                    'color' => '#000000',
                    'opacity' => 0.3,
                ],
                'textAlign' => 'center',
                'animation' => 'fade',
                'animationDuration' => 500,
            ],
        ];
    }

    /**
     * Get the default Save the Date section structure.
     */
    public static function getSaveTheDateSection(): array
    {
        return [
            'enabled' => true,
            'navigation' => [
                'label' => 'Save the Date',
                'showInMenu' => true,
            ],
            'sectionTypography' => [
                'fontFamily' => 'Playfair Display',
                'fontColor' => '#d4a574',
                'fontSize' => 18,
                'fontWeight' => 400,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'showMap' => true,
            'mapProvider' => 'google',
            'mapCoordinates' => [
                'lat' => null,
                'lng' => null,
            ],
            'description' => '',
            'descriptionTypography' => [
                'fontFamily' => 'Playfair Display',
                'fontColor' => '#666666',
                'fontSize' => 16,
                'fontWeight' => 400,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'showCountdown' => true,
            'countdownFormat' => 'days',
            'countdownNumbersTypography' => [
                'fontFamily' => 'Playfair Display',
                'fontColor' => '#d4a574',
                'fontSize' => 48,
                'fontWeight' => 700,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'countdownLabelsTypography' => [
                'fontFamily' => 'Montserrat',
                'fontColor' => '#999999',
                'fontSize' => 12,
                'fontWeight' => 400,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'showCalendarButton' => true,
            'style' => [
                'backgroundColor' => '#f5f5f5',
                'layout' => 'modal',
            ],
        ];
    }

    /**
     * Get the default Gift Registry section structure (mockup).
     */
    public static function getGiftRegistrySection(): array
    {
        return [
            'enabled' => false,
            'navigation' => [
                'label' => 'Lista de Presentes',
                'showInMenu' => true,
            ],
            'title' => 'Lista de Presentes',
            'description' => 'Em breve...',
            'style' => [
                'backgroundColor' => '#ffffff',
            ],
        ];
    }

    /**
     * Get the default RSVP section structure (mockup).
     */
    public static function getRsvpSection(): array
    {
        return [
            'enabled' => false,
            'navigation' => [
                'label' => 'Confirme Presença',
                'showInMenu' => true,
            ],
            'title' => 'Confirme sua Presença',
            'description' => '',
            'mockFields' => [
                ['label' => 'Nome', 'type' => 'text'],
                ['label' => 'Email', 'type' => 'email'],
                ['label' => 'Confirmação', 'type' => 'select'],
                ['label' => 'Acompanhantes', 'type' => 'number'],
            ],
            'style' => [
                'backgroundColor' => '#f5f5f5',
            ],
        ];
    }

    /**
     * Get the default Photo Gallery section structure.
     */
    public static function getPhotoGallerySection(): array
    {
        return [
            'enabled' => false,
            'navigation' => [
                'label' => 'Galeria de Fotos',
                'showInMenu' => true,
            ],
            'albums' => [
                'before' => [
                    'title' => 'Nossa História',
                    'photos' => [],
                ],
                'after' => [
                    'title' => 'O Grande Dia',
                    'photos' => [],
                ],
            ],
            'layout' => 'masonry',
            'showLightbox' => true,
            'allowDownload' => true,
            'style' => [
                'backgroundColor' => '#ffffff',
                'columns' => 3,
            ],
        ];
    }

    /**
     * Get the default footer section structure.
     */
    public static function getFooterSection(): array
    {
        return [
            'enabled' => true,
            'socialLinks' => [],
            'copyrightText' => '',
            'copyrightYear' => null,
            'showPrivacyPolicy' => false,
            'privacyPolicyUrl' => '',
            'showBackToTop' => true,
            'style' => [
                'backgroundColor' => '#333333',
                'textColor' => '#ffffff',
                'borderTop' => false,
            ],
        ];
    }

    /**
     * Validate site content structure.
     *
     * @param array $content The content to validate
     * @return array Array of error messages (empty if valid)
     */
    public static function validate(array $content): array
    {
        $errors = [];

        // Check for sections key
        if (!isset($content['sections'])) {
            $errors[] = 'Content must have a "sections" key';
            return $errors;
        }

        // Check for required sections
        foreach (self::REQUIRED_SECTIONS as $section) {
            if (!isset($content['sections'][$section])) {
                $errors[] = "Missing required section: {$section}";
            }
        }

        // Validate each section has 'enabled' field
        foreach (self::REQUIRED_SECTIONS as $section) {
            if (isset($content['sections'][$section]) && !isset($content['sections'][$section]['enabled'])) {
                $errors[] = "Section '{$section}' must have an 'enabled' field";
            }
        }

        return $errors;
    }
}
