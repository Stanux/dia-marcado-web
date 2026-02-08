<?php

namespace App\Services\Payment;

use App\Models\GiftItem;
use App\Models\GiftRegistryConfig;
use App\Models\Transaction;
use App\Services\FeeCalculator;
use App\ValueObjects\PaymentAmounts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for handling payment processing through PagSeguro.
 */
class PaymentService
{
    public function __construct(
        private PagSeguroClient $pagSeguroClient,
        private FeeCalculator $feeCalculator,
        private IdempotencyService $idempotencyService
    ) {}

    /**
     * Create a charge in PagSeguro and store transaction record.
     *
     * @param GiftItem $giftItem
     * @param string $paymentMethod 'credit_card' or 'pix'
     * @param array $paymentData Payment-specific data (card token, payer info, etc.)
     * @param string $idempotencyKey Unique key to prevent duplicate charges
     * @return Transaction
     * @throws PagSeguroException
     * @throws \InvalidArgumentException
     */
    public function createCharge(
        GiftItem $giftItem,
        string $paymentMethod,
        array $paymentData,
        string $idempotencyKey
    ): Transaction {
        // Validate single item purchase limit (Requirement 2.7)
        $this->validateSingleItemPurchase($giftItem);

        // Validate idempotency key format
        if (!$this->idempotencyService->isValidKeyFormat($idempotencyKey)) {
            throw new \InvalidArgumentException('Chave de idempotência inválida');
        }

        // Check if this idempotency key was already used
        $existingTransaction = $this->idempotencyService->getTransaction($idempotencyKey);
        if ($existingTransaction) {
            Log::info('Returning existing transaction for idempotency key', [
                'idempotency_key' => $idempotencyKey,
                'transaction_id' => $existingTransaction->id,
            ]);
            return $existingTransaction;
        }

        // Validate payment method
        if (!in_array($paymentMethod, ['credit_card', 'pix'])) {
            throw new \InvalidArgumentException('Método de pagamento inválido');
        }

        // Get gift registry config for fee calculation
        $config = GiftRegistryConfig::withoutGlobalScopes()
            ->where('wedding_id', $giftItem->wedding_id)
            ->firstOrFail();

        // Calculate payment amounts
        $amounts = $this->calculatePaymentAmounts($giftItem, $config);

        // Create transaction record with idempotency key in a single DB transaction
        $transaction = DB::transaction(function () use (
            $giftItem,
            $paymentMethod,
            $amounts,
            $config,
            $idempotencyKey
        ) {
            // Double-check idempotency within transaction (race condition protection)
            $existingTransaction = $this->idempotencyService->getTransaction($idempotencyKey);
            if ($existingTransaction) {
                return $existingTransaction;
            }

            $transaction = Transaction::create([
                'internal_id' => $this->generateInternalId(),
                'wedding_id' => $giftItem->wedding_id,
                'gift_item_id' => $giftItem->id,
                'original_unit_price' => $giftItem->price,
                'fee_percentage' => $config->getFeePercentage(),
                'fee_modality' => $config->fee_modality,
                'fee_amount' => $amounts->feeAmount,
                'gross_amount' => $amounts->grossAmount,
                'net_amount_couple' => $amounts->netAmountCouple,
                'platform_amount' => $amounts->platformAmount,
                'payment_method' => $paymentMethod,
                'status' => 'pending',
            ]);

            // Store idempotency key
            $this->idempotencyService->store(
                $idempotencyKey,
                $transaction,
                ['transaction_id' => $transaction->id]
            );

            return $transaction;
        });

        Log::info('Transaction created', [
            'transaction_id' => $transaction->id,
            'internal_id' => $transaction->internal_id,
            'payment_method' => $paymentMethod,
            'gross_amount' => $amounts->grossAmount,
        ]);

        return $transaction;
    }

    /**
     * Calculate payment amounts based on gift item and config.
     *
     * @param GiftItem $giftItem
     * @param GiftRegistryConfig $config
     * @return PaymentAmounts
     */
    public function calculatePaymentAmounts(
        GiftItem $giftItem,
        GiftRegistryConfig $config
    ): PaymentAmounts {
        return $this->feeCalculator->calculate(
            $giftItem->price,
            $config->getFeePercentage(),
            $config->fee_modality
        );
    }

