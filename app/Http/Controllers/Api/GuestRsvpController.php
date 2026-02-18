<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestRsvp;
use App\Services\Guests\RsvpSubmissionException;
use App\Services\Guests\RsvpSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestRsvpController extends Controller
{
    public function __construct(
        private readonly RsvpSubmissionService $rsvpSubmissionService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', GuestRsvp::class);

        $validated = $request->validate([
            'guest_id' => 'required|uuid|exists:guests,id',
            'event_id' => 'required|uuid|exists:guest_events,id',
            'status' => ['required', 'string', 'max:20', Rule::in(GuestRsvp::validationStatuses())],
            'responses' => 'nullable|array',
        ]);

        try {
            $rsvp = $this->rsvpSubmissionService->submitAuthenticated(
                validated: $validated,
                updatedBy: $request->user()?->id,
            );
        } catch (RsvpSubmissionException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->statusCode());
        }

        return response()->json(['data' => $rsvp]);
    }

    public function publicStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'nullable|string',
            'event_id' => 'required|uuid|exists:guest_events,id',
            'status' => ['required', 'string', 'max:20', Rule::in(GuestRsvp::validationStatuses())],
            'responses' => 'nullable|array',
            'guest' => 'required|array',
            'guest.name' => 'required|string|max:255',
            'guest.email' => 'nullable|email|max:255',
            'guest.phone' => 'nullable|string|max:30',
            'guest.is_child' => 'nullable|boolean',
            'household_name' => 'nullable|string|max:255',
        ]);

        try {
            $rsvp = $this->rsvpSubmissionService->submitPublic($validated);
        } catch (RsvpSubmissionException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->statusCode());
        }

        return response()->json(['data' => $rsvp]);
    }
}
