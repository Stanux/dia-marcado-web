<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('wedding_invites')) {
            return;
        }

        $usedCodes = array_fill_keys(
            DB::table('wedding_invites')
                ->whereNotNull('confirmation_code')
                ->pluck('confirmation_code')
                ->map(fn ($code) => strtoupper((string) $code))
                ->all(),
            true
        );

        $inviteIds = DB::table('wedding_invites')
            ->whereNull('confirmation_code')
            ->pluck('id');

        foreach ($inviteIds as $inviteId) {
            $code = $this->generateUniqueCode($usedCodes);
            $usedCodes[$code] = true;

            DB::table('wedding_invites')
                ->where('id', $inviteId)
                ->update([
                    'confirmation_code' => $code,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // No rollback action: generated codes are required for public RSVP flow.
    }

    /**
     * @param array<string, bool> $usedCodes
     */
    private function generateUniqueCode(array $usedCodes): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        do {
            $code = '';

            for ($index = 0; $index < 6; $index++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (isset($usedCodes[$code]));

        return $code;
    }
};
