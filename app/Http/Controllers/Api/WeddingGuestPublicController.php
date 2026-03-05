<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Guests\RsvpSubmissionException;
use App\Services\Guests\WeddingGuestPublicRsvpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WeddingGuestPublicController extends Controller
{
    public function __construct(
        private readonly WeddingGuestPublicRsvpService $weddingGuestPublicRsvpService,
    ) {}

    public function resolve(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'uuid'],
            'confirmation_code' => ['required', 'string', 'size:6'],
        ]);

        try {
            $result = $this->weddingGuestPublicRsvpService->resolveByCode(
                siteSlug: $slug,
                eventId: (string) $validated['event_id'],
                rawCode: (string) $validated['confirmation_code'],
            );
        } catch (RsvpSubmissionException $exception) {
            $response = ['message' => $exception->getMessage()];
            if ($exception->details() !== []) {
                $response['details'] = $exception->details();
            }

            return response()->json($response, $exception->statusCode());
        }

        return response()->json(['data' => $result]);
    }

    public function submit(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'uuid'],
            'confirmation_code' => ['required', 'string', 'size:6'],
            'guests' => ['nullable', 'array'],
            'guests.*.name' => ['nullable', 'string', 'max:255'],
            'guests.*.email' => ['nullable', 'email', 'max:255'],
            'guests.*.phone' => ['nullable', 'string', 'max:30'],
            'guests.*.side' => ['nullable', Rule::in(['bride', 'groom', 'both'])],
            'guests.*.status' => ['nullable', Rule::in(['pending', 'confirmed', 'declined'])],
            'guests.*.is_child' => ['nullable', 'boolean'],
            'guests.*.is_primary_contact' => ['nullable', 'boolean'],
            'responses' => ['nullable', 'array'],
            'responses.*.guest_id' => ['required_with:responses', 'uuid'],
            'responses.*.status' => ['required_with:responses', Rule::in(['confirmed', 'declined'])],
        ]);

        try {
            $result = $this->weddingGuestPublicRsvpService->submitByCode(
                siteSlug: $slug,
                eventId: (string) $validated['event_id'],
                rawCode: (string) $validated['confirmation_code'],
                payload: $validated,
            );
        } catch (RsvpSubmissionException $exception) {
            $response = ['message' => $exception->getMessage()];
            if ($exception->details() !== []) {
                $response['details'] = $exception->details();
            }

            return response()->json($response, $exception->statusCode());
        }

        return response()->json(['data' => $result]);
    }
}
