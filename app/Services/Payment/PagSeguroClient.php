<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PagSeguro API Client Wrapper
 * 
 * This wrapper provides a clean interface to interact with PagSeguro's API
 * for payment processing (credit card and PIX).
 */
class PagSeguroClient
{
    private string $apiUrl;
    private string $token;
    private bool $sandbox;

    public function __construct()
    {
        $this->apiUrl = config('services.pagseguro.api_url', 'https://api.pagseguro.com');
        $this->token = config('services.pagseguro.token', '');
        $this->sandbox = config('services.pagseguro.sandbox', true);
    }

    /**
     * Create a credit card charge.
     *
     * @param array $chargeData
     * @return array
     * @throws PagSeguroException
     */
    public function createCreditCardCharge(array $chargeData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/charges", $chargeData);

            if (!$response->successful()) {
                throw new PagSeguroException(
                    'Falha ao criar cobrança no PagSeguro: ' . $response->body(),
                    $response->status()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('PagSeguro credit card charge failed', [
                'error' => $e->getMessage(),
                'charge_data' => $chargeData,
            ]);

            throw new PagSeguroException(
                'Erro ao processar pagamento com cartão de crédito: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Create a PIX order and generate QR Code.
     *
     * @param array $orderData
     * @return array Contains 'transaction_id', 'qr_code', 'qr_code_text', 'qr_code_base64'
     * @throws PagSeguroException
     */
    public function createPixOrder(array $orderData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/orders", $orderData);

            if (!$response->successful()) {
                throw new PagSeguroException(
                    'Falha ao criar cobrança PIX no PagSeguro: ' . $response->body(),
                    $response->status()
                );
            }

            $data = $response->json();

            $qrCode = $data['qr_codes'][0] ?? null;
            $qrText = $qrCode['text'] ?? null;
            $qrLinks = $qrCode['links'] ?? [];

            $base64Url = null;
            foreach ($qrLinks as $link) {
                if (($link['rel'] ?? '') === 'QRCODE.BASE64') {
                    $base64Url = $link['href'] ?? null;
                    break;
                }
            }

            $qrCodeBase64 = $this->fetchQrCodeBase64($base64Url);

            if (empty($qrText)) {
                throw new PagSeguroException('QR Code não foi gerado pelo PagSeguro');
            }

            return [
                'transaction_id' => $data['id'] ?? null,
                'qr_code' => $qrText,
                'qr_code_text' => $qrText,
                'qr_code_base64' => $qrCodeBase64,
                'expires_at' => $qrCode['expiration_date'] ?? null,
            ];
        } catch (PagSeguroException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PagSeguro PIX order failed', [
                'error' => $e->getMessage(),
                'order_data' => $orderData,
            ]);

            throw new PagSeguroException(
                'Erro ao processar pagamento PIX: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Backwards-compatible PIX charge creation (alias to createPixOrder).
     *
     * @param array $orderData
     * @return array
     * @throws PagSeguroException
     */
    public function createPixCharge(array $orderData): array
    {
        return $this->createPixOrder($orderData);
    }

    private function fetchQrCodeBase64(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
            ])->get($url);

            if (!$response->successful()) {
                return null;
            }

            $contentType = $response->header('Content-Type', '');
            if (str_contains($contentType, 'application/json')) {
                $json = $response->json();
                if (!empty($json['base64'] ?? null)) {
                    return $json['base64'];
                }
            }

            $body = trim($response->body());
            if (str_starts_with($body, 'data:image')) {
                $parts = explode(',', $body, 2);
                return $parts[1] ?? null;
            }

            return $body !== '' ? $body : null;
        } catch (\Exception $e) {
            Log::warning('PagSeguro QR Code base64 fetch failed', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            return null;
        }
    }

    /**
     * Get transaction details by ID.
     *
     * @param string $transactionId
     * @return array
     * @throws PagSeguroException
     */
    public function getTransaction(string $transactionId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
            ])->get("{$this->apiUrl}/charges/{$transactionId}");

            if (!$response->successful()) {
                throw new PagSeguroException(
                    'Falha ao buscar transação no PagSeguro',
                    $response->status()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('PagSeguro get transaction failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            throw new PagSeguroException(
                'Erro ao buscar transação: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Validate webhook signature.
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('services.pagseguro.webhook_secret', '');
        
        if (empty($secret)) {
            Log::warning('PagSeguro webhook secret not configured');
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
}
