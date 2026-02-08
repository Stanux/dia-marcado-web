<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftItem extends WeddingScopedModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wedding_id',
        'name',
        'description',
        'photo_url',
        'price',
        'quantity_available',
        'quantity_sold',
        'is_enabled',
        'original_name',
        'original_description',
        'original_price',
        'original_quantity',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity_available' => 'integer',
            'quantity_sold' => 'integer',
            'original_price' => 'integer',
            'original_quantity' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * Get the transactions for this gift item.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope to filter only available gifts.
     */
    public function scopeAvailable($query)
    {
        return $query->where('quantity_available', '>', 0)
            ->where('is_enabled', true);
    }

    /**
     * Scope to filter only enabled gifts.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Get the display price in reais (float).
     */
    public function getDisplayPrice(): float
    {
        return $this->price / 100;
    }

    /**
     * Check if the gift is available for purchase.
     */
    public function isAvailable(): bool
    {
        return $this->is_enabled && $this->quantity_available > 0;
    }

    /**
     * Check if the gift is sold out.
     */
    public function isSoldOut(): bool
    {
        return $this->quantity_available <= 0;
    }

    /**
     * Restore the gift item to its original values.
     */
    public function restoreOriginal(): void
    {
        $this->name = $this->original_name;
        $this->description = $this->original_description;
        $this->price = $this->original_price;
        $this->quantity_available = $this->original_quantity;
        $this->save();
    }

    /**
     * Decrement the quantity available.
     */
    public function decrementQuantity(): void
    {
        $this->decrement('quantity_available');
        $this->increment('quantity_sold');
        
        // Mark as sold out if quantity reaches zero
        if ($this->quantity_available <= 0) {
            $this->is_enabled = false;
            $this->save();
        }
    }
}
