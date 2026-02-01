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
     * - Other users see only records from weddings they belong to
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (!auth()->check()) {
            // No authenticated user - apply restrictive filter
            $builder->whereRaw('1 = 0');
            return;
        }

        $user = auth()->user();

        // Admin users can see all records
        if ($user->isAdmin()) {
            return;
        }

        // Get wedding IDs the user has access to
        $weddingIds = $user->weddings()->pluck('weddings.id')->toArray();

        if (empty($weddingIds)) {
            // User has no weddings - show nothing
            $builder->whereRaw('1 = 0');
            return;
        }

        // Filter by user's wedding IDs
        $builder->whereIn($model->getTable() . '.wedding_id', $weddingIds);
    }
}
