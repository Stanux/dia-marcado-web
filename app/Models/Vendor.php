<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use App\Models\Concerns\PlanningAuditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use Blameable;
    use PlanningAuditable;

    protected $fillable = [
        'name',
        'document',
        'phone',
        'email',
        'website',
        'created_by',
        'updated_by',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(VendorCategory::class, 'vendor_category_vendor');
    }

    public function weddingVendors(): HasMany
    {
        return $this->hasMany(WeddingVendor::class);
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
