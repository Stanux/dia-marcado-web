<?php

namespace App\Models\Concerns;

use App\Models\PlanningAuditLog;
use Illuminate\Database\Eloquent\Model;

trait PlanningAuditable
{
    protected static function bootPlanningAuditable(): void
    {
        static::created(function (Model $model) {
            static::logPlanningAudit($model, 'created', $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $changes = $model->getChanges();
            $filteredChanges = static::filterAuditChanges($changes);

            if (empty($filteredChanges)) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $filteredChanges);

            static::logPlanningAudit($model, 'updated', [
                'changes' => $filteredChanges,
                'original' => $original,
            ]);
        });

        static::deleted(function (Model $model) {
            static::logPlanningAudit($model, 'deleted', $model->getAttributes());
        });
    }

    protected static function logPlanningAudit(Model $model, string $action, array $changes): void
    {
        $weddingId = $model->getAttribute('wedding_id')
            ?? auth()->user()?->current_wedding_id
            ?? session('filament_wedding_id');

        if (!$weddingId) {
            return;
        }

        PlanningAuditLog::create([
            'wedding_id' => $weddingId,
            'actor_id' => auth()->id(),
            'entity_type' => $model->getMorphClass(),
            'entity_id' => $model->getKey(),
            'action' => $action,
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }

    protected static function filterAuditChanges(array $changes): array
    {
        $ignore = [
            'created_at',
            'updated_at',
            'deleted_at',
            'created_by',
            'updated_by',
        ];

        return array_filter(
            $changes,
            fn ($key) => !in_array($key, $ignore, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
