<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseGiftRequest;
use App\Models\GiftItem;
use App\Models\GiftRegistryConfig;
use App\Services\FeeCalculator;
use App\Services\GiftService;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PagSeguroException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller for gift registry operations.
 * 
 * Handles listing gifts and retrieving gift details for public display.
 * 
 * @Requirements: 11.4
 */
class GiftController extends Controller
{
    public function __construct(
        private readonly GiftService $giftService,
        private readonly FeeCalculator $feeCalculator,
        private readonly PaymentService $paymentService
    ) {}

    /**
     * List all available gifts for a wedding event.
     * 
     * GET /api/events/{eventId}/gifts
     * 
     * @Requirements: 11.4
     */
    public function index(Request $request, string $eventId): JsonResponse
    {
        // Validate sort parameter
        $sortBy = $request->query('sort', 'price');
        if (!in_array($sortBy, ['price', 'price_asc', 'price_desc'])) {
            $sortBy = 'price';
        }

        // Get available gifts for the event
        $gifts = $this->giftService->getAvailableGifts($eventId, $sortBy);

        // Get gift registry config for display price calculation
        $config = GiftRegistryConfig::withoutGlobalScopes()
            ->where('wedding_id', $eventId)
            ->first();

        // Format gifts for response
        $formattedGifts = $gifts->map(function ($gift) use ($config) {
            return $this->formatGiftResponse($gift, $config);
        });

        return response()->json([
            'data' => $formattedGifts,
        ]);
    }

    /**
     * Get details of a specific gift.
     * 
     * GET /api/events/{eventId}/gifts/{giftId}
     * 
     * @Requirements: 11.4
     */
    public function show(Request $request, string $eventId, string $giftId): JsonResponse
    {
        // Validate UUID format to prevent PostgreSQL errors
        if (!$this->isValidUuid($giftId) || !$this->isValidUuid($eventId)) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Presente não encontrado.',
            ], 404);
        }

        // Find gift with multi-tenancy check
        $gift = GiftItem::withoutGlobalScopes()
            ->where('id', $giftId)
            ->where('wedding_id', $eventId)
            ->first();

        if (!$gift) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Presente não encontrado.',
            ], 404);
        }

        // Get gift registry config for display price calculation
        $config = GiftRegistryConfig::withoutGlobalScopes()
            ->where('wedding_id', $eventId)
            ->first();

        return response()->json([
            'data' => $this->formatGiftResponse($gift, $config, true),
        ]);
    }

    /**
     * Initiate gift purchase.
     * 
     * POST /api/events/{eventId}/gifts/{giftId}/purchase
     * 
     * @Requirements: 2.7, 5.1, 5.2, 5.3, 12.1, 12.2
     */
    public function purchase(PurchaseGiftRequest $request, string $eventId, string $giftId): JsonResponse
    {
        // Validate UUID format to prevent PostgreSQL errors
        if (!$this->isValidUuid($giftId) || !$this->isValidUuid($eventId)) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Presente não encontrado.',
            ], 404);
        }

        // Find gift with multi-tenancy check
        $gift = GiftItem::withoutGlobalScopes()
            ->where('id', $giftId)
            ->where('wedding_id', $eventId)
            ->first();

        if (!$gift) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Presente não encontrado.',
            ], 404);
        }

        // Validate gift availability
        if (!$this->giftService->validateGiftAvailability($giftId)) {
            return response()->json([
                'error' => 'Unavailable',
                'message' => 'Este presente não está mais disponível.',
            ], 422);
        }

        $validated = $request->validated();
        $paymentMethod = $validated['payment_method'];
        $idempotencyKey = $validated['idempotency_key'];

        try {
            // Process payment based on method
            if ($paymentMethod === 'credit_card') {
                // Process credit card payment
                $billingData = [
                    'name' => $validated['payer']['name'],
                    'email' => $validated['payer']['email'],
                    'document' => $validated['payer']['document'],
                    'installments' => $validated['installments'] ?? 1,
                ];

                // Add billing address if provided
                if (isset($validated['billing'])) {
                    $billingData['address'] = $validated['billing'];
                }

                $transaction = $this->paymentService->processCreditCardPayment(
                    $gift,
                    $validated['card_token'],
                    $billingData,
                    $idempotencyKey
                );

                return response()->json([
                    'data' => [
                        'transaction_id' => $transaction->internal_id,
                        'status' => $transaction->status,
                        'payment_method' => 'credit_card',
                        'amount' => $transaction->gross_amount,
                        'amount_formatted' => 'R$ ' . number_format($transaction->gross_amount / 100, 2, ',', '.'),
                    ],
                    'message' => 'Pagamento processado com sucesso.',
                ], 201);
            } elseif ($paymentMethod === 'pix') {
                // Process PIX payment
                $payerData = [
                    'name' => $validated['payer']['name'],
                    'email' => $validated['payer']['email'],
                    'document' => $validated['payer']['document'],
                ];

                $result = $this->paymentService->processPixPayment(
                    $gift,
                    $payerData,
                    $idempotencyKey
                );

                return response()->json([
                    'data' => [
                        'transaction_id' => $result['transaction']->internal_id,
                        'status' => $result['transaction']->status,
                        'payment_method' => 'pix',
                        'amount' => $result['transaction']->gross_amount,
                        'amount_formatted' => 'R$ ' . number_format($result['transaction']->gross_amount / 100, 2, ',', '.'),
                        'qr_code' => $result['qr_code'],
                        'qr_code_text' => $result['qr_code_text'],
                        'expires_at' => $result['expires_at'],
                    ],
                    'message' => 'QR Code PIX gerado com sucesso. Aguardando pagamento.',
                ], 201);
            }
        } catch (\InvalidArgumentException $e) {
            // Validation or business logic error
            Log::warning('Purchase validation error', [
                'gift_id' => $giftId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Validation Error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (PagSeguroException $e) {
            // Payment gateway error
            Log::error('Payment processing error', [
                'gift_id' => $giftId,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Payment Error',
                'message' => 'Erro ao processar pagamento: ' . $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            // Unexpected error
            Log::error('Unexpected purchase error', [
                'gift_id' => $giftId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Server Error',
                'message' => 'Erro inesperado ao processar compra. Por favor, tente novamente.',
            ], 500);
        }

        // Should never reach here
        return response()->json([
            'error' => 'Invalid Payment Method',
            'message' => 'Método de pagamento inválido.',
        ], 400);
    }

    /**
     * Format gift response data.
     */
    private function formatGiftResponse(GiftItem $gift, ?GiftRegistryConfig $config, bool $detailed = false): array
    {
        // Calculate display price based on fee modality
        $displayPrice = $gift->price;
        if ($config) {
            $displayPrice = $this->feeCalculator->calculateDisplayPrice(
                $gift->price,
                $config->getFeePercentage(),
                $config->fee_modality
            );
        }

        $response = [
            'id' => $gift->id,
            'name' => $gift->name,
            'description' => $gift->description,
            'photo_url' => $gift->photo_url,
            'display_price' => $displayPrice, // Price in cents
            'display_price_formatted' => 'R$ ' . number_format($displayPrice / 100, 2, ',', '.'),
            'quantity_available' => $gift->quantity_available,
            'is_available' => $gift->isAvailable(),
            'is_sold_out' => $gift->isSoldOut(),
        ];

        if ($detailed) {
            $response['quantity_sold'] = $gift->quantity_sold;
            $response['created_at'] = $gift->created_at?->toIso8601String();
        }

        return $response;
    }

    /**
     * Validate if a string is a valid UUID.
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}
