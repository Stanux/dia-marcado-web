<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use App\Models\Concerns\PlanningAuditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeddingVendor extends WeddingScopedModel
{
    use HasFactory;
    use SoftDeletes;
    use Blameable;
    use PlanningAuditable;

    protected $fillable = [
        'wedding_id',
        'vendor_id',
        'name',
        'document',
        'phone',
        'email',
        'website',
        'created_by',
        'updated_by',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(VendorCategory::class, 'vendor_category_wedding_vendor');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(TaskBudget::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function setDocumentAttribute(?string $value): void
    {
        $this->attributes['document'] = $value ? preg_replace('/\\D+/', '', $value) : null;
    }
}
