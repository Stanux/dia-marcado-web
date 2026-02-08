<?php

namespace App\Http\Controllers;

use App\Services\Payment\WebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling PagSeguro webhook notifications.
 */
class WebhookController extends Controller
{
    public function __construct(
        private WebhookHandler $webhookHandler
    ) {}

    /**
     * Handle incoming PagSeguro webhook.
     *
     * POST /webhooks/pagseguro
     *
     * @param Request $request
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        try {
            $this->webhookHandler->handle($request);

            return response()->noContent();
        } catch (\InvalidArgumentException $e) {
            // Invalid signature or payload
            Log::warning('Webhook rejected', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            // Unexpected error
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Erro ao processar webhook',
            ], 500);
        }
    }
}
