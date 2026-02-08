<?php

namespace App\Services\Payment;

use App\Models\Transaction;
use App\Services\GiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles PagSeguro webhook notifications for payment confirmations.
 */
class WebhookHandler
{
    public function __construct(
        private GiftService $giftService,
        private PagSeguroSignatureValidator $signatureValidator
    ) {}

    /**
     * Process incoming webhook from PagSeguro.
     *
     * @param Request $request
     * @return void
     * @throws \InvalidArgumentException
     */
    public function handle(Request $request): void
    {
        $payload = $request->getContent();
        $signature = $request->header('X-PagSeguro-Signature', '');

        // Validate webhook signature
        if (!$this->validateSignature($payload, $signature)) {
            Log::warning('Webhook rejected: invalid signature');
            throw new \InvalidArgumentException('Assinatura de webhook inválida');
        }

        $webhookData = json_decode($payload, true);

        if (!$webhookData) {
            Log::error('Webhook rejected: invalid JSON payload');
            throw new \InvalidArgumentException('Payload de webhook inválido');
        }

        // Extract event type and transaction data
        $eventType = $webhookData['event_type'] ?? null;
        $chargeData = $webhookData['data'] ?? [];

        Log::info('Webhook received', [
            'event_type' => $eventType,
            'charge_id' => $chargeData['id'] ?? null,
            'status' => $chargeData['status'] ?? null,
        ]);

        // Process based on event type
        try {
            match ($eventType) {
                'CHARGE.PAID', 'CHARGE.AUTHORIZED' => $this->handlePaymentConfirmed($chargeData),
                'CHARGE.DECLINED', 'CHARGE.CANCELED' => $this->handlePaymentFailed($chargeData),
                default => Log::info('Webhook event type not handled', ['event_type' => $eventType]),
            };
        } catch (\Exception $e) {
            $this->handleWebhookError($e, $webhookData);
            throw $e;
        }
    }

    /**
     * Validate webhook signature.
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    private function validateSignature(string $payload, string $signature): bool
    {
        return $this->signatureValidator->validate($payload, $signature);
    }

    /**
     * Handle payment confirmation webhook.
     *
     * @param array $chargeData
     * @return void
     */
    private function handlePaymentConfirmed(array $chargeData): void
    {
        $pagSeguroTransactionId = $chargeData['id'] ?? null;

        if (!$pagSeguroTransactionId) {
            Log::error('Payment confirmation webhook missing transaction ID');
            return;
        }

        // Find transaction by PagSeguro ID
        $transaction = Transaction::withoutGlobalScopes()
            ->where('pagseguro_transaction_id', $pagSeguroTransactionId)
            ->first();

        if (!$transaction) {
            Log::warning('Transaction not found for webhook', [
                'pagseguro_transaction_id' => $pagSeguroTransactionId,
            ]);
            return;
        }

        // Check idempotency - if already confirmed, skip processing
        if ($this->ensureIdempotency($transaction)) {
            Log::info('Webhook already processed (idempotent)', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
            ]);
            return;
        }

        // Process confirmation in a database transaction
        DB::transaction(function () use ($transaction, $chargeData) {
            // Update transaction status to confirmed
            $transaction->markAsConfirmed();

            // Update PagSeguro response data
            $transaction->update([
                'pagseguro_response' => array_merge(
                    $transaction->pagseguro_response ?? [],
                    ['webhook_data' => $chargeData]
                ),
            ]);

            // Decrement gift item quantity
            $this->giftService->decrementGiftQuantity($transaction->gift_item_id);

            Log::info('Payment confirmed via webhook', [
                'transaction_id' => $transaction->id,
                'internal_id' => $transaction->internal_id,
                'gift_item_id' => $transaction->gift_item_id,
            ]);
        });
    }

    /**
     * Handle payment failure webhook.
     *
     * @param array $chargeData
     * @return void
     */
    private function handlePaymentFailed(array $chargeData): void
    {
        $pagSeguroTransactionId = $chargeData['id'] ?? null;

        if (!$pagSeguroTransactionId) {
            Log::error('Payment failure webhook missing transaction ID');
            return;
        }

        // Find transaction by PagSeguro ID
        $transaction = Transaction::withoutGlobalScopes()
            ->where('pagseguro_transaction_id', $pagSeguroTransactionId)
            ->first();

        if (!$transaction) {
            Log::warning('Transaction not found for failure webhook', [
                'pagseguro_transaction_id' => $pagSeguroTransactionId,
            ]);
            return;
        }

        // Mark transaction as failed
        $errorMessage = $chargeData['error_message'] ?? 'Pagamento recusado pelo PagSeguro';
        $transaction->markAsFailed($errorMessage);

        Log::info('Payment failed via webhook', [
            'transaction_id' => $transaction->id,
            'error' => $errorMessage,
        ]);
    }

    /**
     * Ensure idempotency - check if webhook was already processed.
     *
     * @param Transaction $transaction
     * @return bool True if already processed, false otherwise
     */
    private function ensureIdempotency(Transaction $transaction): bool
    {
        // If transaction is already confirmed, it was already processed
        return $transaction->status === 'confirmed' && $transaction->confirmed_at !== null;
    }

    /**
     * Handle webhook processing errors.
     *
     * @param \Exception $exception
     * @param array $webhookData
     * @return void
     */
    private function handleWebhookError(\Exception $exception, array $webhookData): void
    {
        Log::error('Webhook processing error', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'webhook_data' => $webhookData,
        ]);
    }
}
