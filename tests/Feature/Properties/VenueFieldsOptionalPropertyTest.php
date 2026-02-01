<?php

namespace Tests\Feature\Properties;

use App\Models\Wedding;
use App\Services\WeddingSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: onboarding-improvements, Property 4: Campos de Local Opcionais
 * 
 * For any combination of venue fields (all filled, some filled, none filled),
 * saving should be successful without validation errors.
 * 
 * Validates: Requirements 5.3
 */
class VenueFieldsOptionalPropertyTest extends TestCase
{
    use RefreshDatabase;

    private WeddingSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WeddingSettingsService();
    }

    /**
     * Generate random venue data with specified fields filled.
     */
    private function generateVenueData(array $fieldsToFill): array
    {
        $allFields = [
            'venue_name' => fake()->company(),
            'venue_address' => fake()->streetAddress(),
            'venue_neighborhood' => fake()->citySuffix(),
            'venue_city' => fake()->city(),
            'venue_state' => fake()->stateAbbr(),
            'venue_phone' => fake()->phoneNumber(),
        ];

        $data = [];
        foreach ($fieldsToFill as $field) {
            if (isset($allFields[$field])) {
                $data[$field] = $allFields[$field];
            }
        }

        return $data;
    }

    /**
     * Property test: All venue fields empty is valid
     * 
     * Saving with no venue fields should succeed.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function all_venue_fields_empty_is_valid(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            
            // Save with no venue data
            $result = $this->service->update($wedding, []);
            
            $this->assertInstanceOf(
                Wedding::class,
                $result,
                "Saving with no venue fields should succeed (iteration $i)"
            );
        }
    }

    /**
     * Property test: All venue fields filled is valid
     * 
     * Saving with all venue fields should succeed.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function all_venue_fields_filled_is_valid(): void
    {
        $allFields = ['venue_name', 'venue_address', 'venue_neighborhood', 'venue_city', 'venue_state', 'venue_phone'];
        
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $data = $this->generateVenueData($allFields);
            
            $result = $this->service->update($wedding, $data);
            
            $this->assertInstanceOf(
                Wedding::class,
                $result,
                "Saving with all venue fields should succeed (iteration $i)"
            );
            
            // Verify data was saved
            $wedding->refresh();
            $this->assertEquals($data['venue_name'], $wedding->venue);
            $this->assertEquals($data['venue_city'], $wedding->city);
            $this->assertEquals($data['venue_state'], $wedding->state);
        }
    }

    /**
     * Property test: Random subset of venue fields is valid
     * 
     * Saving with any random subset of venue fields should succeed.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function random_subset_of_venue_fields_is_valid(): void
    {
        $allFields = ['venue_name', 'venue_address', 'venue_neighborhood', 'venue_city', 'venue_state', 'venue_phone'];
        
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            
            // Pick a random subset of fields
            $numFields = rand(0, count($allFields));
            $shuffled = $allFields;
            shuffle($shuffled);
            $fieldsToFill = array_slice($shuffled, 0, $numFields);
            
            $data = $this->generateVenueData($fieldsToFill);
            
            $result = $this->service->update($wedding, $data);
            
            $this->assertInstanceOf(
                Wedding::class,
                $result,
                "Saving with " . count($fieldsToFill) . " venue fields should succeed (iteration $i)"
            );
        }
    }

    /**
     * Property test: Only venue name is valid
     * 
     * Saving with only venue name should succeed.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function only_venue_name_is_valid(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $data = $this->generateVenueData(['venue_name']);
            
            $result = $this->service->update($wedding, $data);
            
            $this->assertInstanceOf(
                Wedding::class,
                $result,
                "Saving with only venue name should succeed (iteration $i)"
            );
            
            $wedding->refresh();
            $this->assertEquals($data['venue_name'], $wedding->venue);
        }
    }

    /**
     * Property test: Only city and state is valid
     * 
     * Saving with only city and state should succeed.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function only_city_and_state_is_valid(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $data = $this->generateVenueData(['venue_city', 'venue_state']);
            
            $result = $this->service->update($wedding, $data);
            
            $this->assertInstanceOf(
                Wedding::class,
                $result,
                "Saving with only city and state should succeed (iteration $i)"
            );
            
            $wedding->refresh();
            $this->assertEquals($data['venue_city'], $wedding->city);
            $this->assertEquals($data['venue_state'], $wedding->state);
        }
    }

    /**
     * Property test: Null values are handled correctly
     * 
     * Saving with explicit null values should succeed.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function null_values_are_handled_correctly(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create([
                'venue' => 'Initial Venue',
                'city' => 'Initial City',
            ]);
            
            // Update with null values
            $result = $this->service->update($wedding, [
                'venue_name' => null,
                'venue_city' => null,
            ]);
            
            $this->assertInstanceOf(
                Wedding::class,
                $result,
                "Saving with null values should succeed (iteration $i)"
            );
        }
    }
}
