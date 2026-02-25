<?php

namespace App\Services\Site;

use App\Models\SiteLayout;
use App\Models\SiteTemplate;
use App\Models\Wedding;
use Illuminate\Support\Str;

/**
 * Manages the shared workspace used to visually edit global templates.
 */
class TemplateWorkspaceService
{
    private const WORKSPACE_FLAG = 'template_workspace';

    /**
     * Resolve the shared wedding workspace for template editing.
     */
    public function getOrCreateWorkspaceWedding(): Wedding
    {
        $workspace = Wedding::query()
            ->whereRaw("(settings->>'template_workspace')::boolean = true")
            ->first();

        if ($workspace) {
            return $workspace;
        }

        return Wedding::create([
            'title' => 'Workspace de Templates',
            'wedding_date' => null,
            'venue' => '',
            'city' => '',
            'state' => '',
            'settings' => [
                self::WORKSPACE_FLAG => true,
                'plan_slug' => 'premium',
            ],
            'is_active' => false,
        ]);
    }

    /**
     * Ensure a template has an editor site inside the shared workspace.
     */
    public function ensureEditorSite(SiteTemplate $template): SiteLayout
    {
        $workspace = $this->getOrCreateWorkspaceWedding();

        $existing = $template->editorSite()
            ->withoutGlobalScopes()
            ->first();

        if ($existing && $existing->wedding_id === $workspace->id) {
            return $existing;
        }

        $site = SiteLayout::create([
            'wedding_id' => $workspace->id,
            'draft_content' => SiteContentSchema::normalize((array) ($template->content ?? [])),
            'published_content' => null,
            'slug' => $this->generateUniqueEditorSlug($template, $workspace->id),
            'custom_domain' => null,
            'access_token' => null,
            'is_published' => false,
            'published_at' => null,
        ]);

        $template->forceFill([
            'editor_site_layout_id' => $site->id,
        ])->save();

        return $site;
    }

    /**
     * Push template JSON into the associated editor site.
     */
    public function syncEditorSiteFromTemplate(SiteTemplate $template): ?SiteLayout
    {
        $site = $template->editorSite()
            ->withoutGlobalScopes()
            ->first();

        if (!$site) {
            return null;
        }

        $site->draft_content = SiteContentSchema::normalize((array) ($template->content ?? []));
        $site->save();

        return $site;
    }

    /**
     * Persist editor site draft into template JSON when editing in visual mode.
     */
    public function syncTemplateFromEditorSite(SiteLayout $site, ?array $draftContent = null): ?SiteTemplate
    {
        $template = SiteTemplate::query()
            ->where('editor_site_layout_id', $site->id)
            ->first();

        if (!$template) {
            return null;
        }

        $content = SiteContentSchema::normalize(
            $draftContent ?? (array) ($site->draft_content ?? [])
        );

        $updates = [
            'content' => $content,
        ];

        if (!is_string($template->thumbnail) || trim($template->thumbnail) === '') {
            $heroThumbnail = trim((string) data_get($content, 'sections.hero.media.url', ''));
            if ($heroThumbnail !== '') {
                $updates['thumbnail'] = $heroThumbnail;
            }
        }

        $template->fill($updates)->save();

        return $template;
    }

    /**
     * Generate a unique site slug for editor workspaces.
     */
    private function generateUniqueEditorSlug(SiteTemplate $template, string $workspaceWeddingId): string
    {
        $baseSource = $template->slug ?: $template->name;
        $base = Str::slug('tpl-' . $baseSource);
        if ($base === '') {
            $base = 'tpl-template';
        }

        $slug = $base;
        $counter = 2;

        while (SiteLayout::query()
            ->where('wedding_id', $workspaceWeddingId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
