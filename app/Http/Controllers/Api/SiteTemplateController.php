<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\SiteLayout;
use App\Models\SiteTemplate;
use App\Models\Wedding;
use App\Services\Site\TemplatePlanAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Controller for managing site templates.
 * 
 * Handles listing, viewing, creating private templates,
 * and applying templates to site layouts.
 */
class SiteTemplateController extends Controller
{
    public function __construct(
        private readonly SiteBuilderServiceInterface $siteBuilder,
        private readonly TemplatePlanAccessService $templateAccess
    ) {}

    /**
     * List available templates for the current wedding.
     * 
     * Returns public templates and private templates owned by the wedding.
     * 
     * GET /api/sites/templates
     * 
     * @Requirements: 15.1
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $weddingId = $user->current_wedding_id;

        if (!$weddingId) {
            return response()->json([
                'data' => [],
                'message' => 'Nenhum casamento selecionado.',
            ]);
        }

        $wedding = Wedding::find($weddingId);
        if (!$wedding) {
            return response()->json([
                'data' => [],
                'message' => 'Casamento não encontrado.',
            ], 404);
        }

        $planSlug = $this->templateAccess->resolvePlanSlug($wedding);

        $templates = SiteTemplate::forWedding($weddingId)
            ->with('category')
            ->orderBy('is_public', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'meta' => [
                'plan_slug' => $planSlug,
            ],
            'data' => $templates->map(
                fn (SiteTemplate $template) => $this->formatTemplateResponse(
                    $template,
                    wedding: $wedding,
                    isAdmin: $user->isAdmin()
                )
            ),
        ]);
    }

    /**
     * Get template details.
     * 
     * Verifies access: template must be public or owned by the current wedding.
     * 
     * GET /api/sites/templates/{template}
     * 
     * @Requirements: 15.1
     */
    public function show(Request $request, SiteTemplate $template): JsonResponse
    {
        $user = $request->user();
        $weddingId = $user->current_wedding_id;
        $wedding = $weddingId ? Wedding::find($weddingId) : null;

        // Check access: public templates or templates owned by the wedding
        if (!$template->is_public && $template->wedding_id !== $weddingId) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Você não tem permissão para visualizar este template.',
            ], 403);
        }

        return response()->json([
            'data' => $this->formatTemplateResponse(
                $template,
                includeContent: true,
                wedding: $wedding,
                isAdmin: $user->isAdmin()
            ),
        ]);
    }

    /**
     * Create a private template for the current wedding.
     * 
     * POST /api/sites/templates
     * 
     * @Requirements: 15.4
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $weddingId = $user->current_wedding_id;

        if (!$weddingId) {
            return response()->json([
                'error' => 'Bad Request',
                'message' => 'Nenhum casamento selecionado.',
            ], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:140',
            'description' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|string|url|max:500',
            'content' => 'required|array',
            'template_category_id' => 'nullable|integer|exists:template_categories,id',
            'is_public' => 'nullable|boolean',
        ]);

        $isGlobalTemplate = $user->isAdmin() && ($validated['is_public'] ?? false) === true;

        $template = SiteTemplate::create([
            'wedding_id' => $isGlobalTemplate ? null : $weddingId,
            'template_category_id' => $validated['template_category_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'thumbnail' => $validated['thumbnail'] ?? null,
            'content' => $validated['content'],
            'is_public' => $isGlobalTemplate,
        ]);

        return response()->json([
            'data' => $this->formatTemplateResponse($template, includeContent: true),
            'message' => 'Template criado com sucesso.',
        ], 201);
    }

    /**
     * Apply a template to a site layout.
     * 
     * POST /api/sites/{site}/apply-template/{template}
     * 
     * @Requirements: 15.2, 15.3
     */
    public function apply(Request $request, SiteLayout $site, SiteTemplate $template): JsonResponse
    {
        $user = $request->user();
        $weddingId = $user->current_wedding_id;
        $wedding = Wedding::find($site->wedding_id);
        $mode = $request->validate([
            'mode' => 'nullable|in:merge,overwrite',
        ])['mode'] ?? 'merge';

        // Check authorization via policy 'update'
        if ($user->cannot('update', $site)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Você não tem permissão para atualizar este site.',
            ], 403);
        }

        // Check template access: public templates or templates owned by the wedding
        if (!$template->is_public && $template->wedding_id !== $weddingId) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Você não tem permissão para usar este template.',
            ], 403);
        }

        if (!$wedding) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Casamento não encontrado para este site.',
            ], 404);
        }

        if (!$this->templateAccess->canApplyTemplate($wedding, $template, $user->isAdmin())) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Este template não está disponível no seu plano atual.',
            ], 403);
        }

        try {
            $site = $this->siteBuilder->applyTemplate($site, $template, $mode, $user);

            return response()->json([
                'data' => $this->formatSiteResponse($site),
                'message' => "Template '{$template->name}' aplicado com sucesso ({$mode}).",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server Error',
                'message' => 'Erro ao aplicar template: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format template response data.
     */
    private function formatTemplateResponse(
        SiteTemplate $template,
        bool $includeContent = false,
        ?Wedding $wedding = null,
        bool $isAdmin = false
    ): array
    {
        $canApply = $wedding
            ? $this->templateAccess->canApplyTemplate($wedding, $template, $isAdmin)
            : true;
        $requiredPlans = $this->templateAccess->requiredPlans($template);

        $data = [
            'id' => $template->id,
            'name' => $template->name,
            'slug' => $template->slug,
            'description' => $template->description,
            'thumbnail' => $template->thumbnail,
            'is_public' => $template->is_public,
            'is_system' => $template->wedding_id === null,
            'wedding_id' => $template->wedding_id,
            'template_category_id' => $template->template_category_id,
            'category' => $template->category ? [
                'id' => $template->category->id,
                'name' => $template->category->name,
                'slug' => $template->category->slug,
            ] : null,
            'can_apply' => $canApply,
            'is_locked' => !$canApply,
            'required_plans' => $requiredPlans,
            'preview_url' => $template->slug ? route('public.site.template.preview', ['slug' => $template->slug]) : null,
            'visual_editor_url' => $isAdmin ? route('site-templates.editor', ['template' => $template->id]) : null,
            'created_at' => $template->created_at->toIso8601String(),
            'updated_at' => $template->updated_at->toIso8601String(),
        ];

        if ($includeContent) {
            $data['content'] = $template->content;
        }

        return $data;
    }

    /**
     * Format site response data.
     */
    private function formatSiteResponse(SiteLayout $site): array
    {
        return [
            'id' => $site->id,
            'wedding_id' => $site->wedding_id,
            'slug' => $site->slug,
            'custom_domain' => $site->custom_domain,
            'has_password' => $site->access_token !== null,
            'is_published' => $site->is_published,
            'is_draft' => $site->isDraft(),
            'published_at' => $site->published_at?->toIso8601String(),
            'public_url' => $site->getPublicUrl(),
            'draft_content' => $site->draft_content,
            'created_at' => $site->created_at->toIso8601String(),
            'updated_at' => $site->updated_at->toIso8601String(),
        ];
    }
}
