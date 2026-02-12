<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\GiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DevPaymentController extends Controller
{
    public function confirm(string $internalId, GiftService $giftService): JsonResponse
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        $transaction = Transaction::withoutGlobalScopes()
            ->where('internal_id', $internalId)
            ->firstOrFail();

        if ($transaction->status === 'confirmed') {
            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->internal_id,
                'status' => $transaction->status,
            ]);
        }

        DB::transaction(function () use ($transaction, $giftService) {
            $transaction->markAsConfirmed();
            $giftService->decrementGiftQuantity($transaction->gift_item_id);
        });

        return response()->json([
            'success' => true,
            'transaction_id' => $transaction->internal_id,
            'status' => $transaction->status,
        ]);
    }
}
