<?php

namespace Tests\Feature\Site;

use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Models\SiteTemplate;
use App\Models\TemplateCategory;
use App\Models\User;
use App\Models\Wedding;
use App\Services\Site\SiteContentSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateApplicationTest extends TestCase
{
    use RefreshDatabase;

    private SiteBuilderServiceInterface $siteBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteBuilder = app(SiteBuilderServiceInterface::class);
    }

    #[Test]
    public function merge_mode_preserves_existing_media_and_applies_template_text(): void
    {
        $wedding = Wedding::factory()->create([
            'settings' => ['plan_slug' => 'premium'],
        ]);
        $user = User::factory()->create();
        $wedding->users()->attach($user->id, ['role' => 'couple']);
        $user->update(['current_wedding_id' => $wedding->id]);

        $site = $this->siteBuilder->create($wedding);
        $content = SiteContentSchema::normalize((array) $site->draft_content);
        $content['sectionOrder'] = ['rsvp', 'hero', 'photoGallery', 'saveTheDate', 'giftRegistry'];
        $content['sections']['header']['logo']['type'] = 'image';
        $content['sections']['header']['logo']['url'] = '/storage/sites/current/logo-existente.png';
        $content['sections']['hero']['media'] = [
            'type' => 'image',
            'url' => '/storage/sites/current/hero-existente.png',
            'fallback' => '',
        ];
        $content['sections']['photoGallery']['albums']['before']['items'] = [
            ['type' => 'image', 'url' => '/storage/sites/current/galeria-existente.png'],
        ];
        $content['sections']['header']['title'] = 'Título Atual';
        $site = $this->siteBuilder->updateDraft($site, $content, $user);

        $templateContent = SiteContentSchema::getDefaultContent();
        $templateContent['sectionOrder'] = ['hero', 'saveTheDate', 'giftRegistry', 'rsvp', 'photoGallery'];
        $templateContent['sections']['header']['title'] = 'Título do Template';
        $templateContent['sections']['header']['logo'] = [
            'type' => 'image',
            'url' => '/storage/templates/logo-template.png',
            'alt' => 'Logo template',
            'text' => ['initials' => ['A', 'B'], 'connector' => '&'],
        ];
        $templateContent['sections']['hero']['media'] = [
            'type' => 'image',
            'url' => '/storage/templates/hero-template.png',
            'fallback' => '',
        ];
        $templateContent['sections']['photoGallery']['albums']['before']['items'] = [
            ['type' => 'image', 'url' => '/storage/templates/galeria-template.png'],
        ];

        $template = SiteTemplate::create([
            'wedding_id' => null,
            'name' => 'Template Merge',
            'slug' => 'template-merge',
            'description' => 'Template de teste',
            'content' => $templateContent,
            'is_public' => true,
        ]);

        $site = $this->siteBuilder->applyTemplate($site, $template, 'merge', $user);
        $draft = SiteContentSchema::normalize((array) $site->draft_content);

        $this->assertSame('Título do Template', $draft['sections']['header']['title']);
        $this->assertSame('/storage/sites/current/logo-existente.png', $draft['sections']['header']['logo']['url']);
        $this->assertSame('/storage/sites/current/hero-existente.png', $draft['sections']['hero']['media']['url']);
        $this->assertSame('/storage/sites/current/galeria-existente.png', $draft['sections']['photoGallery']['albums']['before']['items'][0]['url']);
        $this->assertSame($templateContent['sectionOrder'], $draft['sectionOrder']);

        $summaries = $site->versions()->pluck('summary')->all();
        $this->assertTrue(collect($summaries)->contains(fn ($summary) => str_starts_with((string) $summary, 'Snapshot antes do template')));
        $this->assertTrue(collect($summaries)->contains(fn ($summary) => str_contains((string) $summary, 'Template aplicado (merge)')));
    }

    #[Test]
    public function overwrite_mode_replaces_existing_media_with_template_media(): void
    {
        $wedding = Wedding::factory()->create([
            'settings' => ['plan_slug' => 'premium'],
        ]);
        $user = User::factory()->create();
        $wedding->users()->attach($user->id, ['role' => 'couple']);
        $user->update(['current_wedding_id' => $wedding->id]);

        $site = $this->siteBuilder->create($wedding);
        $content = SiteContentSchema::normalize((array) $site->draft_content);
        $content['sections']['header']['logo']['type'] = 'image';
        $content['sections']['header']['logo']['url'] = '/storage/sites/current/logo-existente.png';
        $content['sections']['hero']['media']['url'] = '/storage/sites/current/hero-existente.png';
        $content['sections']['photoGallery']['albums']['before']['items'] = [
            ['type' => 'image', 'url' => '/storage/sites/current/galeria-existente.png'],
        ];
        $site = $this->siteBuilder->updateDraft($site, $content, $user);

        $templateContent = SiteContentSchema::getDefaultContent();
        $templateContent['sections']['header']['logo']['type'] = 'image';
        $templateContent['sections']['header']['logo']['url'] = '/storage/templates/logo-template.png';
        $templateContent['sections']['hero']['media']['url'] = '/storage/templates/hero-template.png';
        $templateContent['sections']['photoGallery']['albums']['before']['items'] = [
            ['type' => 'image', 'url' => '/storage/templates/galeria-template.png'],
        ];

        $template = SiteTemplate::create([
            'wedding_id' => null,
            'name' => 'Template Overwrite',
            'slug' => 'template-overwrite',
            'description' => 'Template de overwrite',
            'content' => $templateContent,
            'is_public' => true,
        ]);

        $site = $this->siteBuilder->applyTemplate($site, $template, 'overwrite', $user);
        $draft = SiteContentSchema::normalize((array) $site->draft_content);

        $this->assertSame('/storage/templates/logo-template.png', $draft['sections']['header']['logo']['url']);
        $this->assertSame('/storage/templates/hero-template.png', $draft['sections']['hero']['media']['url']);
        $this->assertSame('/storage/templates/galeria-template.png', $draft['sections']['photoGallery']['albums']['before']['items'][0]['url']);
    }

    #[Test]
    public function overwrite_mode_clones_template_media_to_target_wedding_storage(): void
    {
        Storage::fake('public');

        $workspaceWedding = Wedding::factory()->create();
        $workspaceSite = $this->siteBuilder->create($workspaceWedding);

        $sourcePath = "sites/{$workspaceWedding->id}/media/template-hero.jpg";
        Storage::disk('public')->put($sourcePath, 'template-file');

        $sourceMedia = \App\Models\SiteMedia::withoutGlobalScopes()->create([
            'site_layout_id' => $workspaceSite->id,
            'wedding_id' => $workspaceWedding->id,
            'original_name' => 'template-hero.jpg',
            'path' => $sourcePath,
            'disk' => 'public',
            'size' => Storage::disk('public')->size($sourcePath),
            'mime_type' => 'image/jpeg',
            'variants' => [],
            'status' => \App\Models\SiteMedia::STATUS_COMPLETED,
        ]);

        $wedding = Wedding::factory()->create([
            'settings' => ['plan_slug' => 'premium'],
        ]);
        $user = User::factory()->create();
        $wedding->users()->attach($user->id, ['role' => 'couple']);
        $user->update(['current_wedding_id' => $wedding->id]);
        $site = $this->siteBuilder->create($wedding);

        $templateContent = SiteContentSchema::getDefaultContent();
        $templateContent['sections']['hero']['media']['url'] = '/storage/' . $sourcePath;
        $templateContent['sections']['header']['logo']['type'] = 'text';

        $template = SiteTemplate::create([
            'wedding_id' => null,
            'name' => 'Template com Midia',
            'slug' => 'template-com-midia',
            'description' => 'Template com mídia global',
            'content' => $templateContent,
            'is_public' => true,
        ]);

        $site = $this->siteBuilder->applyTemplate($site, $template, 'overwrite', $user);
        $draft = SiteContentSchema::normalize((array) $site->draft_content);
        $newUrl = (string) data_get($draft, 'sections.hero.media.url', '');

        $this->assertNotSame('/storage/' . $sourcePath, $newUrl);
        $this->assertStringContainsString("/storage/sites/{$wedding->id}/media/", $newUrl);

        $targetMedia = \App\Models\SiteMedia::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->where('site_layout_id', $site->id)
            ->first();

        $this->assertNotNull($targetMedia);
        $this->assertNotSame($sourceMedia->id, $targetMedia->id);
        Storage::disk('public')->assertExists((string) $targetMedia->path);
    }

    #[Test]
    public function api_blocks_template_when_plan_has_no_category_access(): void
    {
        $wedding = Wedding::factory()->create([
            'settings' => ['plan_slug' => 'basic'],
        ]);
        $user = User::factory()->create();
        $wedding->users()->attach($user->id, ['role' => 'couple']);
        $user->update(['current_wedding_id' => $wedding->id]);

        $site = $this->siteBuilder->create($wedding);

        $premiumCategory = TemplateCategory::create([
            'name' => 'Premium',
            'slug' => 'premium-api-test',
        ]);

        DB::table('plan_template_category')->insert([
            'template_category_id' => $premiumCategory->id,
            'plan_slug' => 'premium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $template = SiteTemplate::create([
            'wedding_id' => null,
            'template_category_id' => $premiumCategory->id,
            'name' => 'Template Premium',
            'slug' => 'template-premium-bloqueado',
            'description' => 'Exclusivo premium',
            'content' => SiteContentSchema::getDefaultContent(),
            'is_public' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/sites/{$site->id}/apply-template/{$template->id}", [
            'mode' => 'merge',
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'Este template não está disponível no seu plano atual.',
        ]);
    }

    #[Test]
    public function template_preview_route_renders_public_site_component(): void
    {
        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Preview Template Teste';

        $template = SiteTemplate::create([
            'wedding_id' => null,
            'name' => 'Template Preview',
            'slug' => 'template-preview-teste',
            'description' => 'Template público para preview',
            'content' => $content,
            'is_public' => true,
        ]);

        $response = $this->get("/site/template/{$template->slug}");

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Public/Site')
                ->where('isTemplatePreview', true)
                ->where('site.slug', $template->slug)
        );
    }

    #[Test]
    public function template_preview_route_exposes_apply_context_when_opened_from_editor(): void
    {
        $wedding = Wedding::factory()->create([
            'settings' => ['plan_slug' => 'premium'],
        ]);
        $user = User::factory()->create();
        $wedding->users()->attach($user->id, ['role' => 'couple']);
        $user->update(['current_wedding_id' => $wedding->id]);

        $site = $this->siteBuilder->create($wedding);

        $template = SiteTemplate::create([
            'wedding_id' => null,
            'name' => 'Template Contexto',
            'slug' => 'template-contexto-editor',
            'description' => 'Template com contexto de aplicação',
            'content' => SiteContentSchema::getDefaultContent(),
            'is_public' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get("/site/template/{$template->slug}?site={$site->id}&session=sessao123");

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Public/Site')
                ->where('templateApplyContext.enabled', true)
                ->where('templateApplyContext.site_id', $site->id)
                ->where('templateApplyContext.template_id', $template->id)
                ->where('templateApplyContext.session', 'sessao123')
        );
    }

    #[Test]
    public function template_preview_route_disables_apply_context_when_not_opened_from_editor(): void
    {
        $template = SiteTemplate::create([
            'wedding_id' => null,
            'name' => 'Template Sem Contexto',
            'slug' => 'template-sem-contexto-editor',
            'description' => 'Template sem contexto',
            'content' => SiteContentSchema::getDefaultContent(),
            'is_public' => true,
        ]);

        $response = $this->get("/site/template/{$template->slug}");

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Public/Site')
                ->where('templateApplyContext.enabled', false)
                ->where('templateApplyContext.reason', 'open_from_editor_required')
        );
    }
}
