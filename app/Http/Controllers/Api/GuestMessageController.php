<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GuestMessage::class);
        $weddingId = $request->user()->current_wedding_id;

        $messages = GuestMessage::where('wedding_id', $weddingId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $messages]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', GuestMessage::class);
        $weddingId = $request->user()->current_wedding_id;

        $validated = $request->validate([
            'channel' => 'required|string|max:20',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'payload' => 'nullable|array',
        ]);

        $message = GuestMessage::create([
            ...$validated,
            'wedding_id' => $weddingId,
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        return response()->json(['data' => $message], 201);
    }
}
