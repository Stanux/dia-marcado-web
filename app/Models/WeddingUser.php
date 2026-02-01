<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class WeddingUser extends Pivot
{
    use HasUuids;

    protected $table = 'wedding_user';

    protected $casts = [
        'permissions' => 'array',
    ];
}
