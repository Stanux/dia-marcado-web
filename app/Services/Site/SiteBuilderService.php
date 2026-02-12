<?php

declare(strict_types=1);

namespace App\Services\Site;

use App\Contracts\Site\ContentSanitizerServiceInterface;
use App\Contracts\Site\SiteBuilderServiceInterface;
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
        private readonly ContentSanitizerServiceInterface $sanitizer
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
        
        // Set meta.title with couple names and date using placeholders
        if ($wedding->wedding_date) {
            $defaultContent['meta']['title'] = 'Casamento de {primeiro_nome_noivo} & {primeiro_nome_noiva} em {data_curta}';
        } else {
            $defaultContent['meta']['title'] = 'Casamento de {primeiro_nome_noivo} & {primeiro_nome_noiva}';
        }
        
        // Set meta.description with wedding date if available
        if ($wedding->wedding_date) {
            $defaultContent['meta']['description'] = "Casamento de {noivo} e {noiva} - {data}";
        } else {
            $defaultContent['meta']['description'] = "Casamento de {noivo} e {noiva}";
        }

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

        return DB::transaction(function () use ($site, $sanitizedContent, $user, $createVersion, $summary) {
            // Extract and save gift registry config if present
            if (isset($sanitizedContent['sections']['giftRegistry']['config'])) {
                $this->saveGiftRegistryConfig($site->wedding, $sanitizedContent['sections']['giftRegistry']['config']);
            }

            // Update draft content
            $site->draft_content = $sanitizedContent;
            $site->save();

            if ($createVersion) {
                // Create version for history tracking
                $this->versionService->createVersion(
                    $site,
                    $sanitizedContent,
                    $user,
                    $summary
                );
            }

            return $site->fresh();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function publish(SiteLayout $site, User $user): SiteLayout
    {
        // Auto-fill meta.title if empty
        $draftContent = $site->draft_content;
        if (empty(trim($draftContent['meta']['title'] ?? ''))) {
            $wedding = $site->wedding;
            
            // Set title with couple names and date using placeholders
            if ($wedding->wedding_date) {
                $draftContent['meta']['title'] = 'Casamento de {primeiro_nome_noivo} & {primeiro_nome_noiva} em {data_curta}';
            } else {
                $draftContent['meta']['title'] = 'Casamento de {primeiro_nome_noivo} & {primeiro_nome_noiva}';
            }
            
            // Also set description if empty
            if (empty(trim($draftContent['meta']['description'] ?? ''))) {
                if ($wedding->wedding_date) {
                    $draftContent['meta']['description'] = "Casamento de {noivo} e {noiva} - {data}";
                } else {
                    $draftContent['meta']['description'] = "Casamento de {noivo} e {noiva}";
                }
            }
            
            $site->draft_content = $draftContent;
        }
        
        // Validate content before publishing
        $errors = SiteContentSchema::validate($site->draft_content);
        
        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'content' => $errors,
            ]);
        }

        return DB::transaction(function () use ($site, $user) {
            // Copy draft to published
            $site->published_content = $site->draft_content;
            $site->is_published = true;
            $site->published_at = now();
            $site->save();

            // Create published version snapshot
            $version = SiteVersion::create([
                'site_layout_id' => $site->id,
                'user_id' => $user->id,
                'content' => $site->draft_content,
                'summary' => 'Site publicado',
                'is_published' => true,
            ]);

            // Dispatch event for notifications
            SitePublished::dispatch($site, $user);

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

            return $site->fresh();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function applyTemplate(SiteLayout $site, SiteTemplate $template): SiteLayout
    {
        return DB::transaction(function () use ($site, $template) {
            // Merge template content with existing draft
            $mergedContent = $this->mergeTemplateContent(
                $site->draft_content ?? SiteContentSchema::getDefaultContent(),
                $template->content
            );

            // Update draft with merged content
            $site->draft_content = $mergedContent;
            $site->save();

            // Get the first user from the wedding to record the version
            $wedding = $site->wedding;
            $user = $wedding->users()->first();

            if ($user) {
                // Create version recording template application
                $this->versionService->createVersion(
                    $site,
                    $mergedContent,
                    $user,
                    "Template aplicado: {$template->name}"
                );
            }

            return $site->fresh();
        });
    }

    /**
     * Merge template content with existing content, preserving user data.
     * 
     * Template provides: styles, theme, layout settings
     * Preserved from existing: user-entered text, uploaded media, personal data
     *
     * @param array $existing The existing draft content
     * @param array $template The template content to apply
     * @return array The merged content
     */
    private function mergeTemplateContent(array $existing, array $template): array
    {
        $merged = $existing;

        // Apply theme from template
        if (isset($template['theme'])) {
            $merged['theme'] = array_merge(
                $merged['theme'] ?? [],
                $template['theme']
            );
        }

        // Apply meta defaults from template (only if empty in existing)
        if (isset($template['meta'])) {
            foreach ($template['meta'] as $key => $value) {
                if (empty($merged['meta'][$key])) {
                    $merged['meta'][$key] = $value;
                }
            }
        }

        // Merge sections - apply styles from template, preserve content from existing
        if (isset($template['sections'])) {
            foreach ($template['sections'] as $sectionName => $templateSection) {
                if (!isset($merged['sections'][$sectionName])) {
                    $merged['sections'][$sectionName] = $templateSection;
                    continue;
                }

                $existingSection = $merged['sections'][$sectionName];

                // Apply style from template
                if (isset($templateSection['style'])) {
                    $existingSection['style'] = array_merge(
                        $existingSection['style'] ?? [],
                        $templateSection['style']
                    );
                }

                // Apply layout from template if exists
                if (isset($templateSection['layout'])) {
                    $existingSection['layout'] = $templateSection['layout'];
                }

                // Preserve user content fields (title, subtitle, description, etc.)
                // Only apply template values if existing is empty
                $contentFields = ['title', 'subtitle', 'description', 'copyrightText'];
                foreach ($contentFields as $field) {
                    if (isset($templateSection[$field]) && empty($existingSection[$field])) {
                        $existingSection[$field] = $templateSection[$field];
                    }
                }

                $merged['sections'][$sectionName] = $existingSection;
            }
        }

        return $merged;
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
