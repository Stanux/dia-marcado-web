<?php

namespace App\Services;

use App\ValueObjects\PaymentAmounts;

class FeeCalculator
{
    /**
     * Calcula os valores baseado na modalidade de taxa.
     *
     * @param int $giftPrice Preço original do presente em centavos
     * @param float $feePercentage Porcentagem da taxa (ex: 0.05 para 5%)
     * @param string $feeModality Modalidade: 'couple_pays' ou 'guest_pays'
     * @return PaymentAmounts
     */
    public function calculate(
        int $giftPrice,
        float $feePercentage,
        string $feeModality
    ): PaymentAmounts {
        if ($feeModality === 'couple_pays') {
            return $this->calculateCouplePays($giftPrice, $feePercentage);
        }

        if ($feeModality === 'guest_pays') {
            return $this->calculateGuestPays($giftPrice, $feePercentage);
        }

        throw new \InvalidArgumentException("Modalidade de taxa inválida: {$feeModality}");
    }

    /**
     * Calcula valores quando os noivos pagam a taxa.
     *
     * Fórmulas:
     * - displayPrice = originalPrice
     * - grossAmount = originalPrice
     * - feeAmount = floor(grossAmount * feePercentage)
     * - netAmountCouple = grossAmount - feeAmount
     * - platformAmount = feeAmount
     *
     * @param int $originalPrice
     * @param float $feePercentage
     * @return PaymentAmounts
     */
    private function calculateCouplePays(int $originalPrice, float $feePercentage): PaymentAmounts
    {
        $displayPrice = $originalPrice;
        $grossAmount = $originalPrice;
        $feeAmount = (int) floor($grossAmount * $feePercentage);
        $netAmountCouple = $grossAmount - $feeAmount;
        $platformAmount = $feeAmount;

        return new PaymentAmounts(
            displayPrice: $displayPrice,
            grossAmount: $grossAmount,
            feeAmount: $feeAmount,
            netAmountCouple: $netAmountCouple,
            platformAmount: $platformAmount
        );
    }

    /**
     * Calcula valores quando os convidados pagam a taxa.
     *
     * Fórmulas:
     * - displayPrice = ceil(originalPrice / (1 - feePercentage))
     * - grossAmount = displayPrice
     * - netAmountCouple = originalPrice
     * - platformAmount = grossAmount - netAmountCouple
     * - feeAmount = platformAmount
     *
     * @param int $originalPrice
     * @param float $feePercentage
     * @return PaymentAmounts
     */
    private function calculateGuestPays(int $originalPrice, float $feePercentage): PaymentAmounts
    {
        // Calcula o preço majorado usando divisão e arredondamento
        $displayPrice = (int) round($originalPrice / (1 - $feePercentage));
        $grossAmount = $displayPrice;
        $netAmountCouple = $originalPrice;
        $platformAmount = $grossAmount - $netAmountCouple;
        $feeAmount = $platformAmount;

        return new PaymentAmounts(
            displayPrice: $displayPrice,
            grossAmount: $grossAmount,
            feeAmount: $feeAmount,
            netAmountCouple: $netAmountCouple,
            platformAmount: $platformAmount
        );
    }

    /**
     * Calcula o preço de exibição para convidados baseado na modalidade.
     *
     * @param int $originalPrice Preço original em centavos
     * @param float $feePercentage Porcentagem da taxa
     * @param string $feeModality Modalidade: 'couple_pays' ou 'guest_pays'
     * @return int Preço a ser exibido em centavos
     */
    public function calculateDisplayPrice(
        int $originalPrice,
        float $feePercentage,
        string $feeModality
    ): int {
        if ($feeModality === 'couple_pays') {
            return $originalPrice;
        }

        if ($feeModality === 'guest_pays') {
            return (int) round($originalPrice / (1 - $feePercentage));
        }

        throw new \InvalidArgumentException("Modalidade de taxa inválida: {$feeModality}");
    }
}
