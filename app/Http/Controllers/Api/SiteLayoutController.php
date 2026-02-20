<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\Site\PlaceholderServiceInterface;
use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Contracts\Site\SiteValidatorServiceInterface;
use App\Contracts\Site\SiteVersionServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\CreateSiteRequest;
use App\Http\Requests\Site\PublishSiteRequest;
use App\Http\Requests\Site\RestoreVersionRequest;
use App\Http\Requests\Site\UpdateDraftRequest;
use App\Http\Requests\Site\UpdateSettingsRequest;
use App\Models\SiteLayout;
use App\Models\SiteVersion;
use App\Services\Site\SiteContentSchema;
use App\Models\Wedding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for managing wedding site layouts.
 * 
 * Handles CRUD operations, draft updates, publishing, versioning,
 * and preview functionality for wedding sites.
 */
class SiteLayoutController extends Controller
{
    public function __construct(
        private readonly SiteBuilderServiceInterface $siteBuilder,
        private readonly SiteVersionServiceInterface $versionService,
        private readonly SiteValidatorServiceInterface $validator,
        private readonly PlaceholderServiceInterface $placeholderService
    ) {}

    /**
     * List site for the current wedding (maximum 1).
     * 
     * GET /api/sites
     * 
     * @Requirements: 1.2
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $weddingId = $user->current_wedding_id;

        if (!$weddingId) {
            return response()->json([
                'data' => null,
                'message' => 'Nenhum casamento selecionado.',
            ]);
        }

        $site = SiteLayout::where('wedding_id', $weddingId)->first();

        if (!$site) {
            return response()->json([
                'data' => null,
                'message' => 'Nenhum site encontrado para este casamento.',
            ]);
        }

        // Check authorization
        if ($request->user()->cannot('view', $site)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Você não tem permissão para visualizar este site.',
            ], 403);
        }

        return response()->json([
            'data' => $this->formatSiteResponse($site),
        ]);
    }

    /**
     * Create a new site for the current wedding.
     * 
     * POST /api/sites
     * 
     * @Requirements: 2.3
     */
    public function store(CreateSiteRequest $request): JsonResponse
    {
        $user = $request->user();
        $weddingId = $user->current_wedding_id;

        if (!$weddingId) {
            return response()->json([
                'error' => 'Bad Request',
                'message' => 'Nenhum casamento selecionado.',
            ], 400);
        }

        $wedding = Wedding::findOrFail($weddingId);

        try {
            $site = $this->siteBuilder->create($wedding);

            return response()->json([
                'data' => $this->formatSiteResponse($site, includeContent: true),
                'message' => 'Site criado com sucesso.',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Conflict',
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Get site details.
     * 
     * GET /api/sites/{site}
     * 
     * @Requirements: 1.2
     */
    public function show(Request $request, SiteLayout $site): JsonResponse
    {
        if ($request->user()->cannot('view', $site)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Você não tem permissão para visualizar este site.',
            ], 403);
        }

        return response()->json([
            'data' => $this->formatSiteResponse($site, includeContent: true),
        ]);
    }

    /**
     * Update site draft content.
     * 
     * PUT /api/sites/{site}/draft
     * 
     * @Requirements: 3.1
     */
    public function updateDraft(UpdateDraftRequest $request, SiteLayout $site): JsonResponse
    {
        $user = $request->user();
        $content = $request->validated('content');
        $createVersion = $request->validated('create_version', true);
        $summary = $request->validated('summary', 'Rascunho atualizado');

        $site = $this->siteBuilder->updateDraft($site, $content, $user, $createVersion, $summary);

        return response()->json([
            'data' => $this->formatSiteResponse($site, includeContent: true),
            'message' => 'Rascunho atualizado com sucesso.',
        ]);
    }

    /**
     * Update site settings (slug, domain, password).
     * 
     * PUT /api/sites/{site}/settings
     * 
     * @Requirements: 5.1, 6.1
     */
    public function updateSettings(UpdateSettingsRequest $request, SiteLayout $site): JsonResponse
    {
        $validated = $request->validated();

        // Update only provided fields
        if (array_key_exists('slug', $validated) && $validated['slug'] !== null) {
            $site->slug = $validated['slug'];
        }

        if (array_key_exists('custom_domain', $validated)) {
            $site->custom_domain = $validated['custom_domain'];
        }

        if (array_key_exists('access_token', $validated)) {
            $site->access_token = $validated['access_token'];
        }

        $site->save();

        return response()->json([
            'data' => $this->formatSiteResponse($site),
            'message' => 'Configurações atualizadas com sucesso.',
        ]);
    }

    /**
     * Publish the site.
     * 
     * POST /api/sites/{site}/publish
     * 
     * @Requirements: 3.2
     */
    public function publish(PublishSiteRequest $request, SiteLayout $site): JsonResponse
    {
        $user = $request->user();

        try {
            $site = $this->siteBuilder->publish($site, $user);

            return response()->json([
                'data' => $this->formatSiteResponse($site, includeContent: true),
                'message' => 'Site publicado com sucesso.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'O site não pode ser publicado devido a erros de validação.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Rollback to the last published version.
     * 
     * POST /api/sites/{site}/rollback
     * 
     * @Requirements: 19.1
     */
    public function rollback(Request $request, SiteLayout $site): JsonResponse
    {
        $user = $request->user();

        // Check publish permission for rollback
        if ($user->cannot('publish', $site)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Você não tem permissão para fazer rollback deste site.',
            ], 403);
        }

        try {
            $site = $this->siteBuilder->rollback($site, $user);

            return response()->json([
                'data' => $this->formatSiteResponse($site, includeContent: true),
                'message' => 'Rollback realizado com sucesso.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => 'Bad Request',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * List site versions.
     * 
     * GET /api/sites/{site}/versions
     * 
     * @Requirements: 4.1
     */
    public function versions(Request $request, SiteLayout $site): JsonResponse
    {
        if ($request->user()->cannot('view', $site)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Você não tem permissão para visualizar as versões deste site.',
            ], 403);
        }

        $versions = $this->versionService->getVersions($site);

        return response()->json([
            'data' => $versions->map(fn (SiteVersion $version) => [
                'id' => $version->id,
                'user_id' => $version->user_id,
                'user_name' => $version->user?->name,
                'summary' => $version->summary,
                'is_published' => $version->is_published,
                'created_at' => $version->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Restore a specific version.
     * 
     * POST /api/sites/{site}/restore
     * 
     * @Requirements: 4.4
     */
    public function restore(RestoreVersionRequest $request, SiteLayout $site): JsonResponse
    {
        $versionId = $request->validated('version_id');
        $version = SiteVersion::findOrFail($versionId);

        // Verify version belongs to this site
        if ($version->site_layout_id !== $site->id) {
            return response()->json([
                'error' => 'Bad Request',
                'message' => 'A versão especificada não pertence a este site.',
            ], 400);
        }

        $site = $this->versionService->restore($site, $version, $request->user());

        return response()->json([
            'data' => $this->formatSiteResponse($site, includeContent: true),
            'message' => 'Versão restaurada com sucesso.',
        ]);
    }

    /**
     * Preview site with placeholders replaced.
     * 
     * GET /api/sites/{site}/preview
     * 
     * @Requirements: 7.4
     */
    public function preview(Request $request, SiteLayout $site): JsonResponse
    {
        if ($request->user()->cannot('view', $site)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Você não tem permissão para visualizar este site.',
            ], 403);
        }

        $wedding = $site->wedding;
        $content = SiteContentSchema::normalize((array) ($site->draft_content ?? []));

        // Apply placeholder replacements
        $renderedContent = $this->placeholderService->replaceInArray($content, $wedding);

        return response()->json([
            'data' => [
                'id' => $site->id,
                'slug' => $site->slug,
                'is_draft' => $site->isDraft(),
                'content' => $renderedContent,
                'public_url' => $site->getPublicUrl(),
            ],
        ]);
    }

    /**
     * Run QA checklist on the site.
     * 
     * GET /api/sites/{site}/qa
     * 
     * @Requirements: 17.5
     */
    public function qa(Request $request, SiteLayout $site): JsonResponse
    {
        if ($request->user()->cannot('view', $site)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Você não tem permissão para executar QA neste site.',
            ], 403);
        }

        $qaResult = $this->validator->runQAChecklist($site);

        return response()->json([
            'data' => [
                'passed' => $qaResult->isPassed(),
                'can_publish' => $qaResult->canPublish(),
                'checks' => $qaResult->getChecks(),
                'failed_checks' => $qaResult->getFailedChecks(),
                'warnings' => $qaResult->getWarnings(),
            ],
        ]);
    }

    /**
     * Format site response data.
     */
    private function formatSiteResponse(SiteLayout $site, bool $includeContent = false): array
    {
        $data = [
            'id' => $site->id,
            'wedding_id' => $site->wedding_id,
            'slug' => $site->slug,
            'custom_domain' => $site->custom_domain,
            'has_password' => $site->access_token !== null,
            'is_published' => $site->is_published,
            'is_draft' => $site->isDraft(),
            'published_at' => $site->published_at?->toIso8601String(),
            'public_url' => $site->getPublicUrl(),
            'created_at' => $site->created_at->toIso8601String(),
            'updated_at' => $site->updated_at->toIso8601String(),
        ];

        if ($includeContent) {
            $data['draft_content'] = is_array($site->draft_content)
                ? SiteContentSchema::normalize($site->draft_content)
                : SiteContentSchema::getDefaultContent();

            $data['published_content'] = is_array($site->published_content)
                ? SiteContentSchema::normalize($site->published_content)
                : null;
        }

        return $data;
    }
}
