<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('normalized_email', 255)->nullable()->after('email');
            $table->string('normalized_phone', 30)->nullable()->after('phone');
            $table->string('overall_rsvp_status', 20)->default('no_response')->after('status');
        });

        Schema::table('guest_invites', function (Blueprint $table) {
            $table->string('token_hash', 64)->nullable()->after('token');
            $table->unsignedInteger('uses_count')->default(0)->after('status');
            $table->unsignedInteger('max_uses')->nullable()->after('uses_count');
            $table->dateTime('revoked_at')->nullable()->after('used_at');
            $table->text('revoked_reason')->nullable()->after('revoked_at');
        });

        $this->backfillGuests();
        $this->backfillRsvps();
        $this->backfillOverallGuestStatus();
        $this->backfillInviteTokens();

        Schema::table('guests', function (Blueprint $table) {
            $table->index(['wedding_id', 'normalized_email'], 'guests_wedding_normalized_email_idx');
            $table->index(['wedding_id', 'normalized_phone'], 'guests_wedding_normalized_phone_idx');
            $table->index(['wedding_id', 'overall_rsvp_status'], 'guests_wedding_overall_rsvp_status_idx');
        });

        Schema::table('guest_invites', function (Blueprint $table) {
            $table->unique('token_hash', 'guest_invites_token_hash_unique');
            $table->index(['status', 'uses_count'], 'guest_invites_status_uses_count_idx');
        });

        $this->addCheckConstraints();
    }

    public function down(): void
    {
        $this->dropCheckConstraints();

        $this->safeSchemaChange(function () {
            Schema::table('guest_invites', function (Blueprint $table) {
                $table->dropIndex('guest_invites_status_uses_count_idx');
            });
        });

        $this->safeSchemaChange(function () {
            Schema::table('guest_invites', function (Blueprint $table) {
                $table->dropUnique('guest_invites_token_hash_unique');
            });
        });

        $this->safeSchemaChange(function () {
            Schema::table('guests', function (Blueprint $table) {
                $table->dropIndex('guests_wedding_overall_rsvp_status_idx');
            });
        });

        $this->safeSchemaChange(function () {
            Schema::table('guests', function (Blueprint $table) {
                $table->dropIndex('guests_wedding_normalized_phone_idx');
            });
        });

        $this->safeSchemaChange(function () {
            Schema::table('guests', function (Blueprint $table) {
                $table->dropIndex('guests_wedding_normalized_email_idx');
            });
        });

        Schema::table('guest_invites', function (Blueprint $table) {
            $table->dropColumn([
                'token_hash',
                'uses_count',
                'max_uses',
                'revoked_at',
                'revoked_reason',
            ]);
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn([
                'normalized_email',
                'normalized_phone',
                'overall_rsvp_status',
            ]);
        });
    }

    private function backfillGuests(): void
    {
        DB::table('guests')
            ->select('id', 'email', 'phone', 'status')
            ->orderBy('id')
            ->chunk(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('guests')
                        ->where('id', $row->id)
                        ->update([
                            'normalized_email' => $this->normalizeEmail($row->email),
                            'normalized_phone' => $this->normalizePhone($row->phone),
                            'status' => $this->normalizeGuestStatus($row->status),
                        ]);
                }
            });
    }

    private function backfillRsvps(): void
    {
        DB::table('guest_rsvps')
            ->select('id', 'status')
            ->orderBy('id')
            ->chunk(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('guest_rsvps')
                        ->where('id', $row->id)
                        ->update([
                            'status' => $this->normalizeRsvpStatus($row->status),
                        ]);
                }
            });
    }

    private function backfillOverallGuestStatus(): void
    {
        DB::table('guests')->update([
            'overall_rsvp_status' => 'no_response',
        ]);

        $summaries = DB::table('guest_rsvps')
            ->select(
                'guest_id',
                DB::raw("MAX(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS has_confirmed"),
                DB::raw("MAX(CASE WHEN status = 'maybe' THEN 1 ELSE 0 END) AS has_maybe"),
                DB::raw("MAX(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) AS has_declined")
            )
            ->groupBy('guest_id')
            ->get();

        foreach ($summaries as $summary) {
            $overall = 'no_response';

            if ((int) $summary->has_confirmed === 1) {
                $overall = 'confirmed';
            } elseif ((int) $summary->has_maybe === 1) {
                $overall = 'maybe';
            } elseif ((int) $summary->has_declined === 1) {
                $overall = 'declined';
            }

            DB::table('guests')
                ->where('id', $summary->guest_id)
                ->update([
                    'overall_rsvp_status' => $overall,
                    'status' => $overall === 'no_response' ? 'pending' : $overall,
                ]);
        }
    }

    private function backfillInviteTokens(): void
    {
        DB::table('guest_invites')
            ->select('id', 'token', 'used_at', 'uses_count')
            ->whereNotNull('token')
            ->orderBy('id')
            ->chunk(500, function ($rows): void {
                foreach ($rows as $row) {
                    $usesCount = (int) ($row->uses_count ?? 0);

                    if ($row->used_at !== null && $usesCount < 1) {
                        $usesCount = 1;
                    }

                    DB::table('guest_invites')
                        ->where('id', $row->id)
                        ->update([
                            'token_hash' => hash('sha256', $row->token),
                            'uses_count' => $usesCount,
                        ]);
                }
            });
    }

    private function addCheckConstraints(): void
    {
        $driver = DB::getDriverName();

        if (!in_array($driver, ['pgsql', 'mysql'], true)) {
            return;
        }

        DB::statement(
            "ALTER TABLE guests ADD CONSTRAINT guests_status_check CHECK (status IN ('pending', 'confirmed', 'declined', 'maybe'))"
        );

        DB::statement(
            "ALTER TABLE guests ADD CONSTRAINT guests_overall_rsvp_status_check CHECK (overall_rsvp_status IN ('no_response', 'confirmed', 'declined', 'maybe'))"
        );

        DB::statement(
            "ALTER TABLE guest_rsvps ADD CONSTRAINT guest_rsvps_status_check CHECK (status IN ('no_response', 'confirmed', 'declined', 'maybe'))"
        );
    }

    private function dropCheckConstraints(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $this->safeStatement('ALTER TABLE guests DROP CONSTRAINT IF EXISTS guests_status_check');
            $this->safeStatement('ALTER TABLE guests DROP CONSTRAINT IF EXISTS guests_overall_rsvp_status_check');
            $this->safeStatement('ALTER TABLE guest_rsvps DROP CONSTRAINT IF EXISTS guest_rsvps_status_check');
            return;
        }

        if ($driver === 'mysql') {
            $this->safeStatement('ALTER TABLE guests DROP CHECK guests_status_check');
            $this->safeStatement('ALTER TABLE guests DROP CHECK guests_overall_rsvp_status_check');
            $this->safeStatement('ALTER TABLE guest_rsvps DROP CHECK guest_rsvps_status_check');
        }
    }

    private function normalizeEmail(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizePhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }

    private function normalizeGuestStatus(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'pending', 'pendente' => 'pending',
            'confirmed', 'confirmado' => 'confirmed',
            'declined', 'recusado' => 'declined',
            'maybe', 'talvez' => 'maybe',
            default => 'pending',
        };
    }

    private function normalizeRsvpStatus(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'confirmed', 'confirmado' => 'confirmed',
            'declined', 'recusado' => 'declined',
            'maybe', 'talvez' => 'maybe',
            'pending', 'pendente', 'no_response', 'sem_resposta', 'sem resposta' => 'no_response',
            default => 'no_response',
        };
    }

    private function safeStatement(string $statement): void
    {
        try {
            DB::statement($statement);
        } catch (\Throwable $e) {
            // no-op: keep rollback/deploy resilient across database engines and partial states.
        }
    }

    private function safeSchemaChange(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            // no-op: keep rollback resilient.
        }
    }
};
