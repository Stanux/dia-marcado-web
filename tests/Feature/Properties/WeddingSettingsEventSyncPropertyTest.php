<?php

namespace Tests\Feature\Properties;

use App\Contracts\WeddingSettingsServiceInterface;
use App\Models\GuestEvent;
use App\Models\Wedding;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeddingSettingsEventSyncPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function auto_created_default_event_is_synced_when_it_matches_previous_expected_datetime(): void
    {
        $service = $this->app->make(WeddingSettingsServiceInterface::class);

        $wedding = Wedding::factory()->create([
            'wedding_date' => '2027-06-10',
            'settings' => [
                'wedding_time' => '18:00',
            ],
        ]);

        $event = GuestEvent::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Casamento',
            'slug' => 'casamento',
            'event_at' => Carbon::parse('2027-06-10 18:00:00', config('app.timezone')),
            'is_active' => true,
            'metadata' => [
                'source' => 'onboarding',
                'auto_created' => true,
                'sync_with_wedding_settings' => true,
            ],
        ]);

        $updated = $service->update($wedding, [
            'wedding_date' => '2027-07-20',
            'wedding_time' => '20:30',
        ]);

        $event->refresh();

        $this->assertSame('2027-07-20', $updated->wedding_date?->format('Y-m-d'));
        $this->assertSame('20:30', $updated->settings['wedding_time'] ?? null);
        $this->assertSame('2027-07-20 20:30', $event->event_at?->format('Y-m-d H:i'));
    }

    /**
     * @test
     */
    public function manually_diverged_default_event_datetime_is_not_overwritten_by_wedding_settings_sync(): void
    {
        $service = $this->app->make(WeddingSettingsServiceInterface::class);

        $wedding = Wedding::factory()->create([
            'wedding_date' => '2027-06-10',
            'settings' => [
                'wedding_time' => '18:00',
            ],
        ]);

        $event = GuestEvent::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Casamento',
            'slug' => 'casamento',
            // Divergiu do valor "esperado" anterior (2027-06-10 18:00).
            'event_at' => Carbon::parse('2027-06-11 10:00:00', config('app.timezone')),
            'is_active' => true,
            'metadata' => [
                'source' => 'onboarding',
                'auto_created' => true,
                'sync_with_wedding_settings' => true,
            ],
        ]);

        $service->update($wedding, [
            'wedding_date' => '2027-07-20',
            'wedding_time' => '20:30',
        ]);

        $event->refresh();

        $this->assertSame('2027-06-11 10:00', $event->event_at?->format('Y-m-d H:i'));
    }

    /**
     * @test
     */
    public function clearing_wedding_date_and_time_persists_null_and_clears_synced_default_event_datetime(): void
    {
        $service = $this->app->make(WeddingSettingsServiceInterface::class);

        $wedding = Wedding::factory()->create([
            'wedding_date' => '2027-06-10',
            'settings' => [
                'wedding_time' => '18:00',
            ],
        ]);

        $event = GuestEvent::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Casamento',
            'slug' => 'casamento',
            'event_at' => Carbon::parse('2027-06-10 18:00:00', config('app.timezone')),
            'is_active' => true,
            'metadata' => [
                'source' => 'onboarding',
                'auto_created' => true,
                'sync_with_wedding_settings' => true,
            ],
        ]);

        $updated = $service->update($wedding, [
            'wedding_date' => '',
            'wedding_time' => '',
        ]);

        $event->refresh();

        $this->assertNull($updated->wedding_date);
        $this->assertNull($updated->settings['wedding_time'] ?? null);
        $this->assertNull($event->event_at);
    }
}
