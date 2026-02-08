<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;

/**
 * Validates PagSeguro webhook signatures to ensure authenticity.
 */
class PagSeguroSignatureValidator
{
    private string $webhookSecret;

    public function __construct()
    {
        $this->webhookSecret = config('services.pagseguro.webhook_secret', '');
    }

    /**
     * Validate webhook signature.
     *
     * @param string $payload Raw request body
     * @param string $signature Signature from request header
     * @return bool
     */
    public function validate(string $payload, string $signature): bool
    {
        if (empty($this->webhookSecret)) {
            Log::warning('PagSeguro webhook secret not configured');
            return false;
        }

        if (empty($signature)) {
            Log::warning('PagSeguro webhook signature is empty');
            return false;
        }

        $expectedSignature = $this->generateSignature($payload);
        
        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            Log::warning('PagSeguro webhook signature validation failed', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);
        }

        return $isValid;
    }

    /**
     * Generate signature for payload.
     *
     * @param string $payload
     * @return string
     */
    private function generateSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->webhookSecret);
    }
}
