<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestHousehold;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestHouseholdController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GuestHousehold::class);
        $weddingId = $request->user()->current_wedding_id;

        $households = GuestHousehold::where('wedding_id', $weddingId)
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $households]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', GuestHousehold::class);
        $weddingId = $request->user()->current_wedding_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'side' => 'nullable|string|max:20',
            'category' => 'nullable|string|max:50',
            'priority' => 'nullable|integer',
            'quota_adults' => 'nullable|integer|min:0',
            'quota_children' => 'nullable|integer|min:0',
            'plus_one_allowed' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $household = GuestHousehold::create([
            ...$validated,
            'wedding_id' => $weddingId,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $household], 201);
    }

    public function show(GuestHousehold $household): JsonResponse
    {
        $this->authorize('view', $household);

        return response()->json(['data' => $household->load('guests')]);
    }

    public function update(Request $request, GuestHousehold $household): JsonResponse
    {
        $this->authorize('update', $household);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:20',
            'side' => 'nullable|string|max:20',
            'category' => 'nullable|string|max:50',
            'priority' => 'nullable|integer',
            'quota_adults' => 'nullable|integer|min:0',
            'quota_children' => 'nullable|integer|min:0',
            'plus_one_allowed' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $household->update($validated);

        return response()->json(['data' => $household]);
    }

    public function destroy(GuestHousehold $household): JsonResponse
    {
        $this->authorize('delete', $household);
        $household->delete();

        return response()->json(['message' => 'Household removido com sucesso.']);
    }
}
