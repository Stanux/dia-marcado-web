<?php

declare(strict_types=1);

namespace Tests\Feature\Properties;

use App\Models\User;
use App\Models\Wedding;
use App\Services\Site\PlaceholderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property tests for PlaceholderService.
 * 
 * Property 9: Substituição de Placeholders
 * Validates: Requirements 22.1-22.7, 7.4
 * 
 * For any Wedding with couple members and a wedding_date, rendering site content
 * with placeholders {noivo}, {noiva}, {data}, {local}, {cidade} SHALL replace
 * each placeholder with the corresponding wedding data. No placeholder tokens
 * SHALL remain in the rendered output.
 */
class PlaceholderPropertyTest extends TestCase
{
    use RefreshDatabase;

    private PlaceholderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlaceholderService();
    }

    /**
     * @test
     * @group property
     */
    public function property_no_placeholders_remain_after_substitution(): void
    {
        // Run 100 iterations with random wedding data
        for ($i = 0; $i < 100; $i++) {
            $wedding = $this->createWeddingWithRandomCoupleCount();
            
            // Content with all placeholders
            $content = 'Casamento de {noivo} e {noiva}. {noivos} se casam em {data} ({data_curta}) no {local}, {cidade}, {estado}. Local completo: {cidade_estado}.';
            
            $result = $this->service->replacePlaceholders($content, $wedding);
            
            // No placeholder tokens should remain
            $this->assertStringNotContainsString('{noivo}', $result, "Iteration $i: {noivo} not replaced");
            $this->assertStringNotContainsString('{noiva}', $result, "Iteration $i: {noiva} not replaced");
            $this->assertStringNotContainsString('{noivos}', $result, "Iteration $i: {noivos} not replaced");
            $this->assertStringNotContainsString('{data}', $result, "Iteration $i: {data} not replaced");
            $this->assertStringNotContainsString('{data_curta}', $result, "Iteration $i: {data_curta} not replaced");
            $this->assertStringNotContainsString('{local}', $result, "Iteration $i: {local} not replaced");
            $this->assertStringNotContainsString('{cidade}', $result, "Iteration $i: {cidade} not replaced");
            $this->assertStringNotContainsString('{estado}', $result, "Iteration $i: {estado} not replaced");
            $this->assertStringNotContainsString('{cidade_estado}', $result, "Iteration $i: {cidade_estado} not replaced");
        }
    }

    /**
     * @test
     * @group property
     */
    public function property_wedding_data_appears_in_output(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create([
                'venue' => 'Venue ' . fake()->company(),
                'city' => 'City ' . fake()->city(),
                'state' => fake()->stateAbbr(),
                'wedding_date' => fake()->dateTimeBetween('+1 month', '+2 years'),
            ]);
            
            // Create couple members with unique names
            $user1 = User::factory()->create(['name' => 'CoupleOne' . $i]);
            $user2 = User::factory()->create(['name' => 'CoupleTwo' . $i]);
            $wedding->users()->attach($user1->id, ['role' => 'couple', 'permissions' => []]);
            $wedding->users()->attach($user2->id, ['role' => 'couple', 'permissions' => []]);
            
            $content = '{noivo} {noiva} {local} {cidade} {estado}';
            $result = $this->service->replacePlaceholders($content, $wedding);
            
            // Wedding data should appear in output
            $this->assertStringContainsString('CoupleOne' . $i, $result, "Iteration $i: First couple name not in output");
            $this->assertStringContainsString('CoupleTwo' . $i, $result, "Iteration $i: Second couple name not in output");
            $this->assertStringContainsString($wedding->venue, $result, "Iteration $i: Venue not in output");
            $this->assertStringContainsString($wedding->city, $result, "Iteration $i: City not in output");
            $this->assertStringContainsString($wedding->state, $result, "Iteration $i: State not in output");
        }
    }

    /**
     * @test
     * @group property
     */
    public function property_date_format_is_correct(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $date = Carbon::create(
                fake()->numberBetween(2024, 2030),
                fake()->numberBetween(1, 12),
                fake()->numberBetween(1, 28)
            );
            
            $wedding = Wedding::factory()->create([
                'wedding_date' => $date,
            ]);
            
            $content = '{data} | {data_curta}';
            $result = $this->service->replacePlaceholders($content, $wedding);
            
            // Long date format: "15 de Março de 2025"
            $this->assertMatchesRegularExpression(
                '/\d{1,2} de [A-Za-zç]+ de \d{4}/',
                $result,
                "Iteration $i: Long date format incorrect"
            );
            
            // Short date format: "15/03/2025"
            $this->assertMatchesRegularExpression(
                '/\d{2}\/\d{2}\/\d{4}/',
                $result,
                "Iteration $i: Short date format incorrect"
            );
        }
    }

    /**
     * @test
     * @group property
     */
    public function property_single_couple_member_uses_same_name(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            
            $user = User::factory()->create(['name' => 'SinglePerson' . $i]);
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            $content = '{noivo} | {noiva} | {noivos}';
            $result = $this->service->replacePlaceholders($content, $wedding);
            
            // With single person, noivo and noiva should be the same
            $parts = explode(' | ', $result);
            $this->assertEquals($parts[0], $parts[1], "Iteration $i: Single person should appear as both noivo and noiva");
            $this->assertEquals($parts[0], $parts[2], "Iteration $i: Single person should appear in noivos");
        }
    }

    /**
     * @test
     * @group property
     */
    public function property_two_couple_members_format_correctly(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            
            $user1 = User::factory()->create(['name' => 'PersonA' . $i]);
            $user2 = User::factory()->create(['name' => 'PersonB' . $i]);
            $wedding->users()->attach($user1->id, ['role' => 'couple', 'permissions' => []]);
            $wedding->users()->attach($user2->id, ['role' => 'couple', 'permissions' => []]);
            
            $content = '{noivos}';
            $result = $this->service->replacePlaceholders($content, $wedding);
            
            // Two people should be "Name1 e Name2"
            $this->assertStringContainsString(' e ', $result, "Iteration $i: Two people should be joined with ' e '");
            $this->assertStringContainsString('PersonA' . $i, $result);
            $this->assertStringContainsString('PersonB' . $i, $result);
        }
    }

    /**
     * @test
     * @group property
     */
    public function property_three_plus_couple_members_format_correctly(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            
            $user1 = User::factory()->create(['name' => 'First' . $i]);
            $user2 = User::factory()->create(['name' => 'Second' . $i]);
            $user3 = User::factory()->create(['name' => 'Third' . $i]);
            $wedding->users()->attach($user1->id, ['role' => 'couple', 'permissions' => []]);
            $wedding->users()->attach($user2->id, ['role' => 'couple', 'permissions' => []]);
            $wedding->users()->attach($user3->id, ['role' => 'couple', 'permissions' => []]);
            
            $content = '{noivos}';
            $result = $this->service->replacePlaceholders($content, $wedding);
            
            // Three+ people should be "Name1, Name2 e Name3"
            $this->assertStringContainsString(', ', $result, "Iteration $i: Three+ people should have comma");
            $this->assertStringContainsString(' e ', $result, "Iteration $i: Three+ people should have ' e ' before last");
            $this->assertStringContainsString('First' . $i, $result);
            $this->assertStringContainsString('Second' . $i, $result);
            $this->assertStringContainsString('Third' . $i, $result);
        }
    }

    /**
     * @test
     * @group property
     */
    public function property_cidade_estado_format_is_correct(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $city = 'City' . $i;
            $state = 'ST';
            
            $wedding = Wedding::factory()->create([
                'city' => $city,
                'state' => $state,
            ]);
            
            $content = '{cidade_estado}';
            $result = $this->service->replacePlaceholders($content, $wedding);
            
            // Should be "City - State"
            $this->assertEquals($city . ' - ' . $state, $result, "Iteration $i: cidade_estado format incorrect");
        }
    }

    /**
     * @test
     * @group property
     */
    public function property_array_replacement_works_recursively(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create([
                'venue' => 'TestVenue' . $i,
                'city' => 'TestCity' . $i,
            ]);
            
            $user = User::factory()->create(['name' => 'TestPerson' . $i]);
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            $content = [
                'title' => 'Casamento de {noivo}',
                'nested' => [
                    'location' => '{local} em {cidade}',
                    'deep' => [
                        'info' => '{noivos}',
                    ],
                ],
                'number' => 42,
                'boolean' => true,
            ];
            
            $result = $this->service->replaceInArray($content, $wedding);
            
            // All placeholders should be replaced
            $this->assertStringContainsString('TestPerson' . $i, $result['title']);
            $this->assertStringContainsString('TestVenue' . $i, $result['nested']['location']);
            $this->assertStringContainsString('TestCity' . $i, $result['nested']['location']);
            $this->assertStringContainsString('TestPerson' . $i, $result['nested']['deep']['info']);
            
            // Non-string values should be preserved
            $this->assertEquals(42, $result['number']);
            $this->assertTrue($result['boolean']);
            
            // No placeholders should remain (check for placeholder pattern {word})
            $jsonResult = json_encode($result);
            $this->assertDoesNotMatchRegularExpression('/\{[a-z_]+\}/', $jsonResult, "Iteration $i: Placeholders remain in result");
        }
    }

    /**
     * @test
     * @group property
     */
    public function property_empty_wedding_data_produces_empty_strings(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create([
                'venue' => null,
                'city' => null,
                'state' => null,
                'wedding_date' => null,
            ]);
            
            $content = '{local}|{cidade}|{estado}|{cidade_estado}|{data}|{data_curta}';
            $result = $this->service->replacePlaceholders($content, $wedding);
            
            // Should have empty strings but no placeholders
            $this->assertStringNotContainsString('{', $result, "Iteration $i: Placeholders should be replaced even with null data");
        }
    }

    /**
     * @test
     * @group property
     */
    public function get_available_placeholders_returns_all_supported(): void
    {
        $placeholders = $this->service->getAvailablePlaceholders();
        
        $expectedKeys = [
            '{noivo}',
            '{noiva}',
            '{noivos}',
            '{data}',
            '{data_curta}',
            '{local}',
            '{cidade}',
            '{estado}',
            '{cidade_estado}',
        ];
        
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $placeholders, "Missing placeholder: $key");
            $this->assertNotEmpty($placeholders[$key], "Empty description for: $key");
        }
    }

    /**
     * Helper to create a wedding with random number of couple members (1-4).
     */
    private function createWeddingWithRandomCoupleCount(): Wedding
    {
        $wedding = Wedding::factory()->create();
        
        $coupleCount = fake()->numberBetween(1, 4);
        
        for ($j = 0; $j < $coupleCount; $j++) {
            $user = User::factory()->create();
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
        }
        
        return $wedding;
    }
}
