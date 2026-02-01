<?php

namespace Tests\Feature\Properties;

use App\Contracts\Site\SiteVersionServiceInterface;
use App\Models\SiteLayout;
use App\Models\SiteVersion;
use App\Models\User;
use App\Models\Wedding;
use App\Services\Site\SiteVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: wedding-site-builder, Property 5: Round-trip de Restauração de Versão
 * 
 * For any SiteLayout and any of its SiteVersions, after calling restore(version), 
 * the draft_content SHALL be equal to the version's content.
 * 
 * Validates: Requirements 4.4
 */
class RestoreRoundTripPropertyTest extends TestCase
{
    use RefreshDatabase;

    private SiteVersionServiceInterface $versionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->versionService = new SiteVersionService();
    }

    /**
     * Property test: Restore sets draft_content equal to version content
     * @test
     */
    public function restore_sets_draft_content_equal_to_version_content(): void
    {
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site-restore-' . $wedding->id,
            'draft_content' => ['version' => '1.0', 'sections' => []],
        ]);

        // Create multiple versions with different content
        $versions = [];
        for ($i = 0; $i < 10; $i++) {
            $content = [
                'version' => '1.0',
                'iteration' => $i,
                'sections' => [
                    'header' => ['enabled' => true, 'title' => "Header {$i}"],
                    'hero' => ['enabled' => fake()->boolean(), 'media' => fake()->imageUrl()],
                ],
                'meta' => [
                    'title' => fake()->sentence(),
                    'description' => fake()->paragraph(),
                ],
                'randomData' => fake()->uuid(),
            ];
            
            $version = $this->versionService->createVersion(
                $site,
                $content,
                $user,
                "Version {$i}: " . fake()->sentence()
            );
            $versions[] = $version;
        }

        // For each version, restore and verify draft_content equals version content
        foreach ($versions as $index => $version) {
            // Refresh version to get latest data
            $version->refresh();
            
            // Store the original version content
            $originalVersionContent = $version->content;
            
            // Restore the version
            $restoredSite = $this->versionService->restore($site, $version);
            
            // Verify draft_content equals the version's content
            $this->assertEquals(
                $originalVersionContent,
                $restoredSite->draft_content,
                "Iteration {$index}: draft_content should equal version content after restore"
            );
            
            // Refresh site for next iteration
            $site->refresh();
        }
    }

    /**
     * Property test: Restore creates a new version with restoration summary
     * @test
     */
    public function restore_creates_new_version_with_restoration_summary(): void
    {
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site-restore-summary',
            'draft_content' => ['version' => '1.0'],
        ]);

        // Create a version
        $originalContent = [
            'version' => '1.0',
            'sections' => ['header' => ['enabled' => true]],
        ];
        
        $version = $this->versionService->createVersion(
            $site,
            $originalContent,
            $user,
            'Original version'
        );

        // Count versions before restore
        $versionCountBefore = SiteVersion::where('site_layout_id', $site->id)->count();

        // Restore the version
        $this->versionService->restore($site, $version);

        // Count versions after restore
        $versionCountAfter = SiteVersion::where('site_layout_id', $site->id)->count();

        // Verify a new version was created
        $this->assertEquals(
            $versionCountBefore + 1,
            $versionCountAfter,
            'Restore should create a new version'
        );

        // Verify the new version has restoration summary
        $latestVersion = SiteVersion::where('site_layout_id', $site->id)
            ->orderByDesc('created_at')
            ->first();

        $this->assertStringContains(
            'Restaurado da versão de',
            $latestVersion->summary,
            'New version should have restoration summary'
        );
    }

    /**
     * Property test: Multiple restores maintain content integrity
     * @test
     */
    public function multiple_restores_maintain_content_integrity(): void
    {
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site-multi-restore',
            'draft_content' => ['version' => '1.0'],
        ]);

        // Create versions with distinct content
        $contents = [];
        for ($i = 0; $i < 5; $i++) {
            $contents[$i] = [
                'version' => '1.0',
                'uniqueMarker' => "content-{$i}-" . fake()->uuid(),
                'iteration' => $i,
            ];
            
            $this->versionService->createVersion(
                $site,
                $contents[$i],
                $user,
                "Version {$i}"
            );
        }

        // Get all versions
        $versions = SiteVersion::where('site_layout_id', $site->id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Restore each version multiple times and verify content
        for ($round = 0; $round < 3; $round++) {
            foreach ($versions as $index => $version) {
                if ($index >= count($contents)) {
                    continue; // Skip restoration versions
                }
                
                $expectedContent = $version->content;
                
                $restoredSite = $this->versionService->restore($site, $version);
                
                $this->assertEquals(
                    $expectedContent,
                    $restoredSite->draft_content,
                    "Round {$round}, Version {$index}: Content should match after restore"
                );
                
                $site->refresh();
            }
        }
    }

    /**
     * Property test: Restore preserves version content immutability
     * @test
     */
    public function restore_preserves_version_content_immutability(): void
    {
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site-immutable',
            'draft_content' => ['version' => '1.0'],
        ]);

        // Create a version with specific content
        $originalContent = [
            'version' => '1.0',
            'immutableData' => fake()->uuid(),
            'sections' => [
                'header' => ['enabled' => true, 'title' => 'Original Title'],
            ],
        ];
        
        $version = $this->versionService->createVersion(
            $site,
            $originalContent,
            $user,
            'Immutable version'
        );

        // Store the original content
        $contentBeforeRestore = $version->content;

        // Restore the version
        $this->versionService->restore($site, $version);

        // Refresh version from database
        $version->refresh();

        // Verify version content was not modified
        $this->assertEquals(
            $contentBeforeRestore,
            $version->content,
            'Version content should remain unchanged after restore'
        );
    }

    /**
     * Property test: Restore works with complex nested content
     * @test
     */
    public function restore_works_with_complex_nested_content(): void
    {
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site-complex',
            'draft_content' => ['version' => '1.0'],
        ]);

        // Create version with complex nested content
        for ($i = 0; $i < 20; $i++) {
            $complexContent = [
                'version' => '1.0',
                'sections' => [
                    'header' => [
                        'enabled' => fake()->boolean(),
                        'logo' => ['url' => fake()->imageUrl(), 'alt' => fake()->sentence()],
                        'navigation' => array_map(fn() => [
                            'label' => fake()->word(),
                            'target' => fake()->url(),
                            'type' => fake()->randomElement(['anchor', 'url', 'action']),
                        ], range(1, fake()->numberBetween(1, 5))),
                        'style' => [
                            'height' => fake()->randomElement(['60px', '80px', '100px']),
                            'backgroundColor' => fake()->hexColor(),
                            'sticky' => fake()->boolean(),
                        ],
                    ],
                    'hero' => [
                        'enabled' => fake()->boolean(),
                        'media' => [
                            'type' => fake()->randomElement(['image', 'video', 'gallery']),
                            'url' => fake()->imageUrl(),
                        ],
                        'style' => [
                            'overlay' => [
                                'color' => fake()->hexColor(),
                                'opacity' => fake()->randomFloat(2, 0, 1),
                            ],
                        ],
                    ],
                    'photoGallery' => [
                        'enabled' => fake()->boolean(),
                        'albums' => [
                            'before' => [
                                'title' => fake()->sentence(),
                                'photos' => array_map(fn() => [
                                    'url' => fake()->imageUrl(),
                                    'caption' => fake()->sentence(),
                                ], range(1, fake()->numberBetween(1, 10))),
                            ],
                        ],
                    ],
                ],
                'meta' => [
                    'title' => fake()->sentence(),
                    'description' => fake()->paragraph(),
                    'ogImage' => fake()->imageUrl(),
                ],
                'theme' => [
                    'primaryColor' => fake()->hexColor(),
                    'secondaryColor' => fake()->hexColor(),
                    'fontFamily' => fake()->randomElement(['Arial', 'Helvetica', 'Georgia']),
                ],
            ];

            $version = $this->versionService->createVersion(
                $site,
                $complexContent,
                $user,
                "Complex version {$i}"
            );

            // Restore and verify
            $restoredSite = $this->versionService->restore($site, $version);

            $this->assertEquals(
                $complexContent,
                $restoredSite->draft_content,
                "Iteration {$i}: Complex nested content should be preserved after restore"
            );

            $site->refresh();
        }
    }

    /**
     * Helper method to check if string contains substring
     */
    private function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            $message ?: "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
