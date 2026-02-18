<?php

namespace App\Services;

use App\Contracts\OnboardingServiceInterface;
use App\Contracts\PartnerInviteServiceInterface;
use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Models\GuestEvent;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service for handling the onboarding process.
 */
class OnboardingService implements OnboardingServiceInterface
{
    public function __construct(
        private readonly WeddingService $weddingService,
        private readonly SiteBuilderServiceInterface $siteBuilderService,
        private readonly PartnerInviteServiceInterface $partnerInviteService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function complete(User $user, array $data): Wedding
    {
        return DB::transaction(function () use ($user, $data) {
            // 1. Create the wedding
            $wedding = $this->createWedding($user, $data);

            // 2. Create the site for the wedding
            $this->siteBuilderService->create($wedding);

            // 3. Create default RSVP event based on onboarding date/time
            $this->createDefaultGuestEvent($wedding, $user, $data);

            // 4. Send partner invite if provided
            if ($this->hasPartnerData($data)) {
                $this->partnerInviteService->sendInvite(
                    $wedding,
                    $user,
                    $data['partner_email'],
                    $data['partner_name']
                );
            }

            // 5. Mark onboarding as complete
            $user->markOnboardingComplete();

            return $wedding;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function hasCompleted(User $user): bool
    {
        return $user->hasCompletedOnboarding();
    }

    /**
     * Create the wedding from onboarding data.
     */
    private function createWedding(User $user, array $data): Wedding
    {
        $weddingTime = $this->normalizeWeddingTime($data['wedding_time'] ?? null);

        $weddingData = [
            'title' => $this->generateWeddingTitle($user, $data),
            'wedding_date' => $data['wedding_date'] ?? null,
            'venue' => $data['venue_name'] ?? null,
            'city' => $data['venue_city'] ?? null,
            'state' => $data['venue_state'] ?? null,
            'settings' => [
                'plan' => $data['plan'] ?? 'basic',
                'venue_address' => $data['venue_address'] ?? null,
                'venue_neighborhood' => $data['venue_neighborhood'] ?? null,
                'venue_phone' => $data['venue_phone'] ?? null,
                'wedding_time' => $weddingTime,
            ],
        ];

        return $this->weddingService->createWedding($user, $weddingData);
    }

    /**
     * Generate a wedding title based on available data.
     */
    private function generateWeddingTitle(User $user, array $data): string
    {
        $creatorFirstName = $this->extractFirstName($user->name);

        if (!empty($data['partner_name'])) {
            $partnerFirstName = $this->extractFirstName($data['partner_name']);
            return "Casamento {$creatorFirstName} e {$partnerFirstName}";
        }

        return "Casamento de {$creatorFirstName}";
    }

    /**
     * Extract the first name from a full name.
     */
    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? $fullName;
    }

    /**
     * Check if partner data was provided.
     */
    private function hasPartnerData(array $data): bool
    {
        return !empty($data['partner_email']) && !empty($data['partner_name']);
    }

    private function createDefaultGuestEvent(Wedding $wedding, User $user, array $data): void
    {
        GuestEvent::withoutGlobalScopes()->firstOrCreate(
            [
                'wedding_id' => $wedding->id,
                'slug' => 'casamento',
            ],
            [
                'created_by' => $user->id,
                'name' => 'Casamento',
                'event_at' => $this->resolveEventAt($data),
                'is_active' => true,
                'metadata' => [
                    'source' => 'onboarding',
                    'auto_created' => true,
                ],
            ],
        );
    }

    private function resolveEventAt(array $data): ?Carbon
    {
        $weddingDate = $data['wedding_date'] ?? null;
        if (empty($weddingDate)) {
            return null;
        }

        $time = $this->normalizeWeddingTime($data['wedding_time'] ?? null);

        return Carbon::parse("{$weddingDate} {$time}", config('app.timezone'));
    }

    private function normalizeWeddingTime(?string $value): string
    {
        if (!$value) {
            return '18:00';
        }

        if (preg_match('/^(?<hour>\d{2}):(?<minute>\d{2})(?::\d{2})?$/', $value, $matches) !== 1) {
            return '18:00';
        }

        $hour = (int) $matches['hour'];
        $minute = (int) $matches['minute'];

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return '18:00';
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
