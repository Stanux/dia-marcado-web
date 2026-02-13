<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Guest::class);
        $weddingId = $request->user()->current_wedding_id;

        $query = Guest::where('wedding_id', $weddingId);

        if ($request->filled('household_id')) {
            $query->where('household_id', $request->string('household_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('side')) {
            $query->where('side', $request->string('side'));
        }

        $guests = $query->orderBy('name')->get();

        return response()->json(['data' => $guests]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Guest::class);
        $weddingId = $request->user()->current_wedding_id;

        $validated = $request->validate([
            'household_id' => 'nullable|uuid|exists:guest_households,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'nickname' => 'nullable|string|max:100',
            'role_in_household' => 'nullable|string|max:30',
            'is_child' => 'nullable|boolean',
            'category' => 'nullable|string|max:50',
            'side' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:20',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['household_id'])) {
            $household = \App\Models\GuestHousehold::find($validated['household_id']);
            if ($household && $household->wedding_id !== $weddingId) {
                return response()->json(['message' => 'Household inválido para este casamento.'], 422);
            }
        }

        $guest = Guest::create([
            ...$validated,
            'wedding_id' => $weddingId,
        ]);

        return response()->json(['data' => $guest], 201);
    }

    public function show(Guest $guest): JsonResponse
    {
        $this->authorize('view', $guest);

        return response()->json(['data' => $guest->load(['household', 'rsvps'])]);
    }

    public function update(Request $request, Guest $guest): JsonResponse
    {
        $this->authorize('update', $guest);

        $validated = $request->validate([
            'household_id' => 'nullable|uuid|exists:guest_households,id',
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'nickname' => 'nullable|string|max:100',
            'role_in_household' => 'nullable|string|max:30',
            'is_child' => 'nullable|boolean',
            'category' => 'nullable|string|max:50',
            'side' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:20',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['household_id'])) {
            $household = \App\Models\GuestHousehold::find($validated['household_id']);
            if ($household && $household->wedding_id !== $guest->wedding_id) {
                return response()->json(['message' => 'Household inválido para este casamento.'], 422);
            }
        }

        $guest->update($validated);

        return response()->json(['data' => $guest]);
    }

    public function destroy(Guest $guest): JsonResponse
    {
        $this->authorize('delete', $guest);
        $guest->delete();

        return response()->json(['message' => 'Convidado removido com sucesso.']);
    }
}
