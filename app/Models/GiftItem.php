<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'default_image_key',
        'photo_url',
        'price',
        'quantity_available',
        'quantity_sold',
        'is_enabled',
        'is_fallback_donation',
        'minimum_custom_amount',
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
            'is_fallback_donation' => 'boolean',
            'minimum_custom_amount' => 'integer',
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
        return $query->where('is_enabled', true)
            ->where(function ($innerQuery) {
                $innerQuery
                    ->where('is_fallback_donation', true)
                    ->orWhere('quantity_available', '>', 0);
            });
    }

    /**
     * Scope to filter only enabled gifts.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope to exclude the system fallback donation item.
     */
    public function scopeRegular($query)
    {
        return $query->where('is_fallback_donation', false);
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
        if (!$this->is_enabled) {
            return false;
        }

        if ($this->is_fallback_donation) {
            return true;
        }

        return $this->quantity_available > 0;
    }

    /**
     * Check if the gift is sold out.
     */
    public function isSoldOut(): bool
    {
        if ($this->is_fallback_donation) {
            return false;
        }

        return $this->quantity_available <= 0;
    }

    /**
     * Get the total number of quotas for this item.
     */
    public function getQuotaTotal(): int
    {
        return max(0, $this->quantity_available + $this->quantity_sold);
    }

    /**
     * Get unit quota amount from total target amount.
     */
    public function getQuotaUnitPrice(): int
    {
        $quotaTotal = $this->getQuotaTotal();

        if ($quotaTotal <= 0) {
            return $this->price;
        }

        return (int) round($this->price / $quotaTotal);
    }

    /**
     * Get progress percentage for quota mode.
     */
    public function getQuotaProgressPercent(): int
    {
        $quotaTotal = $this->getQuotaTotal();

        if ($quotaTotal <= 0) {
            return 0;
        }

        return (int) round(($this->quantity_sold / $quotaTotal) * 100);
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
        $this->photo_url = null;
        $this->save();
    }

    /**
     * Get the resolved image URL for display (custom overrides default).
     */
    public function getDisplayPhotoUrl(): ?string
    {
        if (!empty($this->photo_url)) {
            return $this->photo_url;
        }

        if (!empty($this->default_image_key)) {
            return '/images/gifts/' . ltrim($this->default_image_key, '/');
        }

        return null;
    }

    /**
     * Decrement the quantity available.
     */
    public function decrementQuantity(int $quantity = 1): void
    {
        if ($this->is_fallback_donation) {
            return;
        }

        $quantity = max(1, $quantity);

        if ($this->quantity_available < $quantity) {
            throw new \InvalidArgumentException('Quantidade solicitada indisponível para este presente.');
        }

        $this->decrement('quantity_available', $quantity);
        $this->increment('quantity_sold', $quantity);
        
        // Mark as sold out if quantity reaches zero
        if ($this->quantity_available <= 0) {
            $this->is_enabled = false;
            $this->save();
        }
    }
}