    /**
     * Process credit card payment.
     *
     * @param GiftItem $giftItem
     * @param string $cardToken Tokenized card from PagSeguro client-side SDK
     * @param array $billingData Billing information (name, email, document, etc.)
     * @param string $idempotencyKey
     * @return Transaction
     * @throws PagSeguroException
     */
    public function processCreditCardPayment(
        GiftItem $giftItem,
        string $cardToken,
        array $billingData,
        string $idempotencyKey
    ): Transaction {
        // Validate gift availability
        if (!$giftItem->isAvailable()) {
            throw new \InvalidArgumentException('Presente não está disponível para compra');
        }

        // Create transaction record first
        $transaction = $this->createCharge(
            $giftItem,
            'credit_card',
            ['card_token' => $cardToken, 'billing_data' => $billingData],
            $idempotencyKey
        );

        // If transaction already existed (idempotency), return it
        if ($transaction->pagseguro_transaction_id) {
            return $transaction;
        }

        try {
            // Get config for amount calculation
            $config = GiftRegistryConfig::withoutGlobalScopes()
                ->where('wedding_id', $giftItem->wedding_id)
                ->firstOrFail();
            $amounts = $this->calculatePaymentAmounts($giftItem, $config);

            // Prepare charge data for PagSeguro
            $chargeData = [
                'reference_id' => $transaction->internal_id,
                'description' => "Presente: {$giftItem->name}",
                'amount' => [
                    'value' => $amounts->grossAmount, // Amount in cents
                    'currency' => 'BRL',
                ],
                'payment_method' => [
                    'type' => 'CREDIT_CARD',
                    'card' => [
                        'encrypted' => $cardToken, // Client-side tokenized card
                    ],
                    'installments' => $billingData['installments'] ?? 1,
                ],
                'customer' => [
                    'name' => $billingData['name'],
                    'email' => $billingData['email'],
                    'tax_id' => $billingData['document'],
                ],
            ];

            // Add notification URL if route exists
            try {
                $chargeData['notification_urls'] = [route('webhooks.pagseguro')];
            } catch (\Exception $e) {
                // Route not defined yet, skip notification URL
            }

            // Create charge in PagSeguro
            $response = $this->pagSeguroClient->createCreditCardCharge($chargeData);

            // Update transaction with PagSeguro response
            $transaction->update([
                'pagseguro_transaction_id' => $response['id'] ?? null,
                'pagseguro_response' => $response,
                'status' => $this->mapPagSeguroStatus($response['status'] ?? 'PENDING'),
            ]);

            Log::info('Credit card payment processed', [
                'transaction_id' => $transaction->id,
                'pagseguro_id' => $transaction->pagseguro_transaction_id,
                'status' => $transaction->status,
            ]);

            return $transaction->fresh();
        } catch (PagSeguroException $e) {
            // Mark transaction as failed
            $transaction->markAsFailed($e->getMessage());

            Log::error('Credit card payment failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process PIX payment and generate QR Code.
     *
     * @param GiftItem $giftItem
     * @param array $payerData Payer information (name, email, document)
     * @param string $idempotencyKey
     * @return array Contains 'transaction' and 'qr_code' data
     * @throws PagSeguroException
     */
    public function processPixPayment(
        GiftItem $giftItem,
        array $payerData,
        string $idempotencyKey
    ): array {
        // Validate gift availability
        if (!$giftItem->isAvailable()) {
            throw new \InvalidArgumentException('Presente não está disponível para compra');
        }

        // Create transaction record first
        $transaction = $this->createCharge(
            $giftItem,
            'pix',
            ['payer_data' => $payerData],
            $idempotencyKey
        );

        // If transaction already existed (idempotency), return stored QR code
        if ($transaction->pagseguro_transaction_id && $transaction->pagseguro_response) {
            $storedResponse = $transaction->pagseguro_response;
            return [
                'transaction' => $transaction,
                'qr_code' => $storedResponse['qr_code'] ?? null,
                'qr_code_text' => $storedResponse['qr_code_text'] ?? null,
                'expires_at' => $storedResponse['expires_at'] ?? null,
            ];
        }

        try {
            // Get config for amount calculation
            $config = GiftRegistryConfig::withoutGlobalScopes()
                ->where('wedding_id', $giftItem->wedding_id)
                ->firstOrFail();
            $amounts = $this->calculatePaymentAmounts($giftItem, $config);

            // Prepare charge data for PagSeguro PIX
            $chargeData = [
                'reference_id' => $transaction->internal_id,
                'description' => "Presente: {$giftItem->name}",
                'amount' => [
                    'value' => $amounts->grossAmount, // Amount in cents
                    'currency' => 'BRL',
                ],
                'payment_method' => [
                    'type' => 'PIX',
                    'pix' => [
                        'expiration_date' => now()->addMinutes(30)->toIso8601String(),
                    ],
                ],
                'customer' => [
                    'name' => $payerData['name'],
                    'email' => $payerData['email'],
                    'tax_id' => $payerData['document'],
                ],
            ];

            // Add notification URL if route exists
            try {
                $chargeData['notification_urls'] = [route('webhooks.pagseguro')];
            } catch (\Exception $e) {
                // Route not defined yet, skip notification URL
            }

            // Create PIX charge in PagSeguro
            $response = $this->pagSeguroClient->createPixCharge($chargeData);

            // Update transaction with PagSeguro response including QR code
            $transaction->update([
                'pagseguro_transaction_id' => $response['transaction_id'] ?? null,
                'pagseguro_response' => $response,
                'status' => 'pending', // PIX starts as pending until payment confirmation
            ]);

            Log::info('PIX payment created', [
                'transaction_id' => $transaction->id,
                'pagseguro_id' => $transaction->pagseguro_transaction_id,
                'qr_code_generated' => !empty($response['qr_code']),
            ]);

            return [
                'transaction' => $transaction->fresh(),
                'qr_code' => $response['qr_code'] ?? null,
                'qr_code_text' => $response['qr_code_text'] ?? null,
                'expires_at' => $response['expires_at'] ?? null,
            ];
        } catch (PagSeguroException $e) {
            // Mark transaction as failed
            $transaction->markAsFailed($e->getMessage());

            Log::error('PIX payment failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate a unique internal transaction ID.
     *
     * @return string
     */
    private function generateInternalId(): string
    {
        return 'TXN-' . strtoupper(Str::random(16));
    }

    /**
     * Map PagSeguro status to internal status.
     *
     * @param string $pagSeguroStatus
     * @return string
     */
    private function mapPagSeguroStatus(string $pagSeguroStatus): string
    {
        return match ($pagSeguroStatus) {
            'PAID', 'AUTHORIZED' => 'confirmed',
            'DECLINED', 'CANCELED' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Validate that only one item is being purchased per transaction.
     * 
     * This enforces the business rule that limits purchases to one gift item at a time.
     * 
     * @param GiftItem $giftItem The gift item being purchased
     * @throws \InvalidArgumentException If multiple items are detected
     * 
     * @Requirements: 2.7
     */
    private function validateSingleItemPurchase(GiftItem $giftItem): void
    {
        // The current implementation already enforces single item purchases
        // by accepting a single GiftItem parameter. This validation serves as
        // an explicit guard against future modifications that might attempt
        // to process multiple items.
        
        // Verify that we're processing exactly one item
        if (!$giftItem || !$giftItem->exists) {
            throw new \InvalidArgumentException('Item de presente inválido');
        }

        // Additional validation: ensure the item is a single instance
        // This prevents any potential array or collection being passed
        if (is_array($giftItem) || $giftItem instanceof \Illuminate\Support\Collection) {
            throw new \InvalidArgumentException('Apenas um item pode ser comprado por transação');
        }

        // Log the validation for audit purposes
        Log::debug('Single item purchase validation passed', [
            'gift_item_id' => $giftItem->id,
            'gift_name' => $giftItem->name,
        ]);
    }
}
