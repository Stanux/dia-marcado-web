<?php

namespace App\Contracts\Site;

use App\Models\SiteLayout;
use App\Models\SiteVersion;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Interface for site version management service.
 * 
 * Handles version creation, retrieval, restoration, and pruning
 * for site layouts.
 */
interface SiteVersionServiceInterface
{
    /**
     * Create a new version snapshot of the site content.
     *
     * @param SiteLayout $site The site layout to version
     * @param array $content The content to store in the version
     * @param User $user The user creating the version
     * @param string $summary A description of the changes
     * @return SiteVersion The created version
     */
    public function createVersion(SiteLayout $site, array $content, User $user, string $summary): SiteVersion;

    /**
     * Get versions for a site layout, ordered by most recent first.
     *
     * @param SiteLayout $site The site layout
     * @param int|null $limit Maximum number of versions to return (null uses system config)
     * @return Collection Collection of SiteVersion models
     */
    public function getVersions(SiteLayout $site, ?int $limit = null): Collection;

    /**
     * Restore a site's draft content from a specific version.
     *
     * @param SiteLayout $site The site layout to restore
     * @param SiteVersion $version The version to restore from
     * @return SiteLayout The updated site layout
     */
    public function restore(SiteLayout $site, SiteVersion $version): SiteLayout;

    /**
     * Remove old versions exceeding the configured limit.
     * Published versions are never deleted.
     *
     * @param SiteLayout $site The site layout to prune versions for
     * @return int The number of versions deleted
     */
    public function pruneOldVersions(SiteLayout $site): int;

    /**
     * Get all published versions for a site layout.
     *
     * @param SiteLayout $site The site layout
     * @return Collection Collection of published SiteVersion models
     */
    public function getPublishedVersions(SiteLayout $site): Collection;
}
