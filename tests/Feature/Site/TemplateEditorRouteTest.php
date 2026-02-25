<?php

namespace Tests\Feature\Site;

use App\Models\SiteLayout;
use App\Models\SiteTemplate;
use App\Models\User;
use App\Models\Wedding;
use App\Services\Site\SiteContentSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateEditorRouteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_route_creates_workspace_site_and_redirects_to_visual_editor(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $template = SiteTemplate::create([
            'wedding_id' => null,
            'name' => 'Template Editor Route',
            'slug' => 'template-editor-route',
            'description' => 'Template para teste de rota',
            'content' => SiteContentSchema::getDefaultContent(),
            'is_public' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('site-templates.editor', ['template' => $template->id]));

        $template->refresh();
        $this->assertNotNull($template->editor_site_layout_id);

        $workspaceWedding = Wedding::query()
            ->whereRaw("(settings->>'template_workspace')::boolean = true")
            ->first();

        $this->assertNotNull($workspaceWedding);

        $workspaceSite = SiteLayout::query()
            ->whereKey($template->editor_site_layout_id)
            ->first();

        $this->assertNotNull($workspaceSite);
        $this->assertSame($workspaceWedding->id, $workspaceSite->wedding_id);

        $expectedEditorPath = "/admin/sites/{$workspaceSite->id}/edit";
        $response->assertRedirectContains($expectedEditorPath);
        $response->assertRedirectContains("template={$template->id}");
        $response->assertRedirectContains("wedding_id={$workspaceWedding->id}");
    }
}
