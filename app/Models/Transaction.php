<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends WeddingScopedModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'internal_id',
        'pagseguro_transaction_id',
        'wedding_id',
        'gift_item_id',
        'original_unit_price',
        'fee_percentage',
        'fee_modality',
        'fee_amount',
        'gross_amount',
        'net_amount_couple',
        'platform_amount',
        'payment_method',
        'status',
        'error_message',
        'pagseguro_response',
        'confirmed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_unit_price' => 'integer',
            'fee_percentage' => 'decimal:2',
            'fee_amount' => 'integer',
            'gross_amount' => 'integer',
            'net_amount_couple' => 'integer',
            'platform_amount' => 'integer',
            'pagseguro_response' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the gift item for this transaction.
     */
    public function giftItem(): BelongsTo
    {
        return $this->belongsTo(GiftItem::class);
    }

    /**
     * Scope to filter only confirmed transactions.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope to filter by payment method.
     */
    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Mark the transaction as confirmed.
     */
    public function markAsConfirmed(): void
    {
        $this->status = 'confirmed';
        $this->confirmed_at = now();
        $this->save();
    }

    /**
     * Mark the transaction as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->status = 'failed';
        $this->error_message = $error;
        $this->save();
    }
}
