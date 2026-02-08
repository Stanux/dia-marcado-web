<?php

namespace App\ValueObjects;

class PaymentAmounts
{
    /**
     * Cria uma nova instância de PaymentAmounts.
     *
     * @param int $displayPrice Preço mostrado ao convidado (em centavos)
     * @param int $grossAmount Valor total da transação (em centavos)
     * @param int $feeAmount Valor da taxa (em centavos)
     * @param int $netAmountCouple Valor líquido para os noivos (em centavos)
     * @param int $platformAmount Valor para a plataforma (em centavos)
     */
    public function __construct(
        public readonly int $displayPrice,
        public readonly int $grossAmount,
        public readonly int $feeAmount,
        public readonly int $netAmountCouple,
        public readonly int $platformAmount
    ) {}

    /**
     * Converte o objeto para array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'display_price' => $this->displayPrice,
            'gross_amount' => $this->grossAmount,
            'fee_amount' => $this->feeAmount,
            'net_amount_couple' => $this->netAmountCouple,
            'platform_amount' => $this->platformAmount,
        ];
    }
}
