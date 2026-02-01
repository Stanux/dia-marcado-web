<?php

namespace Tests\Feature\Properties;

use App\Models\User;
use App\Models\Wedding;
use App\Services\Site\SlugGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: wedding-site-builder, Property 12: Geração de Slug a partir de Nomes
 * 
 * For any Wedding with couple members having names, the generated slug SHALL 
 * contain normalized (lowercase, hyphenated) versions of at least one couple 
 * member's name.
 * 
 * Validates: Requirements 5.1
 */
class SlugGenerationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private SlugGeneratorService $slugService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->slugService = new SlugGeneratorService();
    }

    /**
     * Property test: Generated slug contains normalized name from couple
     * @test
     */
    public function generated_slug_contains_normalized_name_from_couple(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Create wedding with random couple members
            $wedding = Wedding::factory()->create();
            
            $numCoupleMembers = fake()->numberBetween(1, 3);
            $names = [];
            
            for ($j = 0; $j < $numCoupleMembers; $j++) {
                $user = User::factory()->create([
                    'name' => fake()->firstName() . ' ' . fake()->lastName(),
                ]);
                $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
                $names[] = $user->name;
            }

            // Generate slug
            $slug = $this->slugService->generate($wedding);

            // Verify slug doesn't contain special characters or spaces
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9-]+$/',
                $slug,
                "Iteration {$i}: Slug '{$slug}' contains invalid characters"
            );

            // Verify slug contains at least part of one name (normalized)
            $containsName = false;
            foreach ($names as $name) {
                $firstName = explode(' ', $name)[0];
                $normalizedFirstName = $this->slugService->normalize($firstName);
                
                if (str_contains($slug, $normalizedFirstName)) {
                    $containsName = true;
                    break;
                }
            }

            // For weddings with couple members, slug should contain a name
            // Exception: if all names normalize to empty strings
            $hasValidNames = collect($names)->some(function ($name) {
                return !empty($this->slugService->normalize(explode(' ', $name)[0]));
            });

            if ($hasValidNames) {
                $this->assertTrue(
                    $containsName,
                    "Iteration {$i}: Slug '{$slug}' should contain normalized name from: " . implode(', ', $names)
                );
            }

            // Cleanup
            $wedding->users()->detach();
            $wedding->delete();
        }
    }

    /**
     * @test
     */
    public function slug_does_not_contain_special_characters(): void
    {
        for ($i = 0; $i < 50; $i++) {
            // Create wedding with names containing special characters
            $wedding = Wedding::factory()->create();
            
            $specialNames = [
                'José María',
                'François',
                'Müller',
                'Søren',
                'Björk',
                'Çağla',
                'Łukasz',
                'Ñoño',
            ];
            
            $name = fake()->randomElement($specialNames) . ' ' . fake()->lastName();
            $user = User::factory()->create(['name' => $name]);
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);

            $slug = $this->slugService->generate($wedding);

            // Verify no special characters
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9-]+$/',
                $slug,
                "Iteration {$i}: Slug '{$slug}' from name '{$name}' contains special characters"
            );

            // Cleanup
            $wedding->users()->detach();
            $wedding->delete();
        }
    }

    /**
     * @test
     */
    public function normalize_removes_accents(): void
    {
        $testCases = [
            'José' => 'jose',
            'François' => 'francois',
            'Müller' => 'muller',
            'Søren' => 'soren',
            'Björk' => 'bjork',
            'Çağla' => 'cagla',
            'María' => 'maria',
            'Ñoño' => 'nono',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->slugService->normalize($input);
            $this->assertEquals(
                $expected,
                $result,
                "normalize('{$input}') should return '{$expected}', got '{$result}'"
            );
        }
    }

    /**
     * @test
     */
    public function normalize_replaces_spaces_with_hyphens(): void
    {
        $result = $this->slugService->normalize('João e Maria');
        $this->assertEquals('joao-e-maria', $result);
    }

    /**
     * @test
     */
    public function normalize_removes_duplicate_hyphens(): void
    {
        $result = $this->slugService->normalize('João   e   Maria');
        $this->assertEquals('joao-e-maria', $result);
    }

    /**
     * @test
     */
    public function normalize_limits_length_to_100_characters(): void
    {
        $longName = str_repeat('a', 150);
        $result = $this->slugService->normalize($longName);
        
        $this->assertLessThanOrEqual(100, strlen($result));
    }

    /**
     * @test
     */
    public function generate_creates_slug_for_two_people(): void
    {
        $wedding = Wedding::factory()->create();
        
        $user1 = User::factory()->create(['name' => 'João Silva']);
        $user2 = User::factory()->create(['name' => 'Maria Santos']);
        
        $wedding->users()->attach($user1->id, ['role' => 'couple', 'permissions' => []]);
        $wedding->users()->attach($user2->id, ['role' => 'couple', 'permissions' => []]);

        $slug = $this->slugService->generate($wedding);

        $this->assertStringContainsString('e', $slug);
        $this->assertTrue(
            str_contains($slug, 'joao') || str_contains($slug, 'maria'),
            "Slug should contain 'joao' or 'maria'"
        );
    }

    /**
     * @test
     */
    public function generate_creates_slug_for_one_person(): void
    {
        $wedding = Wedding::factory()->create();
        
        $user = User::factory()->create(['name' => 'João Silva']);
        $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);

        $slug = $this->slugService->generate($wedding);

        $this->assertStringContainsString('casamento', $slug);
        $this->assertStringContainsString('joao', $slug);
    }

    /**
     * @test
     */
    public function generate_creates_slug_for_three_or_more_people(): void
    {
        $wedding = Wedding::factory()->create();
        
        $user1 = User::factory()->create(['name' => 'João Silva']);
        $user2 = User::factory()->create(['name' => 'Maria Santos']);
        $user3 = User::factory()->create(['name' => 'Pedro Costa']);
        
        $wedding->users()->attach($user1->id, ['role' => 'couple', 'permissions' => []]);
        $wedding->users()->attach($user2->id, ['role' => 'couple', 'permissions' => []]);
        $wedding->users()->attach($user3->id, ['role' => 'couple', 'permissions' => []]);

        $slug = $this->slugService->generate($wedding);

        $this->assertStringContainsString('outros', $slug);
    }
}
