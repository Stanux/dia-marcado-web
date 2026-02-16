<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait Blameable
{
    protected static function bootBlameable(): void
    {
        static::creating(function (Model $model) {
            if (!auth()->check()) {
                return;
            }

            $userId = auth()->id();

            if (in_array('created_by', $model->getFillable(), true)) {
                $model->setAttribute('created_by', $model->getAttribute('created_by') ?? $userId);
            }

            if (in_array('updated_by', $model->getFillable(), true)) {
                $model->setAttribute('updated_by', $model->getAttribute('updated_by') ?? $userId);
            }
        });

        static::updating(function (Model $model) {
            if (!auth()->check()) {
                return;
            }

            $userId = auth()->id();

            if (in_array('updated_by', $model->getFillable(), true)) {
                $model->setAttribute('updated_by', $userId);
            }
        });
    }
}
