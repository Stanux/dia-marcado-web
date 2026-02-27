<?php

namespace App\Services;

use App\Contracts\OnboardingServiceInterface;
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
        private readonly SiteBuilderServiceInterface $siteBuilderService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function complete(User $user, array $data): Wedding
    {
        return DB::transaction(function () use ($user, $data) {
            $creatorName = $this->resolveCreatorName($user, $data);

            if ($creatorName !== $user->name) {
                $user->forceFill([
                    'name' => $creatorName,
                ])->save();
            }

            // 1. Create the wedding
            $wedding = $this->createWedding($user, $data, $creatorName);

            // 2. Create the site for the wedding
            $this->siteBuilderService->create($wedding);

            // 3. Create default RSVP event based on onboarding date/time
            $this->createDefaultGuestEvent($wedding, $user, $data);

            // 4. Mark onboarding as complete
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
    private function createWedding(User $user, array $data, string $creatorName): Wedding
    {
        $partnerNameDraft = $this->normalizeName($data['partner_name'] ?? null);

        $weddingData = [
            'title' => $this->generateWeddingTitle($creatorName, $data),
            'wedding_date' => $data['wedding_date'] ?? null,
            'venue' => $data['venue_name'] ?? null,
            'city' => $data['venue_city'] ?? null,
            'state' => $data['venue_state'] ?? null,
            'settings' => [
                'plan' => $data['plan'] ?? 'basic',
                'venue_address' => $data['venue_address'] ?? null,
                'venue_neighborhood' => $data['venue_neighborhood'] ?? null,
                'venue_phone' => $data['venue_phone'] ?? null,
                // Horário passa a ser definido em "Dados do Evento" após onboarding.
                'wedding_time' => null,
                'partner_name_draft' => $partnerNameDraft,
            ],
        ];

        return $this->weddingService->createWedding($user, $weddingData);
    }

    /**
     * Generate a wedding title based on available data.
     */
    private function generateWeddingTitle(string $creatorName, array $data): string
    {
        $creatorFirstName = $this->extractFirstName($creatorName);

        $partnerName = $this->normalizeName($data['partner_name'] ?? null);
        if ($partnerName !== null) {
            $partnerFirstName = $this->extractFirstName($partnerName);
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
     * Resolve creator name from onboarding data.
     */
    private function resolveCreatorName(User $user, array $data): string
    {
        return $this->normalizeName($data['creator_name'] ?? null) ?? $user->name;
    }

    private function normalizeName(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
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
                    'sync_with_wedding_settings' => true,
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

        // O horário deixa de ser coletado no onboarding.
        // O event_at será definido posteriormente em "Dados do Evento".
        return null;
    }
}
