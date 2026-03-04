<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class WeddingScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * 
     * Filters queries by wedding_id based on user's access:
     * - Admin users can see all records (no filter applied)
     * - Other users see only records from the currently selected wedding context
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (!auth()->check()) {
            // Allow access when running in console/tests or for public access flows.
            if (app()->runningInConsole() || app()->runningUnitTests()) {
                return;
            }

            // No authenticated user - apply restrictive filter
            $builder->whereRaw('1 = 0');
            return;
        }

        $user = auth()->user();

        // Admin users can see all records
        if ($user->isAdmin()) {
            return;
        }

        $currentWeddingId = session('filament_wedding_id') ?? $user->current_wedding_id;

        // No explicit context: keep restrictive behavior to avoid cross-wedding leakage.
        if (! $currentWeddingId) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $hasAccess = $user->weddings()
            ->where('weddings.id', $currentWeddingId)
            ->exists();

        if (! $hasAccess) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->where($model->getTable() . '.wedding_id', $currentWeddingId);
    }
}
