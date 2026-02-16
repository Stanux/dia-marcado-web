<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\WeddingPlan;
use App\Services\PermissionService;
use App\Services\Planning\PlanExportService;

class PlanExportController extends Controller
{
    public function __invoke(WeddingPlan $plan, PermissionService $permissionService, PlanExportService $exportService)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        if (!$user->isAdmin() && !$permissionService->canAccess($user, 'tasks', $plan->wedding)) {
            abort(403);
        }

        return $exportService->download($plan);
    }
}
