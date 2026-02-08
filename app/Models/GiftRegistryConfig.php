<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class GiftRegistryConfig extends WeddingScopedModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wedding_id',
        'is_enabled',
        'section_title',
        'title_font_family',
        'title_font_size',
        'title_color',
        'title_style',
        'fee_modality',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'title_font_size' => 'integer',
        ];
    }

    /**
     * Get the fee percentage from the wedding's plan.
     * 
     * This is a placeholder - should be implemented based on the plan system.
     */
    public function getFeePercentage(): float
    {
        // TODO: Implement based on wedding's plan
        // For now, return a default value
        return 0.05; // 5%
    }
}
