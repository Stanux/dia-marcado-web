<?php

namespace App\Services\Payment;

use App\Models\IdempotencyKey;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Service to handle idempotency for payment operations.
 * 
 * Prevents duplicate payment processing by tracking idempotency keys.
 */
class IdempotencyService
{
    /**
     * Check if an idempotency key has been used before.
     *
     * @param string $key
     * @return IdempotencyKey|null
     */
    public function findByKey(string $key): ?IdempotencyKey
    {
        return IdempotencyKey::where('key', $key)
            ->notExpired()
            ->first();
    }

    /**
     * Store a new idempotency key with transaction reference.
     *
     * @param string $key
     * @param Transaction $transaction
     * @param array $response
     * @return IdempotencyKey
     */
    public function store(string $key, Transaction $transaction, array $response): IdempotencyKey
    {
        // Check if key already exists (race condition protection)
        $existing = $this->findByKey($key);
        if ($existing) {
            return $existing;
        }

        return IdempotencyKey::create([
            'key' => $key,
            'transaction_id' => $transaction->id,
            'response' => $response,
            'expires_at' => now()->addHours(24), // Keys expire after 24 hours
        ]);
    }

    /**
     * Get the transaction associated with an idempotency key.
     *
     * @param string $key
     * @return Transaction|null
     */
    public function getTransaction(string $key): ?Transaction
    {
        $idempotencyKey = $this->findByKey($key);
        
        if (!$idempotencyKey) {
            return null;
        }

        return Transaction::withoutGlobalScopes()->find($idempotencyKey->transaction_id);
    }

    /**
     * Check if a key exists and return the stored response.
     *
     * @param string $key
     * @return array|null Returns the stored response or null if key doesn't exist
     */
    public function getStoredResponse(string $key): ?array
    {
        $idempotencyKey = $this->findByKey($key);
        
        if (!$idempotencyKey) {
            return null;
        }

        return $idempotencyKey->response;
    }

    /**
     * Clean up expired idempotency keys.
     * This should be called periodically (e.g., via scheduled job).
     *
     * @return int Number of deleted keys
     */
    public function cleanupExpired(): int
    {
        return IdempotencyKey::where('expires_at', '<', now())->delete();
    }

    /**
     * Validate idempotency key format.
     *
     * @param string $key
     * @return bool
     */
    public function isValidKeyFormat(string $key): bool
    {
        // Key should be a non-empty string with reasonable length
        return !empty($key) && strlen($key) <= 100;
    }
}
