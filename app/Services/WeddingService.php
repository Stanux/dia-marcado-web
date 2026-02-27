<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wedding;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WeddingService
{
    /**
     * Create a new wedding and automatically link the creator as couple.
     *
     * @param User $creator
     * @param array $data
     * @return Wedding
     */
    public function createWedding(User $creator, array $data): Wedding
    {
        return DB::transaction(function () use ($creator, $data) {
            $wedding = Wedding::create([
                'title' => $data['title'],
                'wedding_date' => $this->normalizeWeddingDate($data['wedding_date'] ?? null),
                'venue' => $data['venue'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'settings' => $data['settings'] ?? [],
                'is_active' => true,
            ]);

            // Automatically link creator as couple
            $wedding->users()->attach($creator->id, [
                'role' => 'couple',
                'permissions' => [],
            ]);

            // Set as current wedding for the user
            $creator->update(['current_wedding_id' => $wedding->id]);

            return $wedding;
        });
    }

    private function normalizeWeddingDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '' || strtolower($normalized) === 'null' || $normalized === '0000-00-00') {
            return null;
        }

        if (preg_match('/^(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})$/', $normalized, $matches) === 1) {
            $year = (int) $matches['year'];
            $month = (int) $matches['month'];
            $day = (int) $matches['day'];

            return checkdate($month, $day, $year) ? $normalized : null;
        }

        try {
            return Carbon::parse($normalized)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Add a second couple member to the wedding.
     *
     * @param Wedding $wedding
     * @param User $user
     * @return void
     */
    public function addCouplePartner(Wedding $wedding, User $user): void
    {
        $wedding->users()->attach($user->id, [
            'role' => 'couple',
            'permissions' => [],
        ]);
    }
}
