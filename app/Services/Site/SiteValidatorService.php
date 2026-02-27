<?php

declare(strict_types=1);

namespace App\Services\Site;

use App\Contracts\Site\PlaceholderServiceInterface;
use App\Contracts\Site\SiteValidatorServiceInterface;
use App\Models\Guest;
use App\Models\GuestEvent;
use App\Models\GuestInvite;
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

        // Check 4: Dynamic data readiness (placeholders / initials / meta)
        $this->checkDynamicDataReadinessQA($site, $content, $result);

        // Check 5: WCAG AA contrast
        $this->checkContrastQA($content, $result);

        // Check 6: Resource size within threshold
        $this->checkResourceSizeQA($site, $result);

        // Check 7: RSVP readiness
        $this->checkRsvpReadinessQA($site, $content, $result);

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
            $items = $this->getGalleryAlbumItems(is_array($album) ? $album : []);
            foreach ($items as $index => $item) {
                if ($this->getGalleryItemType($item) !== 'image') {
                    continue;
                }

                $alt = is_array($item) ? ($item['alt'] ?? '') : '';
                if (empty(trim((string) $alt))) {
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
                $mediaAlt = $sections['hero']['media']['alt'] ?? '';
                if (empty(trim((string) $mediaAlt))) {
                    $warnings[] = [
                        'type' => 'missing_alt',
                        'section' => 'hero',
                        'element' => 'media',
                        'message' => 'A imagem do destaque não tem texto alternativo',
                        'suggestion' => 'Adicione um texto alternativo (alt) para a imagem de fundo do destaque',
                    ];
                }
            }
        }

        // Check photo gallery (only if enabled)
        if (($sections['photoGallery']['enabled'] ?? false)) {
            $albums = $sections['photoGallery']['albums'] ?? [];
            foreach ($albums as $albumName => $album) {
                $items = $this->getGalleryAlbumItems(is_array($album) ? $album : []);
                foreach ($items as $index => $item) {
                    if ($this->getGalleryItemType($item) !== 'image') {
                        continue;
                    }

                    $alt = is_array($item) ? ($item['alt'] ?? '') : '';
                    if (empty(trim((string) $alt))) {
                        $warnings[] = [
                            'type' => 'missing_alt',
                            'section' => 'photoGallery',
                            'element' => "albums.{$albumName}.items[{$index}]",
                            'message' => "Mídia {$index} (imagem) no álbum '{$albumName}' não tem texto alternativo",
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
            $header = is_array($sections['header'] ?? null) ? $sections['header'] : [];
            $bgColor = (string) ($header['style']['backgroundColor'] ?? '#ffffff');
            $candidates = $this->getHeaderContrastCandidates($header, is_array($theme) ? $theme : []);

            $failingCandidates = [];

            foreach ($candidates as $candidate) {
                $ratio = $this->calculateContrastRatio((string) $candidate['color'], $bgColor);
                $requiredRatio = $this->getRequiredContrastRatio(
                    $candidate['fontSize'] ?? null,
                    $candidate['fontWeight'] ?? null
                );

                if ($ratio < $requiredRatio) {
                    $failingCandidates[] = [
                        'label' => (string) ($candidate['label'] ?? 'Texto'),
                        'ratio' => $ratio,
                        'required' => $requiredRatio,
                    ];
                }
            }

            if (!empty($failingCandidates)) {
                usort($failingCandidates, static fn (array $a, array $b): int => $a['ratio'] <=> $b['ratio']);
                $worstCandidate = $failingCandidates[0];
                $failingLabels = array_values(array_unique(array_map(
                    static fn (array $candidate): string => (string) $candidate['label'],
                    $failingCandidates
                )));

                $warnings[] = [
                    'type' => 'low_contrast',
                    'section' => 'header',
                    'ratio' => round($worstCandidate['ratio'], 2),
                    'required' => $worstCandidate['required'],
                    'elements' => $failingLabels,
                    'message' => sprintf(
                        'Contraste insuficiente no cabeçalho (%.2f:1, mínimo %.1f:1) em: %s',
                        $worstCandidate['ratio'],
                        $worstCandidate['required'],
                        implode(', ', $failingLabels)
                    ),
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
                    'elements' => ['Texto do rodapé'],
                    'message' => "Contraste insuficiente no rodapé ({$ratio}:1, mínimo {self::WCAG_AA_CONTRAST_RATIO}:1)",
                    'suggestion' => 'Ajuste as cores do texto ou fundo para melhorar a legibilidade',
                ];
            }
        }

        // Check Save the Date contrast
        if (($sections['saveTheDate']['enabled'] ?? false)) {
            $saveTheDate = is_array($sections['saveTheDate'] ?? null) ? $sections['saveTheDate'] : [];
            $effectiveTheme = is_array($theme) ? $theme : [];
            $bgColor = $this->resolveSaveTheDateTextBackgroundColor($saveTheDate, $effectiveTheme);
            $candidates = $this->getSaveTheDateContrastCandidates($saveTheDate, $effectiveTheme);

            $failingCandidates = [];

            foreach ($candidates as $candidate) {
                $ratio = $this->calculateContrastRatio((string) $candidate['color'], $bgColor);
                $requiredRatio = $this->getRequiredContrastRatio(
                    $candidate['fontSize'] ?? null,
                    $candidate['fontWeight'] ?? null
                );

                if ($ratio < $requiredRatio) {
                    $failingCandidates[] = [
                        'label' => (string) ($candidate['label'] ?? 'Texto'),
                        'ratio' => $ratio,
                        'required' => $requiredRatio,
                    ];
                }
            }

            if (!empty($failingCandidates)) {
                usort($failingCandidates, static fn (array $a, array $b): int => $a['ratio'] <=> $b['ratio']);
                $worstCandidate = $failingCandidates[0];
                $failingLabels = array_values(array_unique(array_map(
                    static fn (array $candidate): string => (string) $candidate['label'],
                    $failingCandidates
                )));

                $warnings[] = [
                    'type' => 'low_contrast',
                    'section' => 'saveTheDate',
                    'ratio' => round($worstCandidate['ratio'], 2),
                    'required' => $worstCandidate['required'],
                    'elements' => $failingLabels,
                    'message' => sprintf(
                        'Contraste insuficiente no Save the Date (%.2f:1, mínimo %.1f:1) em: %s',
                        $worstCandidate['ratio'],
                        $worstCandidate['required'],
                        implode(', ', $failingLabels)
                    ),
                    'suggestion' => 'Ajuste as cores da tipografia ou do fundo para melhorar a legibilidade',
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
     * QA Check: Dynamic placeholder readiness (date, names, initials, meta rendering).
     */
    private function checkDynamicDataReadinessQA(SiteLayout $site, array $content, QAResult $result): void
    {
        $usedPlaceholders = $this->extractPlaceholdersFromContent($content);
        $usedPlaceholderLookup = array_fill_keys($usedPlaceholders, true);
        $issues = [];

        if ($this->placeholderHasMissingData($site, '{data}')) {
            $issues[] = 'Data do evento não definida';
        }

        if ($this->placeholderHasMissingData($site, '{primeiro_nome_noivo}')) {
            $issues[] = 'Primeiro nome principal do casal não definido';
        }

        if ($this->placeholderHasMissingData($site, '{primeiro_nome_noiva}')) {
            $issues[] = 'Primeiro nome do(a) parceiro(a) não definido';
        }

        if ($this->placeholderHasMissingData($site, '{noivo}')) {
            $issues[] = 'Nome completo principal do casal não definido';
        }

        if ($this->placeholderHasMissingData($site, '{noiva}')) {
            $issues[] = 'Nome completo do(a) parceiro(a) não definido';
        }

        $requiredDynamicPlaceholders = [
            '{data}' => 'Data do evento',
            '{data_curta}' => 'Data do evento',
            '{data_extenso}' => 'Data do evento',
            '{data_simples}' => 'Data do evento',
            '{primeiro_nome_noivo}' => 'Primeiro nome',
            '{primeiro_nome_noiva}' => 'Primeiro nome',
            '{primeiro_nome_1}' => 'Primeiro nome',
            '{primeiro_nome_2}' => 'Primeiro nome',
            '{noivo}' => 'Nome completo',
            '{noiva}' => 'Nome completo',
            '{nome_1}' => 'Nome completo',
            '{nome_2}' => 'Nome completo',
            '{noivos}' => 'Nome completo',
        ];

        foreach ($requiredDynamicPlaceholders as $placeholder => $label) {
            if (!isset($usedPlaceholderLookup[$placeholder])) {
                continue;
            }

            if ($this->placeholderHasMissingData($site, $placeholder)) {
                $issues[] = "{$label}: {$placeholder}";
            }
        }

        if ($this->headerTextLogoHasMissingInitials($content)) {
            $issues[] = 'Iniciais do logo em texto não configuradas (Header)';
        }

        $issues = array_merge($issues, $this->collectMetaDynamicIssues($site, $content));
        $issues = array_values(array_unique($issues));

        if ($issues === []) {
            $result->addPassedCheck(
                'dynamic_data_readiness',
                'Dados dinâmicos essenciais configurados para publicação.',
                'general'
            );

            return;
        }

        $message = "Dados dinâmicos pendentes para publicação:\n• " . implode("\n• ", $issues);
        $message .= "\n\nUse o Preview para ver como o site está ficando antes de publicar.";
        $message .= "\nComplete os dados em Dados do Evento e, quando necessário, em Usuários.";

        $result->addFailedCheck(
            'dynamic_data_readiness',
            $message,
            'general'
        );
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
            $details = [];

            foreach ($lowContrast as $warning) {
                $section = (string) ($warning['section'] ?? '');
                $sectionLabel = $this->getSectionLabelForQa($section);
                $elements = $warning['elements'] ?? [];

                if (is_array($elements) && !empty($elements)) {
                    $elementsText = implode(', ', array_map(static fn ($item): string => (string) $item, $elements));
                    $details[] = "{$sectionLabel}: {$elementsText}";
                    continue;
                }

                $fallback = trim((string) ($warning['message'] ?? ''));
                if ($fallback !== '') {
                    $details[] = "{$sectionLabel}: {$fallback}";
                }
            }

            $details = array_values(array_unique($details));
            $message = "{$count} seção(ões) com contraste abaixo do recomendado";
            if ($details !== []) {
                $message .= "\n\nItens com contraste baixo:\n• " . implode("\n• ", $details);
            }

            $result->addWarningCheck(
                'wcag_contrast',
                $message,
                $lowContrast[0]['section'] ?? null
            );
        }
    }

    /**
     * Extract all dynamic placeholders found in content strings.
     *
     * @return array<int, string>
     */
    private function extractPlaceholdersFromContent(array $content): array
    {
        $placeholders = [];

        array_walk_recursive($content, function (mixed $value) use (&$placeholders): void {
            if (!is_string($value) || trim($value) === '') {
                return;
            }

            $count = preg_match_all('/\{[a-z0-9_]+\}/i', $value, $matches);
            if ($count === false || $count === 0) {
                return;
            }

            foreach ($matches[0] as $placeholder) {
                $placeholders[$placeholder] = true;
            }
        });

        return array_keys($placeholders);
    }

    private function placeholderHasMissingData(SiteLayout $site, string $placeholder): bool
    {
        $wedding = $site->wedding;
        if (!$wedding) {
            return true;
        }

        /** @var PlaceholderServiceInterface $placeholderService */
        $placeholderService = app(PlaceholderServiceInterface::class);
        $resolved = trim($placeholderService->replacePlaceholders($placeholder, $wedding));

        if ($resolved === '' || $resolved === $placeholder) {
            return true;
        }

        return in_array($resolved, ['[DATA A DEFINIR]', '[NOME A DEFINIR]'], true);
    }

    private function headerTextLogoHasMissingInitials(array $content): bool
    {
        $header = is_array($content['sections']['header'] ?? null)
            ? $content['sections']['header']
            : [];

        if (!(bool) ($header['enabled'] ?? false)) {
            return false;
        }

        $logo = is_array($header['logo'] ?? null) ? $header['logo'] : [];
        if (($logo['type'] ?? 'image') !== 'text') {
            return false;
        }

        $logoText = is_array($logo['text'] ?? null) ? $logo['text'] : [];
        $initials = is_array($logoText['initials'] ?? null) ? $logoText['initials'] : [];

        $firstInitial = trim((string) ($initials[0] ?? ''));
        $secondInitial = trim((string) ($initials[1] ?? ''));

        return $firstInitial === '' || $secondInitial === '';
    }

    /**
     * Collect dynamic issues from rendered meta fields.
     *
     * @return array<int, string>
     */
    private function collectMetaDynamicIssues(SiteLayout $site, array $content): array
    {
        $meta = is_array($content['meta'] ?? null) ? $content['meta'] : [];
        $wedding = $site->wedding;

        if (!$wedding) {
            return ['Dados do casamento indisponíveis para renderizar meta tags dinâmicas.'];
        }

        /** @var PlaceholderServiceInterface $placeholderService */
        $placeholderService = app(PlaceholderServiceInterface::class);
        $issues = [];

        foreach (['title' => 'meta.title', 'description' => 'meta.description'] as $metaKey => $label) {
            $raw = (string) ($meta[$metaKey] ?? '');
            if (trim($raw) === '') {
                continue;
            }

            $rendered = trim($placeholderService->replacePlaceholders($raw, $wedding));

            if (str_contains($rendered, '[DATA A DEFINIR]')) {
                $issues[] = "{$label} contém [DATA A DEFINIR]";
            }

            if (str_contains($rendered, '[NOME A DEFINIR]')) {
                $issues[] = "{$label} contém [NOME A DEFINIR]";
            }

            if (preg_match('/\{[a-z0-9_]+\}/i', $rendered) === 1) {
                $issues[] = "{$label} contém placeholders não resolvidos";
            }
        }

        return $issues;
    }

    /**
     * Get user-friendly section label for QA messages.
     */
    private function getSectionLabelForQa(string $section): string
    {
        return match ($section) {
            'header' => 'Cabeçalho',
            'hero' => 'Destaque',
            'saveTheDate' => 'Save the Date',
            'giftRegistry' => 'Lista de Presentes',
            'rsvp' => 'Confirme Presença',
            'photoGallery' => 'Galeria de Fotos',
            'footer' => 'Rodapé',
            default => $section !== '' ? $section : 'Seção',
        };
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
     * QA Check: RSVP readiness based on editor config + guest module data.
     */
    private function checkRsvpReadinessQA(SiteLayout $site, array $content, QAResult $result): void
    {
        $rsvpSection = $content['sections']['rsvp'] ?? [];

        if (!(bool) ($rsvpSection['enabled'] ?? false)) {
            $result->addPassedCheck(
                'rsvp_readiness',
                'Seção de confirmação desativada.',
                'rsvp'
            );

            return;
        }

        $access = is_array($rsvpSection['access'] ?? null) ? $rsvpSection['access'] : [];
        $fields = is_array($rsvpSection['fields'] ?? null) ? $rsvpSection['fields'] : [];
        $eventSelection = is_array($rsvpSection['eventSelection'] ?? null) ? $rsvpSection['eventSelection'] : [];
        $statusOptions = is_array($rsvpSection['statusOptions'] ?? null) ? $rsvpSection['statusOptions'] : [];

        $failures = [];
        $warnings = [];

        $activeEventIds = GuestEvent::withoutGlobalScopes()
            ->where('wedding_id', $site->wedding_id)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        if ($activeEventIds === []) {
            $failures[] = 'Nenhum evento RSVP ativo encontrado.';
        }

        $selectionMode = (string) ($eventSelection['mode'] ?? 'all_active');
        $selectedEventIds = collect($eventSelection['selectedEventIds'] ?? [])
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        $availableEventIds = $activeEventIds;

        if ($selectionMode === 'selected') {
            if ($selectedEventIds === []) {
                $failures[] = 'Modo de eventos específicos sem nenhum evento selecionado.';
                $availableEventIds = [];
            } else {
                $availableEventIds = array_values(array_intersect($activeEventIds, $selectedEventIds));

                if ($availableEventIds === []) {
                    $failures[] = 'Os eventos selecionados não estão ativos no módulo de convidados.';
                }
            }
        }

        $featuredEventId = $eventSelection['featuredEventId'] ?? null;
        if ($featuredEventId !== null && $featuredEventId !== '' && !in_array((string) $featuredEventId, $availableEventIds, true)) {
            $warnings[] = 'O evento principal pré-selecionado não está disponível para o formulário.';
        }

        $showConfirmed = (bool) ($statusOptions['showConfirmed'] ?? true);
        $showMaybe = (bool) ($statusOptions['showMaybe'] ?? true);
        $showDeclined = (bool) ($statusOptions['showDeclined'] ?? true);

        if (!$showConfirmed && !$showMaybe && !$showDeclined) {
            $failures[] = 'Nenhuma opção de resposta (confirmo/talvez/não vou) está habilitada.';
        }

        $collectEmail = (bool) ($fields['collectEmail'] ?? true);
        $collectPhone = (bool) ($fields['collectPhone'] ?? true);
        $requireEmail = (bool) ($fields['requireEmail'] ?? false);
        $requirePhone = (bool) ($fields['requirePhone'] ?? false);

        if ($requireEmail && !$collectEmail) {
            $failures[] = 'E-mail obrigatório está ativo, mas o campo de e-mail está oculto.';
        }

        if ($requirePhone && !$collectPhone) {
            $failures[] = 'Telefone obrigatório está ativo, mas o campo de telefone está oculto.';
        }

        $weddingAccess = $site->wedding?->settings['rsvp_access'] ?? 'open';
        $configuredAccessMode = (string) ($access['mode'] ?? 'inherit');
        $effectiveAccessMode = match ($configuredAccessMode) {
            'open', 'restricted', 'token_only' => $configuredAccessMode,
            default => ($weddingAccess === 'restricted' ? 'restricted' : 'open'),
        };

        $requireInviteToken = (bool) ($access['requireInviteToken'] ?? false) || $effectiveAccessMode === 'token_only';

        if (in_array($effectiveAccessMode, ['restricted', 'token_only'], true)) {
            $guestCount = Guest::withoutGlobalScopes()
                ->where('wedding_id', $site->wedding_id)
                ->count();

            if ($guestCount === 0) {
                $warnings[] = 'Acesso restrito/token ativo, mas não há convidados cadastrados.';
            }
        }

        if ($requireInviteToken) {
            $inviteCount = GuestInvite::withoutGlobalScopes()
                ->whereHas('household', function ($query) use ($site): void {
                    $query
                        ->withoutGlobalScopes()
                        ->where('wedding_id', $site->wedding_id);
                })
                ->count();

            if ($inviteCount === 0) {
                $warnings[] = 'Token obrigatório ativo, mas nenhum convite foi gerado.';
            }
        }

        if ($failures !== []) {
            $result->addFailedCheck(
                'rsvp_readiness',
                $this->formatChecklistIssues($failures),
                'rsvp'
            );

            return;
        }

        if ($warnings !== []) {
            $result->addWarningCheck(
                'rsvp_readiness',
                $this->formatChecklistIssues($warnings),
                'rsvp'
            );

            return;
        }

        $result->addPassedCheck(
            'rsvp_readiness',
            'Fluxo de confirmação pronto para receber respostas.',
            'rsvp'
        );
    }

    /**
     * Format QA issues as short readable bullet list.
     */
    private function formatChecklistIssues(array $issues): string
    {
        $normalized = array_values(array_filter(
            array_map(static fn ($item) => trim((string) $item), $issues),
            static fn ($item) => $item !== ''
        ));

        if ($normalized === []) {
            return '';
        }

        return '• ' . implode("\n• ", $normalized);
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
                $items = $this->getGalleryAlbumItems(is_array($album) ? $album : []);
                foreach ($items as $item) {
                    $itemUrl = $this->getGalleryItemUrl($item);
                    if (!empty($itemUrl)) {
                        $usedMediaUrls[] = $itemUrl;
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
            ->select('id', 'original_name', 'path', 'disk', 'size', 'variants')
            ->get();
        
        $totalSize = 0;
        $filesWithSize = [];
        
        foreach ($media as $item) {
            // Check if this media (or one of its variants) is actually used in the content
            $matchedPath = $this->resolveMatchedMediaPath($item, $usedMediaUrls);

            if ($matchedPath === null) {
                continue;
            }

            $fileSize = $this->resolveMediaPathSize($item, $matchedPath);

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
     * Normalize gallery album items with backward compatibility for legacy "photos".
     *
     * @return array<int, mixed>
     */
    private function getGalleryAlbumItems(array $album): array
    {
        $items = $album['items'] ?? [];

        if (!is_array($items) || empty($items)) {
            $legacyPhotos = $album['photos'] ?? [];
            if (!is_array($legacyPhotos)) {
                return [];
            }

            return $legacyPhotos;
        }

        return $items;
    }

    /**
     * Resolve gallery item type (image|video).
     */
    private function getGalleryItemType(mixed $item): string
    {
        if (!is_array($item)) {
            return 'image';
        }

        $type = strtolower((string) ($item['type'] ?? 'image'));

        return in_array($type, ['image', 'video'], true) ? $type : 'image';
    }

    /**
     * Resolve URL for a gallery item.
     */
    private function getGalleryItemUrl(mixed $item): string
    {
        if (is_string($item)) {
            return trim($item);
        }

        if (!is_array($item)) {
            return '';
        }

        $type = $this->getGalleryItemType($item);

        if ($type === 'video') {
            return trim((string) (
                $item['displayUrl']
                ?? $item['display_url']
                ?? $item['url']
                ?? ''
            ));
        }

        return trim((string) (
            $item['displayUrl']
            ?? $item['display_url']
            ?? $item['thumbnailUrl']
            ?? $item['thumbnail_url']
            ?? $item['url']
            ?? ''
        ));
    }

    /**
     * Resolve which path (original or variant) matches any used URL.
     */
    private function resolveMatchedMediaPath(mixed $media, array $usedMediaUrls): ?string
    {
        $originalPath = (string) ($media->path ?? '');
        $disk = (string) ($media->disk ?? 'public');
        $variants = is_array($media->variants ?? null) ? $media->variants : [];

        $candidatePaths = [];
        if ($originalPath !== '') {
            $candidatePaths[] = $originalPath;
        }

        foreach ($variants as $variantPath) {
            if (is_string($variantPath) && $variantPath !== '') {
                $candidatePaths[] = $variantPath;
            }
        }

        if ($candidatePaths === []) {
            return null;
        }

        $candidatePaths = array_values(array_unique($candidatePaths));

        foreach ($usedMediaUrls as $usedUrl) {
            foreach ($candidatePaths as $candidatePath) {
                if (str_contains($usedUrl, $candidatePath)) {
                    return $candidatePath;
                }

                try {
                    $candidateUrl = \Storage::disk($disk)->url($candidatePath);
                    if ($usedUrl === $candidateUrl) {
                        return $candidatePath;
                    }
                } catch (\Throwable) {
                    // Ignore URL generation failures for this candidate.
                }
            }
        }

        return null;
    }

    /**
     * Resolve file size for a matched media path.
     */
    private function resolveMediaPathSize(mixed $media, string $matchedPath): int
    {
        $originalPath = (string) ($media->path ?? '');
        $disk = (string) ($media->disk ?? 'public');

        if ($matchedPath === $originalPath) {
            $size = (int) ($media->size ?? 0);
            if ($size > 0) {
                return $size;
            }
        }

        try {
            return (int) \Storage::disk($disk)->size($matchedPath);
        } catch (\Throwable) {
            return max(0, (int) ($media->size ?? 0));
        }
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
     * Build list of header text elements that must be validated against header background.
     *
     * @return array<int, array{label: string, color: string, fontSize: mixed, fontWeight: mixed}>
     */
    private function getHeaderContrastCandidates(array $header, array $theme): array
    {
        $candidates = [];
        $themePrimaryColor = (string) ($theme['primaryColor'] ?? '#333333');

        $logo = is_array($header['logo'] ?? null) ? $header['logo'] : [];
        $logoType = (string) ($logo['type'] ?? 'image');
        $logoText = is_array($logo['text'] ?? null) ? $logo['text'] : [];
        $logoInitials = is_array($logoText['initials'] ?? null) ? $logoText['initials'] : [];
        $hasLogoInitials = array_filter(array_map(static fn ($initial): string => trim((string) $initial), $logoInitials)) !== [];

        if ($logoType === 'text' && $hasLogoInitials) {
            $logoTypography = is_array($logoText['typography'] ?? null) ? $logoText['typography'] : [];
            $candidates[] = [
                'label' => 'Logo',
                'color' => (string) ($logoTypography['fontColor'] ?? $themePrimaryColor),
                'fontSize' => $logoTypography['fontSize'] ?? 48,
                'fontWeight' => $logoTypography['fontWeight'] ?? 700,
            ];
        }

        $headerTitle = trim((string) ($header['title'] ?? ''));
        if ($headerTitle !== '') {
            $titleTypography = is_array($header['titleTypography'] ?? null) ? $header['titleTypography'] : [];
            $candidates[] = [
                'label' => 'Título',
                'color' => (string) ($titleTypography['fontColor'] ?? $themePrimaryColor),
                'fontSize' => $titleTypography['fontSize'] ?? 20,
                'fontWeight' => $titleTypography['fontWeight'] ?? 600,
            ];
        }

        $headerSubtitle = trim((string) ($header['subtitle'] ?? ''));
        if ($headerSubtitle !== '') {
            $subtitleTypography = is_array($header['subtitleTypography'] ?? null) ? $header['subtitleTypography'] : [];
            $candidates[] = [
                'label' => 'Subtítulo',
                'color' => (string) ($subtitleTypography['fontColor'] ?? '#6b7280'),
                'fontSize' => $subtitleTypography['fontSize'] ?? 14,
                'fontWeight' => $subtitleTypography['fontWeight'] ?? 400,
            ];
        }

        if ($this->headerHasVisibleNavigation($header)) {
            $menuTypography = is_array($header['menuTypography'] ?? null) ? $header['menuTypography'] : [];
            $menuHoverTypography = is_array($header['menuHoverTypography'] ?? null) ? $header['menuHoverTypography'] : [];

            $candidates[] = [
                'label' => 'Menu',
                'color' => (string) ($menuTypography['fontColor'] ?? '#374151'),
                'fontSize' => $menuTypography['fontSize'] ?? 14,
                'fontWeight' => $menuTypography['fontWeight'] ?? 400,
            ];

            $candidates[] = [
                'label' => 'Menu (hover)',
                'color' => (string) ($menuHoverTypography['fontColor'] ?? $themePrimaryColor),
                'fontSize' => $menuHoverTypography['fontSize'] ?? ($menuTypography['fontSize'] ?? 14),
                'fontWeight' => $menuHoverTypography['fontWeight'] ?? ($menuTypography['fontWeight'] ?? 500),
            ];
        }

        // Fallback for legacy configurations that may not define title/logo typography.
        if ($candidates === []) {
            $candidates[] = [
                'label' => 'Texto',
                'color' => $themePrimaryColor,
                'fontSize' => 14,
                'fontWeight' => 400,
            ];
        }

        return $candidates;
    }

    /**
     * Determine whether header has at least one visible navigation label.
     */
    private function headerHasVisibleNavigation(array $header): bool
    {
        $navigationItems = $header['navigation'] ?? [];

        if (!is_array($navigationItems)) {
            return false;
        }

        foreach ($navigationItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (!(bool) ($item['showInMenu'] ?? false)) {
                continue;
            }

            if (trim((string) ($item['label'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve effective text background for Save the Date section.
     *
     * Inline layout: text is rendered directly over section background.
     * Modal/column layout: text is rendered inside a fixed white box.
     */
    private function resolveSaveTheDateTextBackgroundColor(array $saveTheDate, array $theme): string
    {
        $style = is_array($saveTheDate['style'] ?? null) ? $saveTheDate['style'] : [];
        $layout = strtolower(trim((string) ($style['layout'] ?? 'modal')));

        if ($layout === 'inline') {
            return $this->resolveSaveTheDateSectionBackgroundColor(
                (string) ($style['backgroundColor'] ?? ''),
                $theme
            );
        }

        return '#ffffff';
    }

    /**
     * Match Save the Date runtime background normalization used by the public component.
     */
    private function resolveSaveTheDateSectionBackgroundColor(string $backgroundColor, array $theme): string
    {
        $fallback = (string) ($theme['surfaceBackgroundColor'] ?? '#f8f6f4');
        $normalized = strtolower(trim($backgroundColor));

        if ($normalized === '') {
            return $fallback;
        }

        if ($normalized === '#f8f6f4' || $normalized === '#f5f5f5') {
            return $fallback;
        }

        return $backgroundColor;
    }

    /**
     * Build list of Save the Date text elements validated against the effective text background.
     *
     * @return array<int, array{label: string, color: string, fontSize: mixed, fontWeight: mixed}>
     */
    private function getSaveTheDateContrastCandidates(array $saveTheDate, array $theme): array
    {
        $candidates = [];
        $themePrimaryColor = (string) ($theme['primaryColor'] ?? '#f97373');

        $sectionTypography = is_array($saveTheDate['sectionTypography'] ?? null) ? $saveTheDate['sectionTypography'] : [];
        $descriptionTypography = is_array($saveTheDate['descriptionTypography'] ?? null) ? $saveTheDate['descriptionTypography'] : [];
        $countdownNumbersTypography = is_array($saveTheDate['countdownNumbersTypography'] ?? null) ? $saveTheDate['countdownNumbersTypography'] : [];
        $countdownLabelsTypography = is_array($saveTheDate['countdownLabelsTypography'] ?? null) ? $saveTheDate['countdownLabelsTypography'] : [];

        // "Save the Date" heading has fixed class size (text-3xl) in public rendering.
        $candidates[] = [
            'label' => 'Título',
            'color' => (string) ($sectionTypography['fontColor'] ?? $themePrimaryColor),
            'fontSize' => 30,
            'fontWeight' => $sectionTypography['fontWeight'] ?? 700,
        ];

        // Date/location/address use the section typography styles.
        $candidates[] = [
            'label' => 'Data e Local',
            'color' => (string) ($sectionTypography['fontColor'] ?? $themePrimaryColor),
            'fontSize' => $sectionTypography['fontSize'] ?? 18,
            'fontWeight' => $sectionTypography['fontWeight'] ?? 400,
        ];

        if (trim((string) ($saveTheDate['description'] ?? '')) !== '') {
            $candidates[] = [
                'label' => 'Descrição',
                'color' => (string) ($descriptionTypography['fontColor'] ?? '#666666'),
                'fontSize' => $descriptionTypography['fontSize'] ?? 16,
                'fontWeight' => $descriptionTypography['fontWeight'] ?? 400,
            ];
        }

        if ((bool) ($saveTheDate['showCountdown'] ?? true)) {
            $candidates[] = [
                'label' => 'Contador (números)',
                'color' => (string) ($countdownNumbersTypography['fontColor'] ?? $themePrimaryColor),
                'fontSize' => $countdownNumbersTypography['fontSize'] ?? 48,
                'fontWeight' => $countdownNumbersTypography['fontWeight'] ?? 700,
            ];

            $candidates[] = [
                'label' => 'Contador (labels)',
                'color' => (string) ($countdownLabelsTypography['fontColor'] ?? '#999999'),
                'fontSize' => $countdownLabelsTypography['fontSize'] ?? 12,
                'fontWeight' => $countdownLabelsTypography['fontWeight'] ?? 400,
            ];
        }

        return $candidates;
    }

    /**
     * Resolve minimum required contrast ratio according to WCAG AA.
     */
    private function getRequiredContrastRatio(mixed $fontSize, mixed $fontWeight): float
    {
        $size = $this->parseTypographyFontSize($fontSize);
        $weight = $this->parseTypographyFontWeight($fontWeight);

        if ($size === null) {
            return self::WCAG_AA_CONTRAST_RATIO;
        }

        $isLargeText = $size >= 24 || ($size >= 18.66 && $weight >= 700);

        return $isLargeText
            ? self::WCAG_AA_LARGE_TEXT_CONTRAST_RATIO
            : self::WCAG_AA_CONTRAST_RATIO;
    }

    /**
     * Parse typography font size to pixels.
     */
    private function parseTypographyFontSize(mixed $fontSize): ?float
    {
        if (is_numeric($fontSize)) {
            return (float) $fontSize;
        }

        if (is_string($fontSize)) {
            if (preg_match('/-?\d+(?:\.\d+)?/', $fontSize, $matches) === 1) {
                return (float) $matches[0];
            }
        }

        return null;
    }

    /**
     * Parse typography weight to numeric format.
     */
    private function parseTypographyFontWeight(mixed $fontWeight): int
    {
        if (is_numeric($fontWeight)) {
            return (int) $fontWeight;
        }

        if (!is_string($fontWeight)) {
            return 400;
        }

        $normalized = strtolower(trim($fontWeight));

        if ($normalized === 'bold') {
            return 700;
        }

        if ($normalized === 'normal') {
            return 400;
        }

        return is_numeric($normalized) ? (int) $normalized : 400;
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
        $backgroundColor = $this->resolveEffectiveBackgroundColor($background);
        $foregroundColor = $this->resolveEffectiveTextColor($foreground, $backgroundColor);

        $l1 = $this->getRelativeLuminance($foregroundColor);
        $l2 = $this->getRelativeLuminance($backgroundColor);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Calculate relative luminance of a color.
     * 
     * @param array{r: int, g: int, b: int} $rgbColor
     * @return float Relative luminance (0-1)
     */
    private function getRelativeLuminance(array $rgbColor): float
    {
        $r = $this->linearize($rgbColor['r'] / 255);
        $g = $this->linearize($rgbColor['g'] / 255);
        $b = $this->linearize($rgbColor['b'] / 255);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Resolve effective text color (including alpha compositing) over background.
     */
    private function resolveEffectiveTextColor(string $foreground, array $background): array
    {
        $parsedForeground = $this->parseColor($foreground) ?? ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 1.0];

        return $this->compositeColors($parsedForeground, $background);
    }

    /**
     * Resolve effective opaque background color.
     */
    private function resolveEffectiveBackgroundColor(string $background): array
    {
        $parsedBackground = $this->parseColor($background) ?? ['r' => 255, 'g' => 255, 'b' => 255, 'a' => 1.0];

        return $this->compositeColors($parsedBackground, ['r' => 255, 'g' => 255, 'b' => 255]);
    }

    /**
     * Composite a source RGBA color over an opaque RGB destination.
     *
     * @param array{r: int, g: int, b: int, a: float} $source
     * @param array{r: int, g: int, b: int} $destination
     * @return array{r: int, g: int, b: int}
     */
    private function compositeColors(array $source, array $destination): array
    {
        $alpha = max(0.0, min(1.0, (float) ($source['a'] ?? 1.0)));
        $inverseAlpha = 1 - $alpha;

        return [
            'r' => (int) round(($source['r'] * $alpha) + ($destination['r'] * $inverseAlpha)),
            'g' => (int) round(($source['g'] * $alpha) + ($destination['g'] * $inverseAlpha)),
            'b' => (int) round(($source['b'] * $alpha) + ($destination['b'] * $inverseAlpha)),
        ];
    }

    /**
     * Parse CSS color values (hex/rgb/rgba/transparent).
     *
     * @return array{r: int, g: int, b: int, a: float}|null
     */
    private function parseColor(string $color): ?array
    {
        $normalized = strtolower(trim($color));

        if ($normalized === '') {
            return null;
        }

        if ($normalized === 'transparent') {
            return ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0.0];
        }

        if (str_starts_with($normalized, '#')) {
            return $this->parseHexColor($normalized);
        }

        if (preg_match(
            '/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([+-]?\d*\.?\d+)\s*\)$/i',
            $normalized,
            $matches
        ) === 1) {
            return [
                'r' => $this->clampRgbChannel((int) $matches[1]),
                'g' => $this->clampRgbChannel((int) $matches[2]),
                'b' => $this->clampRgbChannel((int) $matches[3]),
                'a' => $this->clampAlpha($matches[4]),
            ];
        }

        if (preg_match(
            '/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i',
            $normalized,
            $matches
        ) === 1) {
            return [
                'r' => $this->clampRgbChannel((int) $matches[1]),
                'g' => $this->clampRgbChannel((int) $matches[2]),
                'b' => $this->clampRgbChannel((int) $matches[3]),
                'a' => 1.0,
            ];
        }

        return null;
    }

    /**
     * Parse hex colors in #rgb, #rgba, #rrggbb or #rrggbbaa formats.
     *
     * @return array{r: int, g: int, b: int, a: float}|null
     */
    private function parseHexColor(string $hexColor): ?array
    {
        $hex = ltrim($hexColor, '#');

        if (strlen($hex) === 3 || strlen($hex) === 4) {
            $hex = implode('', array_map(static fn (string $char): string => $char . $char, str_split($hex)));
        }

        if (strlen($hex) !== 6 && strlen($hex) !== 8) {
            return null;
        }

        if (!ctype_xdigit($hex)) {
            return null;
        }

        $alpha = 1.0;
        if (strlen($hex) === 8) {
            $alpha = round(hexdec(substr($hex, 6, 2)) / 255, 4);
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
            'a' => $alpha,
        ];
    }

    /**
     * Clamp RGB channel to valid range.
     */
    private function clampRgbChannel(int $value): int
    {
        return max(0, min(255, $value));
    }

    /**
     * Clamp alpha to valid range.
     */
    private function clampAlpha(string $value): float
    {
        $parsed = (float) $value;
        return max(0.0, min(1.0, $parsed));
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
