<?php

namespace Database\Factories;

use App\Models\GiftItem;
use App\Models\Transaction;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalPrice = fake()->numberBetween(1000, 50000); // R$ 10,00 a R$ 500,00
        $feePercentage = fake()->randomFloat(2, 0.03, 0.10); // 3% a 10%
        $feeModality = fake()->randomElement(['couple_pays', 'guest_pays']);
        
        // Calculate amounts based on modality
        if ($feeModality === 'couple_pays') {
            $displayPrice = $originalPrice;
            $grossAmount = $originalPrice;
            $feeAmount = (int) ($grossAmount * $feePercentage);
            $netAmountCouple = $grossAmount - $feeAmount;
            $platformAmount = $feeAmount;
        } else {
            $displayPrice = (int) ceil($originalPrice / (1 - $feePercentage));
            $grossAmount = $displayPrice;
            $feeAmount = (int) ($grossAmount * $feePercentage);
            $netAmountCouple = $originalPrice;
            $platformAmount = $grossAmount - $netAmountCouple;
        }

        return [
            'internal_id' => 'TXN-' . Str::upper(Str::random(16)),
            'pagseguro_transaction_id' => 'PS-' . Str::upper(Str::random(20)),
            'wedding_id' => Wedding::factory(),
            'gift_item_id' => GiftItem::factory(),
            'original_unit_price' => $originalPrice,
            'fee_percentage' => $feePercentage,
            'fee_modality' => $feeModality,
            'fee_amount' => $feeAmount,
            'gross_amount' => $grossAmount,
            'net_amount_couple' => $netAmountCouple,
            'platform_amount' => $platformAmount,
            'payment_method' => fake()->randomElement(['credit_card', 'pix']),
            'status' => 'pending',
            'error_message' => null,
            'pagseguro_response' => null,
            'confirmed_at' => null,
        ];
    }

    /**
     * Create a confirmed transaction.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Create a failed transaction.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->randomElement([
                'Cartão recusado',
                'Saldo insuficiente',
                'Dados inválidos',
                'Timeout na transação',
                'Erro no processamento',
            ]),
            'pagseguro_response' => [
                'error_code' => fake()->numberBetween(1000, 9999),
                'error_message' => 'Payment declined by issuer',
            ],
        ]);
    }

    /**
     * Create a refunded transaction.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'confirmed_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Create a credit card transaction.
     */
    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'credit_card',
            'pagseguro_response' => [
                'installments' => fake()->numberBetween(1, 12),
                'card_brand' => fake()->randomElement(['visa', 'mastercard', 'elo', 'amex']),
            ],
        ]);
    }

    /**
     * Create a PIX transaction.
     */
    public function pix(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'pix',
            'pagseguro_response' => [
                'qr_code' => fake()->uuid(),
                'qr_code_text' => 'PIX-' . Str::upper(Str::random(32)),
            ],
        ]);
    }

    /**
     * Create a transaction with couple_pays modality.
     */
    public function couplePays(): static
    {
        return $this->state(function (array $attributes) {
            $originalPrice = $attributes['original_unit_price'];
            $feePercentage = $attributes['fee_percentage'];
            
            $grossAmount = $originalPrice;
            $feeAmount = (int) ($grossAmount * $feePercentage);
            $netAmountCouple = $grossAmount - $feeAmount;
            $platformAmount = $feeAmount;
            
            return [
                'fee_modality' => 'couple_pays',
                'gross_amount' => $grossAmount,
                'fee_amount' => $feeAmount,
                'net_amount_couple' => $netAmountCouple,
                'platform_amount' => $platformAmount,
            ];
        });
    }

    /**
     * Create a transaction with guest_pays modality.
     */
    public function guestPays(): static
    {
        return $this->state(function (array $attributes) {
            $originalPrice = $attributes['original_unit_price'];
            $feePercentage = $attributes['fee_percentage'];
            
            $displayPrice = (int) ceil($originalPrice / (1 - $feePercentage));
            $grossAmount = $displayPrice;
            $netAmountCouple = $originalPrice;
            $platformAmount = $grossAmount - $netAmountCouple;
            $feeAmount = $platformAmount;
            
            return [
                'fee_modality' => 'guest_pays',
                'gross_amount' => $grossAmount,
                'fee_amount' => $feeAmount,
                'net_amount_couple' => $netAmountCouple,
                'platform_amount' => $platformAmount,
            ];
        });
    }

    /**
     * Create a transaction without PagSeguro ID (not yet processed).
     */
    public function notProcessed(): static
    {
        return $this->state(fn (array $attributes) => [
            'pagseguro_transaction_id' => null,
        ]);
    }
}
