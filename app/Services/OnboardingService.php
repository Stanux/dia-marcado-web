<?php

namespace App\Services;

use App\Contracts\OnboardingServiceInterface;
use App\Contracts\PartnerInviteServiceInterface;
use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Models\User;
use App\Models\Wedding;
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

            // 3. Send partner invite if provided
            if ($this->hasPartnerData($data)) {
                $this->partnerInviteService->sendInvite(
                    $wedding,
                    $user,
                    $data['partner_email'],
                    $data['partner_name']
                );
            }

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
    private function createWedding(User $user, array $data): Wedding
    {
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
}
