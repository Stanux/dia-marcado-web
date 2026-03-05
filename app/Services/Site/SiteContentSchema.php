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
        'guestsV2',
        'rsvp',
        'photoGallery',
        'footer',
    ];

    /**
     * Sections that can be reordered in the site editor.
     * Header and footer remain fixed.
     */
    public const MOVABLE_SECTIONS = [
        'hero',
        'saveTheDate',
        'giftRegistry',
        'guestsV2',
        'photoGallery',
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
            'sectionOrder' => self::MOVABLE_SECTIONS,
            'sections' => [
                'header' => self::getHeaderSection(),
                'hero' => self::getHeroSection(),
                'saveTheDate' => self::getSaveTheDateSection(),
                'giftRegistry' => self::getGiftRegistrySection(),
                'guestsV2' => self::getGuestsV2Section(),
                'rsvp' => self::getRsvpSection(),
                'photoGallery' => self::getPhotoGallerySection(),
                'footer' => self::getFooterSection(),
            ],
            'meta' => [
                'title' => '{primeiro_nome_noivo} & {primeiro_nome_noiva} em {data_curta}',
                'description' => '{primeiro_nome_noivo} & {primeiro_nome_noiva} em {data_curta}',
                'ogImage' => '',
                'canonical' => '',
            ],
            'theme' => [
                'primaryColor' => '#e11d48',
                'secondaryColor' => '#be123c',
                'baseBackgroundColor' => '#ffffff',
                'surfaceBackgroundColor' => '#f9fafb',
                'fontFamily' => 'Figtree',
                'fontSize' => '14px',
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
            'menuTypography' => [
                'fontFamily' => 'Montserrat',
                'fontColor' => '#374151',
                'fontSize' => 14,
                'fontWeight' => 400,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'menuHoverTypography' => [
                'fontFamily' => 'Montserrat',
                'fontColor' => '#f97373',
                'fontSize' => 14,
                'fontWeight' => 500,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
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
                'fontColor' => '#f97373',
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
                'fontColor' => '#f97373',
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
            'calendarButtonTypography' => [
                'fontFamily' => 'Montserrat',
                'fontColor' => '#ffffff',
                'fontSize' => 14,
                'fontWeight' => 600,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'calendarButtonStyle' => [
                'backgroundColor' => '#f97373',
                'borderColor' => '#f97373',
                'borderWidth' => 0,
                'borderRadius' => 8,
                'paddingX' => 24,
                'paddingY' => 12,
            ],
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
            'config' => [
                'section_title' => 'Lista de Presentes',
                'fee_modality' => 'couple_pays',
                'registry_mode' => 'quantity',
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
            'titleTypography' => [
                'fontFamily' => 'Playfair Display',
                'fontColor' => '#d87a8d',
                'fontSize' => 36,
                'fontWeight' => 700,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'subtitleTypography' => [
                'fontFamily' => 'Playfair Display',
                'fontColor' => '#4b5563',
                'fontSize' => 18,
                'fontWeight' => 400,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'access' => [
                // inherit | open | restricted | token_only
                'mode' => 'inherit',
                'allowResponseUpdate' => true,
                'requireInviteToken' => false,
            ],
            'eventSelection' => [
                // all_active | selected
                'mode' => 'all_active',
                'selectedEventIds' => [],
                'featuredEventId' => null,
            ],
            'fields' => [
                'collectName' => true,
                'collectEmail' => true,
                'collectPhone' => true,
                'requireEmail' => false,
                'requirePhone' => false,
                'showDynamicQuestions' => true,
            ],
            'messages' => [
                'success' => 'RSVP enviado com sucesso!',
                'genericError' => 'Erro ao enviar RSVP.',
                'nameRequired' => 'Informe seu nome.',
                'eventRequired' => 'Selecione um evento.',
                'tokenLimitReached' => 'Este token já atingiu o limite de uso. Solicite um novo link para alterar sua confirmação.',
                'tokenInvalid' => 'Este link de convite é inválido.',
                'tokenExpired' => 'Este link de convite expirou.',
                'tokenRevoked' => 'Este link de convite foi revogado.',
                'tokenRequired' => 'Este convite exige um link com token para confirmação.',
                'restrictedAccess' => 'Não encontramos seu cadastro na lista de convidados para este evento.',
                'submitLabel' => 'Confirmar Presença',
                'submitLoadingLabel' => 'Enviando...',
                'submitDisabledTokenLabel' => 'Token sem novos usos',
            ],
            'labels' => [
                'name' => 'Nome completo',
                'email' => 'Email',
                'phone' => 'Telefone',
                'event' => 'Evento',
                'status' => 'Confirmação',
            ],
            'statusOptions' => [
                'showConfirmed' => true,
                'showMaybe' => true,
                'showDeclined' => true,
                'confirmedLabel' => 'Confirmo presença',
                'maybeLabel' => 'Talvez',
                'declinedLabel' => 'Não poderei comparecer',
            ],
            'mockFields' => [
                ['label' => 'Nome', 'type' => 'text'],
                ['label' => 'Email', 'type' => 'email'],
                ['label' => 'Confirmação', 'type' => 'select'],
                ['label' => 'Acompanhantes', 'type' => 'number'],
            ],
            'style' => [
                'backgroundColor' => '#f5f5f5',
                'layout' => 'card',
                'containerMaxWidth' => 'max-w-xl',
                'showCard' => true,
            ],
            'preview' => [
                // default | valid_token | invalid_token | token_limit_reached | restricted_denied | success
                'scenario' => 'default',
            ],
        ];
    }

    /**
     * Get the default Guests V2 section structure.
     */
    public static function getGuestsV2Section(): array
    {
        return [
            'enabled' => false,
            'navigation' => [
                'label' => 'Convidados',
                'showInMenu' => true,
            ],
            'title' => 'Convidados',
            'description' => 'Utilize o convite recebido para confirmar presença e manter seus dados atualizados.',
            'titleTypography' => [
                'fontFamily' => 'Playfair Display',
                'fontColor' => '#d87a8d',
                'fontSize' => 36,
                'fontWeight' => 700,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'descriptionTypography' => [
                'fontFamily' => 'Playfair Display',
                'fontColor' => '#4b5563',
                'fontSize' => 18,
                'fontWeight' => 400,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'style' => [
                'backgroundColor' => '#f5f5f5',
                'layout' => 'card',
                'containerMaxWidth' => 'max-w-6xl',
                'showCard' => true,
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
            'title' => 'Galeria de Fotos',
            'albums' => [
                'before' => [
                    'title' => 'Nossa História',
                    'items' => [],
                    'photos' => [],
                ],
                'after' => [
                    'title' => 'O Grande Dia',
                    'items' => [],
                    'photos' => [],
                ],
            ],
            'layout' => 'masonry',
            'showLightbox' => true,
            'allowDownload' => true,
            'pagination' => [
                'perPage' => 20,
            ],
            'video' => [
                'hoverPreview' => true,
                'hoverDelayMs' => 1000,
            ],
            'display' => [
                'showBefore' => true,
                'showAfter' => true,
            ],
            'titleTypography' => [
                'fontFamily' => 'Playfair Display',
                'fontColor' => '#c45a6f',
                'fontSize' => 40,
                'fontWeight' => 700,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'tabsTypography' => [
                'fontFamily' => 'Montserrat',
                'fontColor' => '#6b7280',
                'fontSize' => 14,
                'fontWeight' => 500,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'tabsActiveTypography' => [
                'fontFamily' => 'Montserrat',
                'fontColor' => '#111827',
                'fontSize' => 14,
                'fontWeight' => 600,
                'fontItalic' => false,
                'fontUnderline' => false,
            ],
            'tabsStyle' => [
                'backgroundColor' => '#f3f4f6',
                'activeBackgroundColor' => '#ffffff',
                'borderColor' => '#e5e7eb',
                'activeBorderColor' => '#d87a8d',
            ],
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

        if (isset($content['sectionOrder'])) {
            if (!is_array($content['sectionOrder'])) {
                $errors[] = 'The "sectionOrder" field must be an array when provided';
            } else {
                foreach ($content['sectionOrder'] as $index => $sectionKey) {
                    if (!is_string($sectionKey)) {
                        $errors[] = "The \"sectionOrder\" item at index {$index} must be a string";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Normalize any incoming content against the default schema.
     *
     * Keeps existing values and fills only missing keys.
     * Indexed arrays are preserved as-is to avoid unexpected list merges.
     */
    public static function normalize(array $content): array
    {
        $normalized = self::mergeWithDefaults(self::getDefaultContent(), $content);
        $normalized = self::migrateLegacyRsvpToGuestsV2($normalized);

        // Legacy RSVP section is intentionally disabled/hidden while Guests V2 is adopted.
        if (isset($normalized['sections']['rsvp']) && is_array($normalized['sections']['rsvp'])) {
            $normalized['sections']['rsvp']['enabled'] = false;

            if (
                isset($normalized['sections']['rsvp']['navigation'])
                && is_array($normalized['sections']['rsvp']['navigation'])
            ) {
                $normalized['sections']['rsvp']['navigation']['showInMenu'] = false;
            }
        }

        return $normalized;
    }

    /**
     * Migrate legacy RSVP references to Guests V2 so existing sites keep working
     * without manual editor intervention.
     */
    private static function migrateLegacyRsvpToGuestsV2(array $content): array
    {
        if (!isset($content['sections']) || !is_array($content['sections'])) {
            return $content;
        }

        $sections = &$content['sections'];

        $legacyRsvp = is_array($sections['rsvp'] ?? null) ? $sections['rsvp'] : [];
        if ($legacyRsvp === []) {
            return $content;
        }

        if (!isset($sections['guestsV2']) || !is_array($sections['guestsV2'])) {
            $sections['guestsV2'] = self::getGuestsV2Section();
        }

        $guestsV2 = &$sections['guestsV2'];
        $legacyRsvpEnabled = (bool) ($legacyRsvp['enabled'] ?? false);

        if (!(bool) ($guestsV2['enabled'] ?? false) && $legacyRsvpEnabled) {
            $guestsV2['enabled'] = true;
        }

        $legacyNavigation = is_array($legacyRsvp['navigation'] ?? null) ? $legacyRsvp['navigation'] : [];
        if (!isset($guestsV2['navigation']) || !is_array($guestsV2['navigation'])) {
            $guestsV2['navigation'] = [
                'label' => 'Convidados',
                'showInMenu' => true,
            ];
        }

        if (
            !empty($legacyNavigation['label'])
            && (
                empty($guestsV2['navigation']['label'])
                || $guestsV2['navigation']['label'] === 'Convidados'
            )
        ) {
            $guestsV2['navigation']['label'] = (string) $legacyNavigation['label'];
        }

        if (
            !(bool) ($guestsV2['navigation']['showInMenu'] ?? false)
            && (bool) ($legacyNavigation['showInMenu'] ?? false)
        ) {
            $guestsV2['navigation']['showInMenu'] = true;
        }

        if (
            !empty($legacyRsvp['title'])
            && (
                empty($guestsV2['title'])
                || $guestsV2['title'] === 'Convidados'
            )
        ) {
            $guestsV2['title'] = (string) $legacyRsvp['title'];
        }

        if (
            isset($legacyRsvp['titleTypography'])
            && is_array($legacyRsvp['titleTypography'])
            && (!isset($guestsV2['titleTypography']) || !is_array($guestsV2['titleTypography']))
        ) {
            $guestsV2['titleTypography'] = $legacyRsvp['titleTypography'];
        }

        if (
            isset($legacyRsvp['subtitleTypography'])
            && is_array($legacyRsvp['subtitleTypography'])
            && (!isset($guestsV2['descriptionTypography']) || !is_array($guestsV2['descriptionTypography']))
        ) {
            $guestsV2['descriptionTypography'] = $legacyRsvp['subtitleTypography'];
        }

        $legacyStyle = is_array($legacyRsvp['style'] ?? null) ? $legacyRsvp['style'] : [];
        if ($legacyRsvpEnabled && $legacyStyle !== []) {
            if (!isset($guestsV2['style']) || !is_array($guestsV2['style'])) {
                $guestsV2['style'] = [];
            }

            foreach (['backgroundColor', 'layout', 'containerMaxWidth', 'showCard'] as $styleKey) {
                if (array_key_exists($styleKey, $legacyStyle)) {
                    $guestsV2['style'][$styleKey] = $legacyStyle[$styleKey];
                }
            }
        }

        if (isset($sections['header']) && is_array($sections['header'])) {
            $sections['header'] = self::migrateLegacyHeaderNavigation(
                $sections['header'],
                (string) ($guestsV2['navigation']['label'] ?? 'Convidados'),
                (bool) ($guestsV2['navigation']['showInMenu'] ?? $legacyRsvpEnabled)
            );
        }

        return $content;
    }

    private static function migrateLegacyHeaderNavigation(
        array $headerSection,
        string $defaultGuestsLabel,
        bool $defaultShowInMenu
    ): array {
        $navigation = $headerSection['navigation'] ?? null;
        if (!is_array($navigation)) {
            return $headerSection;
        }

        $hasGuestsV2Item = false;
        foreach ($navigation as $item) {
            if (is_array($item) && ($item['sectionKey'] ?? null) === 'guestsV2') {
                $hasGuestsV2Item = true;
                break;
            }
        }

        $migrated = [];

        foreach ($navigation as $item) {
            if (!is_array($item)) {
                $migrated[] = $item;
                continue;
            }

            $sectionKey = $item['sectionKey'] ?? null;
            if ($sectionKey === 'rsvp') {
                if ($hasGuestsV2Item) {
                    continue;
                }

                $item['sectionKey'] = 'guestsV2';
                $item['target'] = self::normalizeGuestsTarget((string) ($item['target'] ?? ''));
                if (!isset($item['type']) || !is_string($item['type']) || trim($item['type']) === '') {
                    $item['type'] = 'anchor';
                }

                if (!isset($item['label']) || !is_string($item['label']) || trim($item['label']) === '') {
                    $item['label'] = $defaultGuestsLabel;
                }

                if (!array_key_exists('showInMenu', $item)) {
                    $item['showInMenu'] = $defaultShowInMenu;
                }

                $hasGuestsV2Item = true;
            } elseif (($item['sectionKey'] ?? null) === 'guestsV2') {
                $item['target'] = self::normalizeGuestsTarget((string) ($item['target'] ?? ''));
            }

            $migrated[] = $item;
        }

        if (!$hasGuestsV2Item && $defaultShowInMenu) {
            $migrated[] = [
                'sectionKey' => 'guestsV2',
                'label' => $defaultGuestsLabel,
                'target' => '#guests-v2',
                'type' => 'anchor',
                'showInMenu' => true,
            ];
        }

        $headerSection['navigation'] = $migrated;

        return $headerSection;
    }

    private static function normalizeGuestsTarget(string $target): string
    {
        $trimmed = trim($target);
        if ($trimmed === '' || $trimmed === '#rsvp' || $trimmed === '#confirmar-presenca') {
            return '#guests-v2';
        }

        return $trimmed;
    }

    /**
     * Recursively merge content with defaults.
     *
     * For associative arrays, keys are merged recursively.
     * For indexed arrays, provided content wins entirely.
     */
    private static function mergeWithDefaults(mixed $defaults, mixed $content): mixed
    {
        if (!is_array($defaults)) {
            return $content ?? $defaults;
        }

        if (!is_array($content)) {
            return $defaults;
        }

        if (self::isIndexedArray($defaults) || self::isIndexedArray($content)) {
            return $content;
        }

        $merged = $defaults;

        foreach ($content as $key => $value) {
            if (!array_key_exists($key, $defaults)) {
                $merged[$key] = $value;
                continue;
            }

            $merged[$key] = self::mergeWithDefaults($defaults[$key], $value);
        }

        return $merged;
    }

    private static function isIndexedArray(array $value): bool
    {
        return array_is_list($value);
    }
}
