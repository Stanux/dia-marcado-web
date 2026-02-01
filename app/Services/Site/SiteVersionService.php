<?php

namespace App\Services\Site;

use App\Contracts\Site\SiteVersionServiceInterface;
use App\Models\SiteLayout;
use App\Models\SiteVersion;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Service for managing site layout versions.
 * 
 * Handles version creation, retrieval, restoration, and pruning
 * with automatic cleanup of old versions.
 */
class SiteVersionService implements SiteVersionServiceInterface
{
    /**
     * Default maximum number of versions to keep per site.
     */
    private const DEFAULT_MAX_VERSIONS = 30;

    /**
     * Create a new version snapshot of the site content.
     *
     * @param SiteLayout $site The site layout to version
     * @param array $content The content to store in the version
     * @param User $user The user creating the version
     * @param string $summary A description of the changes
     * @return SiteVersion The created version
     */
    public function createVersion(SiteLayout $site, array $content, User $user, string $summary): SiteVersion
    {
        $version = SiteVersion::create([
            'site_layout_id' => $site->id,
            'user_id' => $user->id,
            'content' => $content,
            'summary' => $summary,
            'is_published' => false,
        ]);

        // Prune old versions to maintain the limit
        $this->pruneOldVersions($site);

        return $version;
    }

    /**
     * Get versions for a site layout, ordered by most recent first.
     *
     * @param SiteLayout $site The site layout
     * @param int|null $limit Maximum number of versions to return (null uses system config)
     * @return Collection Collection of SiteVersion models
     */
    public function getVersions(SiteLayout $site, ?int $limit = null): Collection
    {
        $maxVersions = $limit ?? $this->getMaxVersions();

        return $site->versions()
            ->orderByDesc('created_at')
            ->limit($maxVersions)
            ->get();
    }

    /**
     * Restore a site's draft content from a specific version.
     *
     * @param SiteLayout $site The site layout to restore
     * @param SiteVersion $version The version to restore from
     * @return SiteLayout The updated site layout
     */
    public function restore(SiteLayout $site, SiteVersion $version): SiteLayout
    {
        // Copy version content to draft
        $site->draft_content = $version->content;
        $site->save();

        // Create a new version recording the restoration
        $formattedDate = $version->created_at->format('d/m/Y H:i');
        
        SiteVersion::create([
            'site_layout_id' => $site->id,
            'user_id' => $version->user_id,
            'content' => $version->content,
            'summary' => "Restaurado da versão de {$formattedDate}",
            'is_published' => false,
        ]);

        return $site->fresh();
    }

    /**
     * Remove old versions exceeding the configured limit.
     * Published versions are never deleted.
     *
     * @param SiteLayout $site The site layout to prune versions for
     * @return int The number of versions deleted
     */
    public function pruneOldVersions(SiteLayout $site): int
    {
        $maxVersions = $this->getMaxVersions();
        
        // Count total versions for this site
        $totalVersions = SiteVersion::where('site_layout_id', $site->id)->count();

        if ($totalVersions <= $maxVersions) {
            return 0;
        }

        $versionsToDelete = $totalVersions - $maxVersions;

        // Get the oldest non-published versions to delete
        $oldVersions = SiteVersion::where('site_layout_id', $site->id)
            ->where('is_published', false)
            ->orderBy('created_at', 'asc')
            ->limit($versionsToDelete)
            ->get();

        $deletedCount = 0;
        foreach ($oldVersions as $version) {
            $version->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Get all published versions for a site layout.
     *
     * @param SiteLayout $site The site layout
     * @return Collection Collection of published SiteVersion models
     */
    public function getPublishedVersions(SiteLayout $site): Collection
    {
        return SiteVersion::where('site_layout_id', $site->id)
            ->where('is_published', true)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get the maximum number of versions from system config.
     *
     * @return int The maximum number of versions
     */
    private function getMaxVersions(): int
    {
        return SystemConfig::get('site.max_versions', self::DEFAULT_MAX_VERSIONS);
    }
}
