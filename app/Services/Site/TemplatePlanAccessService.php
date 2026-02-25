<?php

namespace App\Services\Site;

use App\Models\PlanLimit;
use App\Models\SiteTemplate;
use App\Models\TemplateCategory;
use App\Models\Wedding;

/**
 * Resolves template access rules by wedding subscription plan and category.
 */
class TemplatePlanAccessService
{
    private const DEFAULT_PLAN = PlanLimit::PLAN_BASIC;

    /**
     * Resolve the current plan slug for the wedding.
     */
    public function resolvePlanSlug(Wedding $wedding): string
    {
        if (isset($wedding->plan_slug) && is_string($wedding->plan_slug) && trim($wedding->plan_slug) !== '') {
            return trim($wedding->plan_slug);
        }

        $planSlug = $wedding->getSetting('plan_slug');
        if (is_string($planSlug) && trim($planSlug) !== '') {
            return trim($planSlug);
        }

        return self::DEFAULT_PLAN;
    }

    /**
     * Resolve allowed category IDs for a given plan.
     *
     * @return array<int>
     */
    public function allowedCategoryIdsForPlan(string $planSlug): array
    {
        return TemplateCategory::query()
            ->whereHas('allowedPlans', fn ($query) => $query->where('plan_limits.plan_slug', $planSlug))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Determine if template can be applied for current wedding plan.
     */
    public function canApplyTemplate(Wedding $wedding, SiteTemplate $template, bool $isAdmin = false): bool
    {
        if ($isAdmin) {
            return true;
        }

        // Private templates owned by wedding are always allowed.
        if ($template->wedding_id !== null && $template->wedding_id === $wedding->id) {
            return true;
        }

        // Templates without a category are available to all plans.
        if ($template->template_category_id === null) {
            return true;
        }

        $planSlug = $this->resolvePlanSlug($wedding);

        return TemplateCategory::query()
            ->whereKey($template->template_category_id)
            ->whereHas('allowedPlans', fn ($query) => $query->where('plan_limits.plan_slug', $planSlug))
            ->exists();
    }

    /**
     * Get all required plan slugs for template category.
     *
     * @return array<string>
     */
    public function requiredPlans(SiteTemplate $template): array
    {
        if ($template->template_category_id === null) {
            return [];
        }

        return TemplateCategory::query()
            ->whereKey($template->template_category_id)
            ->first()
            ?->allowedPlans()
            ->pluck('plan_limits.plan_slug')
            ->map(fn ($slug) => (string) $slug)
            ->all() ?? [];
    }
}
