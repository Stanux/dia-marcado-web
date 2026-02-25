<?php

namespace App\Http\Controllers;

use App\Models\SiteTemplate;
use App\Services\Site\TemplateWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TemplateEditorController extends Controller
{
    public function __construct(
        private readonly TemplateWorkspaceService $templateWorkspaceService
    ) {}

    /**
     * Redirect an admin user to the visual editor for a template.
     */
    public function edit(Request $request, SiteTemplate $template): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $site = $this->templateWorkspaceService->ensureEditorSite($template);
        $this->templateWorkspaceService->syncEditorSiteFromTemplate($template);

        return redirect()->route('sites.edit', [
            'site' => $site->id,
            'wedding_id' => $site->wedding_id,
            'template' => $template->id,
        ]);
    }
}
