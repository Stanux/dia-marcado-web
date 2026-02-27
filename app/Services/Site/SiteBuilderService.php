<?php

declare(strict_types=1);

namespace App\Services\Site;

use App\Contracts\Site\ContentSanitizerServiceInterface;
use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Contracts\Site\SiteValidatorServiceInterface;
use App\Contracts\Site\SiteVersionServiceInterface;
use App\Contracts\Site\SlugGeneratorServiceInterface;
use App\Events\SitePublished;
use App\Models\SiteLayout;
use App\Models\SiteTemplate;
use App\Models\SiteVersion;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service for building and managing wedding site layouts.
 * 
 * Handles site creation, draft updates, publishing, rollback,
 * and template application with proper versioning and sanitization.
 */
class SiteBuilderService implements SiteBuilderServiceInterface
{
    public function __construct(
        private readonly SlugGeneratorServiceInterface $slugGenerator,
        private readonly SiteVersionServiceInterface $versionService,
        private readonly SiteValidatorServiceInterface $validator,
        private readonly ContentSanitizerServiceInterface $sanitizer,
        private readonly TemplateWorkspaceService $templateWorkspaceService,
        private readonly TemplateMediaCloneService $templateMediaCloneService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function create(Wedding $wedding): SiteLayout
    {
        // Check if wedding already has a site (limit of 1)
        if ($wedding->siteLayout()->exists()) {
            throw new \InvalidArgumentException(
                'Este casamento já possui um site. Apenas um site é permitido por casamento.'
            );
        }

        // Generate unique slug based on couple names
        $slug = $this->slugGenerator->generate($wedding);

        // Get default content and populate with wedding data
        $defaultContent = SiteContentSchema::getDefaultContent();
        
        // Create site with populated content
        $site = SiteLayout::create([
            'wedding_id' => $wedding->id,
            'draft_content' => $defaultContent,
            'published_content' => null,
            'slug' => $slug,
            'custom_domain' => null,
            'access_token' => null,
            'is_published' => false,
            'published_at' => null,
        ]);

        return $site;
    }

    /**
     * {@inheritdoc}
     */
    public function updateDraft(SiteLayout $site, array $content, User $user, bool $createVersion = true, string $summary = 'Rascunho atualizado'): SiteLayout
    {
        // Sanitize content to prevent XSS
        $sanitizedContent = $this->sanitizer->sanitizeArray($content);
        $normalizedContent = SiteContentSchema::normalize($sanitizedContent);

        return DB::transaction(function () use ($site, $normalizedContent, $user, $createVersion, $summary) {
            // Extract and save gift registry config if present
            if (isset($normalizedContent['sections']['giftRegistry']['config'])) {
                $this->saveGiftRegistryConfig($site->wedding, $normalizedContent['sections']['giftRegistry']['config']);
            }

            // Update draft content
            $site->draft_content = $normalizedContent;
            $site->save();

            if ($createVersion) {
                // Create version for history tracking
                $this->versionService->createVersion(
                    $site,
                    $normalizedContent,
                    $user,
                    $summary
                );
            }

            $this->templateWorkspaceService->syncTemplateFromEditorSite($site, $normalizedContent);

            return $site->fresh();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function publish(SiteLayout $site, User $user): SiteLayout
    {
        $normalizedDraft = SiteContentSchema::normalize((array) ($site->draft_content ?? []));

        // Validate content before publishing
        $errors = SiteContentSchema::validate($normalizedDraft);
        
        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'content' => $errors,
            ]);
        }

        $qaResult = $this->validator->runQAChecklist($site);
        if (!$qaResult->canPublish()) {
            $failedChecks = $qaResult->getFailedChecks();
            $messages = array_values(array_filter(array_map(static function (array $check): string {
                $title = trim((string) ($check['name'] ?? ''));
                $message = trim((string) ($check['message'] ?? ''));

                if ($title === '' && $message === '') {
                    return '';
                }

                if ($title === '') {
                    return $message;
                }

                if ($message === '') {
                    return $title;
                }

                return "{$title}: {$message}";
            }, $failedChecks)));

            if ($messages === []) {
                $messages[] = 'Checklist de qualidade reprovado para publicação.';
            }

            throw ValidationException::withMessages([
                'qa' => $messages,
            ]);
        }

        return DB::transaction(function () use ($site, $user, $normalizedDraft) {
            // Copy draft to published
            $site->draft_content = $normalizedDraft;
            $site->published_content = $normalizedDraft;
            $site->is_published = true;
            $site->published_at = now();
            $site->save();

            // Create published version snapshot
            SiteVersion::create([
                'site_layout_id' => $site->id,
                'user_id' => $user->id,
                'content' => $normalizedDraft,
                'summary' => 'Site publicado',
                'is_published' => true,
            ]);

            // Dispatch event for notifications
            SitePublished::dispatch($site, $user);

            $this->templateWorkspaceService->syncTemplateFromEditorSite($site, $normalizedDraft);

            return $site->fresh();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(SiteLayout $site, User $user): SiteLayout
    {
        // Find the last published version
        $lastPublishedVersion = $this->versionService->getPublishedVersions($site)->first();

        if (!$lastPublishedVersion) {
            throw new RuntimeException(
                'Não há versão publicada anterior para restaurar.'
            );
        }

        return DB::transaction(function () use ($site, $lastPublishedVersion, $user) {
            // Restore published content from version
            $site->published_content = $lastPublishedVersion->content;
            $site->draft_content = $lastPublishedVersion->content;
            $site->save();

            // Create version recording the rollback
            $formattedDate = $lastPublishedVersion->created_at->format('d/m/Y H:i');
            
            SiteVersion::create([
                'site_layout_id' => $site->id,
                'user_id' => $user->id,
                'content' => $lastPublishedVersion->content,
                'summary' => "Rollback para versão de {$formattedDate}",
                'is_published' => true,
            ]);

            $this->templateWorkspaceService->syncTemplateFromEditorSite(
                $site,
                (array) $lastPublishedVersion->content
            );

            return $site->fresh();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function applyTemplate(
        SiteLayout $site,
        SiteTemplate $template,
        string $mode = 'merge',
        ?User $actor = null
    ): SiteLayout
    {
        $normalizedMode = strtolower(trim($mode));
        if (!in_array($normalizedMode, ['merge', 'overwrite'], true)) {
            throw new InvalidArgumentException('Modo de aplicação inválido. Use "merge" ou "overwrite".');
        }

        return DB::transaction(function () use ($site, $template, $normalizedMode, $actor) {
            $existingContent = SiteContentSchema::normalize((array) ($site->draft_content ?? []));
            $templateContent = SiteContentSchema::normalize((array) ($template->content ?? []));

            $appliedContent = $normalizedMode === 'overwrite'
                ? $templateContent
                : $this->mergeTemplateContent($existingContent, $templateContent);

            $appliedContent = SiteContentSchema::normalize(
                $this->sanitizer->sanitizeArray($appliedContent)
            );
            $appliedContent = $this->templateMediaCloneService->replicateForSite($appliedContent, $site, $template);
            $appliedContent = SiteContentSchema::normalize($appliedContent);

            // Keep gift registry model in sync if template provides section config.
            if (isset($appliedContent['sections']['giftRegistry']['config']) && is_array($appliedContent['sections']['giftRegistry']['config'])) {
                $this->saveGiftRegistryConfig($site->wedding, $appliedContent['sections']['giftRegistry']['config']);
            }

            $site->draft_content = $appliedContent;
            $site->save();

            $user = $actor ?? auth()->user() ?? $site->wedding?->users()->first();

            if ($user) {
                // Snapshot immediately before template application for one-click restore.
                $this->versionService->createVersion(
                    $site,
                    $existingContent,
                    $user,
                    "Snapshot antes do template: {$template->name}"
                );

                $this->versionService->createVersion(
                    $site,
                    $appliedContent,
                    $user,
                    "Template aplicado ({$normalizedMode}): {$template->name}"
                );
            }

            $this->templateWorkspaceService->syncTemplateFromEditorSite($site, $appliedContent);

            return $site->fresh();
        });
    }

    /**
     * Merge template content with existing content.
     *
     * Template wins for structure, order, style and textual configuration.
     * Existing site keeps user media (logo image, hero media, gallery assets).
     *
     * @param array $existing The existing draft content
     * @param array $template The template content to apply
     * @return array The merged content
     */
    private function mergeTemplateContent(array $existing, array $template): array
    {
        // Merge mode starts with template to enforce section order, styles and textual content.
        $merged = $this->mergeUnknownKeys($template, $existing);

        // Preserve user media assets in merge mode according to product rules.
        $merged = $this->preserveHeaderLogoMedia($existing, $merged);
        $merged = $this->preserveHeroMedia($existing, $merged);
        $merged = $this->preserveGalleryMedia($existing, $merged);

        return $merged;
    }

    /**
     * Keep unknown keys from existing content to avoid data loss when template schema is older.
     */
    private function mergeUnknownKeys(array $template, array $existing): array
    {
        foreach ($existing as $key => $value) {
            if (!array_key_exists($key, $template)) {
                $template[$key] = $value;
                continue;
            }

            if (
                is_array($template[$key])
                && is_array($value)
                && !array_is_list($template[$key])
                && !array_is_list($value)
            ) {
                $template[$key] = $this->mergeUnknownKeys($template[$key], $value);
            }
        }

        return $template;
    }

    /**
     * Preserve existing header logo image in merge mode when template still uses image logo.
     */
    private function preserveHeaderLogoMedia(array $existing, array $merged): array
    {
        $templateLogoType = strtolower((string) data_get($merged, 'sections.header.logo.type', 'image'));
        $existingLogoUrl = trim((string) data_get($existing, 'sections.header.logo.url', ''));

        if ($templateLogoType !== 'image' || $existingLogoUrl === '') {
            return $merged;
        }

        $merged['sections']['header']['logo']['type'] = 'image';
        $merged['sections']['header']['logo']['url'] = $existingLogoUrl;
        $merged['sections']['header']['logo']['alt'] = (string) data_get($existing, 'sections.header.logo.alt', '');

        return $merged;
    }

    /**
     * Preserve existing hero media (image/video/gallery) in merge mode.
     */
    private function preserveHeroMedia(array $existing, array $merged): array
    {
        $existingHeroMedia = data_get($existing, 'sections.hero.media');

        if (!is_array($existingHeroMedia) || !$this->hasHeroMedia($existingHeroMedia)) {
            return $merged;
        }

        $merged['sections']['hero']['media'] = $existingHeroMedia;

        return $merged;
    }

    /**
     * Preserve existing photo gallery media in merge mode.
     */
    private function preserveGalleryMedia(array $existing, array $merged): array
    {
        $existingAlbums = data_get($existing, 'sections.photoGallery.albums');

        if (!is_array($existingAlbums) || !$this->hasGalleryMedia($existingAlbums)) {
            return $merged;
        }

        $merged['sections']['photoGallery']['albums'] = $existingAlbums;

        return $merged;
    }

    private function hasHeroMedia(array $heroMedia): bool
    {
        $url = trim((string) ($heroMedia['url'] ?? ''));
        $fallback = trim((string) ($heroMedia['fallback'] ?? ''));
        $images = $heroMedia['images'] ?? [];

        return $url !== ''
            || $fallback !== ''
            || (is_array($images) && count($images) > 0);
    }

    private function hasGalleryMedia(array $albums): bool
    {
        foreach ($albums as $album) {
            if (!is_array($album)) {
                continue;
            }

            $items = $album['items'] ?? $album['photos'] ?? [];

            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if ($this->galleryItemHasMedia($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function galleryItemHasMedia(mixed $item): bool
    {
        if (is_string($item)) {
            return trim($item) !== '';
        }

        if (!is_array($item)) {
            return false;
        }

        $url = trim((string) (
            $item['displayUrl']
            ?? $item['display_url']
            ?? $item['thumbnailUrl']
            ?? $item['thumbnail_url']
            ?? $item['url']
            ?? ''
        ));

        return $url !== '';
    }

    /**
     * Save gift registry configuration to database.
     * 
     * @param Wedding $wedding The wedding
     * @param array $config The configuration data
     * @return void
     */
    private function saveGiftRegistryConfig(Wedding $wedding, array $config): void
    {
        // Extract typography data if present
        $titleFontFamily = $config['title_font_family'] ?? null;
        $titleColor = $config['title_color'] ?? null;
        $titleFontSize = $config['title_font_size'] ?? null;
        $titleStyle = $config['title_style'] ?? 'normal';
        
        \App\Models\GiftRegistryConfig::updateOrCreate(
            ['wedding_id' => $wedding->id],
            [
                'is_enabled' => true, // Always enabled if section is enabled in site
                'section_title' => $config['section_title'] ?? 'Lista de Presentes',
                'title_font_family' => $titleFontFamily,
                'title_font_size' => $titleFontSize,
                'title_color' => $titleColor,
                'title_style' => $titleStyle,
                'fee_modality' => $config['fee_modality'] ?? 'couple_pays',
            ]
        );
    }
}
