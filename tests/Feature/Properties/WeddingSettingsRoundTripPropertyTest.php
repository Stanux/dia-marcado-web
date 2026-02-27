<?php

namespace Tests\Feature\Properties;

use App\Models\Wedding;
use App\Services\WeddingSettingsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: onboarding-improvements, Property 2: Round-Trip de Persistência de Dados
 * 
 * For any valid set of configuration data (date, venue, plan),
 * saving the data and then loading the page again should return
 * the same data that was saved.
 * 
 * Validates: Requirements 3.3, 4.3, 5.2, 6.2
 */
class WeddingSettingsRoundTripPropertyTest extends TestCase
{
    use RefreshDatabase;

    private WeddingSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WeddingSettingsService();
    }

    /**
     * Generate random wedding settings data.
     */
    private function generateRandomSettingsData(): array
    {
        $plans = ['basic', 'premium'];
        $states = ['SP', 'RJ', 'MG', 'BA', 'RS', 'PR', 'SC', 'PE', 'CE', 'GO'];
        
        return [
            'wedding_date' => Carbon::now()->addDays(rand(30, 365))->format('Y-m-d'),
            'venue_name' => 'Venue ' . fake()->company(),
            'venue_address' => fake()->streetAddress(),
            'venue_neighborhood' => fake()->citySuffix() . ' ' . fake()->lastName(),
            'venue_city' => fake()->city(),
            'venue_state' => $states[array_rand($states)],
            'venue_phone' => fake()->phoneNumber(),
            'plan' => $plans[array_rand($plans)],
        ];
    }

    /**
     * Property test: Wedding date round-trip
     * 
     * For any valid wedding date, saving and loading should preserve the date.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function wedding_date_round_trip(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $futureDate = Carbon::now()->addDays(rand(1, 365))->format('Y-m-d');
            
            $this->service->update($wedding, ['wedding_date' => $futureDate]);
            
            $wedding->refresh();
            
            $this->assertEquals(
                $futureDate,
                $wedding->wedding_date->format('Y-m-d'),
                "Wedding date should be preserved after save (iteration $i)"
            );
        }
    }

    /**
     * Property test: Venue data round-trip
     * 
     * For any valid venue data, saving and loading should preserve all fields.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function venue_data_round_trip(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $data = $this->generateRandomSettingsData();
            
            $this->service->update($wedding, $data);
            
            $wedding->refresh();
            
            $this->assertEquals(
                $data['venue_name'],
                $wedding->venue,
                "Venue name should be preserved (iteration $i)"
            );
            
            $this->assertEquals(
                $data['venue_city'],
                $wedding->city,
                "Venue city should be preserved (iteration $i)"
            );
            
            $this->assertEquals(
                $data['venue_state'],
                $wedding->state,
                "Venue state should be preserved (iteration $i)"
            );
            
            $this->assertEquals(
                $data['venue_address'],
                $wedding->settings['venue_address'] ?? null,
                "Venue address should be preserved in settings (iteration $i)"
            );
            
            $this->assertEquals(
                $data['venue_neighborhood'],
                $wedding->settings['venue_neighborhood'] ?? null,
                "Venue neighborhood should be preserved in settings (iteration $i)"
            );
            
            $this->assertEquals(
                $data['venue_phone'],
                $wedding->settings['venue_phone'] ?? null,
                "Venue phone should be preserved in settings (iteration $i)"
            );
        }
    }

    /**
     * Property test: Plan round-trip
     * 
     * For any valid plan selection, saving and loading should preserve the plan.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function plan_round_trip(): void
    {
        $plans = ['basic', 'premium'];
        
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $plan = $plans[array_rand($plans)];
            
            $this->service->update($wedding, ['plan' => $plan]);
            
            $wedding->refresh();
            
            $this->assertEquals(
                $plan,
                $wedding->settings['plan'] ?? null,
                "Plan should be preserved after save (iteration $i)"
            );
        }
    }

    /**
     * Property test: Full settings round-trip
     * 
     * For any complete set of settings, saving and loading should preserve all data.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function full_settings_round_trip(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $data = $this->generateRandomSettingsData();
            
            $this->service->update($wedding, $data);
            
            $wedding->refresh();
            
            // Verify all fields are preserved
            $this->assertEquals($data['wedding_date'], $wedding->wedding_date->format('Y-m-d'));
            $this->assertEquals($data['venue_name'], $wedding->venue);
            $this->assertEquals($data['venue_city'], $wedding->city);
            $this->assertEquals($data['venue_state'], $wedding->state);
            $this->assertEquals($data['venue_address'], $wedding->settings['venue_address'] ?? null);
            $this->assertEquals($data['venue_neighborhood'], $wedding->settings['venue_neighborhood'] ?? null);
            $this->assertEquals($data['venue_phone'], $wedding->settings['venue_phone'] ?? null);
            $this->assertEquals($data['plan'], $wedding->settings['plan'] ?? null);
        }
    }

    /**
     * Property test: Partial update preserves other fields
     * 
     * When updating only some fields, other fields should remain unchanged.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function partial_update_preserves_other_fields(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $initialData = $this->generateRandomSettingsData();
            
            // First, set all data
            $this->service->update($wedding, $initialData);
            $wedding->refresh();
            
            // Then, update only the plan
            $newPlan = $initialData['plan'] === 'basic' ? 'premium' : 'basic';
            $this->service->update($wedding, ['plan' => $newPlan]);
            $wedding->refresh();
            
            // Verify plan changed
            $this->assertEquals($newPlan, $wedding->settings['plan'] ?? null);
            
            // Verify other fields preserved
            $this->assertEquals($initialData['venue_name'], $wedding->venue);
            $this->assertEquals($initialData['venue_city'], $wedding->city);
        }
    }

    /**
     * Property test: Partner draft name round-trip.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function partner_name_draft_round_trip(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $partnerNameDraft = fake()->name();

            $this->service->update($wedding, ['partner_name_draft' => $partnerNameDraft]);

            $wedding->refresh();

            $this->assertEquals(
                $partnerNameDraft,
                $wedding->settings['partner_name_draft'] ?? null,
                "Partner draft name should be preserved (iteration $i)"
            );
        }
    }
}
