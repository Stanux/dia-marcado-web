<?php

namespace Tests\Feature\Property;

use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: wedding-saas-foundation, Property 6: JSONB Settings Round-Trip
 * 
 * For any valid settings object stored in the weddings table,
 * serializing to JSONB and deserializing should produce an equivalent object.
 * 
 * Validates: Requirements 5.4
 */
class JsonbSettingsRoundTripTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider settingsProvider
     */
    public function jsonb_settings_round_trip_preserves_data(array $settings): void
    {
        // Create wedding with settings
        $wedding = Wedding::create([
            'title' => 'Test Wedding',
            'settings' => $settings,
        ]);

        // Retrieve from database
        $retrieved = Wedding::find($wedding->id);

        // Assert round-trip preserves data
        $this->assertEquals($settings, $retrieved->settings);
    }

    /**
     * Property test: run 100 iterations with random settings
     * @test
     */
    public function jsonb_settings_round_trip_property_test(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $settings = $this->generateRandomSettings();

            $wedding = Wedding::create([
                'title' => "Wedding {$i}",
                'settings' => $settings,
            ]);

            $retrieved = Wedding::find($wedding->id);

            $this->assertEquals(
                $settings,
                $retrieved->settings,
                "Round-trip failed for iteration {$i} with settings: " . json_encode($settings)
            );
        }
    }

    /**
     * Generate random settings for property testing
     */
    private function generateRandomSettings(): array
    {
        $settings = [];

        // Random theme settings
        if (rand(0, 1)) {
            $settings['theme'] = [
                'primary_color' => '#' . str_pad(dechex(rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
                'secondary_color' => '#' . str_pad(dechex(rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
                'font_family' => fake()->randomElement(['Roboto', 'Open Sans', 'Lato', 'Montserrat']),
            ];
        }

        // Random notification settings
        if (rand(0, 1)) {
            $settings['notifications'] = [
                'email_enabled' => (bool) rand(0, 1),
                'sms_enabled' => (bool) rand(0, 1),
                'push_enabled' => (bool) rand(0, 1),
            ];
        }

        // Random feature flags
        if (rand(0, 1)) {
            $settings['features'] = [
                'gamification' => (bool) rand(0, 1),
                'rsvp' => (bool) rand(0, 1),
                'gift_registry' => (bool) rand(0, 1),
                'photo_gallery' => (bool) rand(0, 1),
            ];
        }

        // Random nested data
        if (rand(0, 1)) {
            $settings['custom'] = [
                'string_value' => fake()->sentence(),
                'number_value' => rand(1, 1000),
                'boolean_value' => (bool) rand(0, 1),
                'array_value' => array_map(fn() => fake()->word(), range(1, rand(1, 5))),
                'nested' => [
                    'level2' => [
                        'value' => fake()->word(),
                    ],
                ],
            ];
        }

        return $settings;
    }

    public static function settingsProvider(): array
    {
        return [
            'empty settings' => [[]],
            'simple string' => [['key' => 'value']],
            'nested object' => [['theme' => ['color' => '#ffffff', 'font' => 'Arial']]],
            'array values' => [['tags' => ['wedding', 'celebration', 'love']]],
            'mixed types' => [[
                'string' => 'text',
                'number' => 42,
                'boolean' => true,
                'null' => null,
                'array' => [1, 2, 3],
                'object' => ['nested' => 'value'],
            ]],
            'unicode characters' => [['message' => 'Parabéns! 🎉 Casamento de João & Maria']],
            'special characters' => [['description' => "Line1\nLine2\tTabbed \"quoted\""]],
        ];
    }
}
