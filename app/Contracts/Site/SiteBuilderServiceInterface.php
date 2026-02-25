<?php

declare(strict_types=1);

namespace App\Contracts\Site;

use App\Models\SiteLayout;
use App\Models\SiteTemplate;
use App\Models\User;
use App\Models\Wedding;

/**
 * Interface for site builder service.
 * 
 * Provides methods for creating, updating, publishing, and managing
 * wedding site layouts.
 */
interface SiteBuilderServiceInterface
{
    /**
     * Create a new site layout for a wedding.
     * 
     * Generates a unique slug based on couple names and initializes
     * the site with default content structure.
     *
     * @param Wedding $wedding The wedding to create a site for
     * @return SiteLayout The created site layout
     * @throws \InvalidArgumentException If wedding already has a site
     */
    public function create(Wedding $wedding): SiteLayout;

    /**
     * Update the draft content of a site layout.
     * 
     * Sanitizes the content, saves it to draft_content, and creates
     * a new version for history tracking.
     *
     * @param SiteLayout $site The site layout to update
     * @param array $content The new content to save
     * @param User $user The user making the update
     * @return SiteLayout The updated site layout
     */
    public function updateDraft(SiteLayout $site, array $content, User $user): SiteLayout;

    /**
     * Publish the site, making draft content publicly visible.
     * 
     * Validates the content, copies draft to published, creates a
     * published version snapshot, and dispatches notification event.
     *
     * @param SiteLayout $site The site layout to publish
     * @param User $user The user publishing the site
     * @return SiteLayout The published site layout
     * @throws \Illuminate\Validation\ValidationException If validation fails
     */
    public function publish(SiteLayout $site, User $user): SiteLayout;

    /**
     * Rollback to the last published version.
     * 
     * Restores the published_content from the most recent published
     * version snapshot.
     *
     * @param SiteLayout $site The site layout to rollback
     * @param User $user The user performing the rollback
     * @return SiteLayout The rolled back site layout
     * @throws \RuntimeException If no published version exists
     */
    public function rollback(SiteLayout $site, User $user): SiteLayout;

    /**
     * Apply a template to a site layout.
     * 
     * Merges template content with existing draft content, preserving
     * user-entered data while applying template styles and structure.
     *
     * @param SiteLayout $site The site layout to apply template to
     * @param SiteTemplate $template The template to apply
     * @param string $mode Application mode: merge or overwrite
     * @param User|null $actor User responsible for this application (for history)
     * @return SiteLayout The updated site layout
     */
    public function applyTemplate(
        SiteLayout $site,
        SiteTemplate $template,
        string $mode = 'merge',
        ?User $actor = null
    ): SiteLayout;
}
