<?php

namespace Tests\Feature\Properties;

use App\Services\Site\SiteContentSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: wedding-site-builder, Property 8: Validação de Schema JSON do Site
 * 
 * For any valid SiteLayout draft_content, serializing to JSON and deserializing 
 * back SHALL produce an equivalent structure containing all required section keys 
 * (header, hero, saveTheDate, giftRegistry, rsvp, photoGallery, footer).
 * 
 * Validates: Requirements 2.1, 2.3
 */
class JsonSchemaPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property test: JSON round-trip preserves all required sections
     * @test
     */
    public function json_round_trip_preserves_all_required_sections(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Get default content and apply random modifications
            $content = SiteContentSchema::getDefaultContent();
            
            // Apply random modifications to simulate user edits
            $content['sections']['header']['title'] = fake()->words(rand(1, 5), true);
            $content['sections']['header']['subtitle'] = fake()->sentence();
            $content['sections']['header']['enabled'] = fake()->boolean(80);
            
            $content['sections']['hero']['title'] = fake()->words(rand(2, 6), true);
            $content['sections']['hero']['subtitle'] = fake()->sentence();
            $content['sections']['hero']['layout'] = fake()->randomElement(['full-bleed', 'boxed', 'split']);
            
            $content['sections']['saveTheDate']['description'] = fake()->paragraph();
            $content['sections']['saveTheDate']['showCountdown'] = fake()->boolean();
            $content['sections']['saveTheDate']['countdownFormat'] = fake()->randomElement(['days', 'hours', 'minutes', 'full']);
            
            $content['sections']['giftRegistry']['enabled'] = fake()->boolean(30);
            $content['sections']['giftRegistry']['title'] = fake()->words(3, true);
            
            $content['sections']['rsvp']['enabled'] = fake()->boolean(30);
            $content['sections']['rsvp']['title'] = fake()->words(3, true);
            
            $content['sections']['photoGallery']['enabled'] = fake()->boolean(50);
            $content['sections']['photoGallery']['layout'] = fake()->randomElement(['masonry', 'grid', 'slideshow']);
            
            $content['sections']['footer']['copyrightText'] = fake()->sentence();
            $content['sections']['footer']['showBackToTop'] = fake()->boolean();
            
            $content['theme']['primaryColor'] = fake()->hexColor();
            $content['theme']['secondaryColor'] = fake()->hexColor();
            
            $content['meta']['title'] = fake()->words(5, true);
            $content['meta']['description'] = fake()->sentence();

            // Serialize to JSON
            $json = json_encode($content);
            $this->assertNotFalse($json, "Iteration {$i}: Failed to encode content to JSON");

            // Deserialize back
            $decoded = json_decode($json, true);
            $this->assertNotNull($decoded, "Iteration {$i}: Failed to decode JSON back to array");

            // Verify all required sections exist
            foreach (SiteContentSchema::REQUIRED_SECTIONS as $section) {
                $this->assertArrayHasKey(
                    $section,
                    $decoded['sections'],
                    "Iteration {$i}: Missing required section '{$section}' after round-trip"
                );
            }

            // Verify structure is equivalent
            $this->assertEquals(
                $content['version'],
                $decoded['version'],
                "Iteration {$i}: Version mismatch after round-trip"
            );

            // Verify validation passes
            $errors = SiteContentSchema::validate($decoded);
            $this->assertEmpty(
                $errors,
                "Iteration {$i}: Validation failed after round-trip: " . implode(', ', $errors)
            );
        }
    }

    /**
     * @test
     */
    public function default_content_is_valid(): void
    {
        $content = SiteContentSchema::getDefaultContent();
        
        $errors = SiteContentSchema::validate($content);
        
        $this->assertEmpty($errors, 'Default content should be valid: ' . implode(', ', $errors));
    }

    /**
     * @test
     */
    public function default_content_has_all_required_sections(): void
    {
        $content = SiteContentSchema::getDefaultContent();

        foreach (SiteContentSchema::REQUIRED_SECTIONS as $section) {
            $this->assertArrayHasKey(
                $section,
                $content['sections'],
                "Default content missing required section: {$section}"
            );
        }
    }

    /**
     * @test
     */
    public function validation_fails_for_missing_sections(): void
    {
        $content = SiteContentSchema::getDefaultContent();
        
        // Remove a required section
        unset($content['sections']['header']);
        
        $errors = SiteContentSchema::validate($content);
        
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('header', implode(', ', $errors));
    }

    /**
     * @test
     */
    public function validation_fails_for_missing_sections_key(): void
    {
        $content = ['version' => '1.0'];
        
        $errors = SiteContentSchema::validate($content);
        
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('sections', implode(', ', $errors));
    }

    /**
     * @test
     */
    public function each_section_has_enabled_field(): void
    {
        $content = SiteContentSchema::getDefaultContent();

        foreach (SiteContentSchema::REQUIRED_SECTIONS as $section) {
            $this->assertArrayHasKey(
                'enabled',
                $content['sections'][$section],
                "Section '{$section}' should have 'enabled' field"
            );
            $this->assertIsBool(
                $content['sections'][$section]['enabled'],
                "Section '{$section}' enabled field should be boolean"
            );
        }
    }

    /**
     * @test
     */
    public function header_section_has_required_structure(): void
    {
        $header = SiteContentSchema::getHeaderSection();

        $this->assertArrayHasKey('enabled', $header);
        $this->assertArrayHasKey('logo', $header);
        $this->assertArrayHasKey('title', $header);
        $this->assertArrayHasKey('subtitle', $header);
        $this->assertArrayHasKey('showDate', $header);
        $this->assertArrayHasKey('navigation', $header);
        $this->assertArrayHasKey('actionButton', $header);
        $this->assertArrayHasKey('style', $header);
        
        // Verify nested structure
        $this->assertArrayHasKey('url', $header['logo']);
        $this->assertArrayHasKey('alt', $header['logo']);
        $this->assertArrayHasKey('label', $header['actionButton']);
        $this->assertArrayHasKey('target', $header['actionButton']);
        $this->assertArrayHasKey('style', $header['actionButton']);
    }

    /**
     * @test
     */
    public function hero_section_has_required_structure(): void
    {
        $hero = SiteContentSchema::getHeroSection();

        $this->assertArrayHasKey('enabled', $hero);
        $this->assertArrayHasKey('media', $hero);
        $this->assertArrayHasKey('title', $hero);
        $this->assertArrayHasKey('subtitle', $hero);
        $this->assertArrayHasKey('ctaPrimary', $hero);
        $this->assertArrayHasKey('ctaSecondary', $hero);
        $this->assertArrayHasKey('layout', $hero);
        $this->assertArrayHasKey('style', $hero);
        
        // Verify media structure
        $this->assertArrayHasKey('type', $hero['media']);
        $this->assertArrayHasKey('url', $hero['media']);
        $this->assertArrayHasKey('fallback', $hero['media']);
    }

    /**
     * @test
     */
    public function save_the_date_section_has_required_structure(): void
    {
        $saveTheDate = SiteContentSchema::getSaveTheDateSection();

        $this->assertArrayHasKey('enabled', $saveTheDate);
        $this->assertArrayHasKey('showMap', $saveTheDate);
        $this->assertArrayHasKey('mapProvider', $saveTheDate);
        $this->assertArrayHasKey('mapCoordinates', $saveTheDate);
        $this->assertArrayHasKey('description', $saveTheDate);
        $this->assertArrayHasKey('showCountdown', $saveTheDate);
        $this->assertArrayHasKey('countdownFormat', $saveTheDate);
        $this->assertArrayHasKey('showCalendarButton', $saveTheDate);
        $this->assertArrayHasKey('style', $saveTheDate);
        
        // Verify coordinates structure
        $this->assertArrayHasKey('lat', $saveTheDate['mapCoordinates']);
        $this->assertArrayHasKey('lng', $saveTheDate['mapCoordinates']);
    }

    /**
     * @test
     */
    public function photo_gallery_section_has_required_structure(): void
    {
        $gallery = SiteContentSchema::getPhotoGallerySection();

        $this->assertArrayHasKey('enabled', $gallery);
        $this->assertArrayHasKey('albums', $gallery);
        $this->assertArrayHasKey('layout', $gallery);
        $this->assertArrayHasKey('showLightbox', $gallery);
        $this->assertArrayHasKey('allowDownload', $gallery);
        $this->assertArrayHasKey('style', $gallery);
        
        // Verify albums structure
        $this->assertArrayHasKey('before', $gallery['albums']);
        $this->assertArrayHasKey('after', $gallery['albums']);
        $this->assertArrayHasKey('title', $gallery['albums']['before']);
        $this->assertArrayHasKey('photos', $gallery['albums']['before']);
    }

    /**
     * @test
     */
    public function footer_section_has_required_structure(): void
    {
        $footer = SiteContentSchema::getFooterSection();

        $this->assertArrayHasKey('enabled', $footer);
        $this->assertArrayHasKey('socialLinks', $footer);
        $this->assertArrayHasKey('copyrightText', $footer);
        $this->assertArrayHasKey('copyrightYear', $footer);
        $this->assertArrayHasKey('showPrivacyPolicy', $footer);
        $this->assertArrayHasKey('privacyPolicyUrl', $footer);
        $this->assertArrayHasKey('showBackToTop', $footer);
        $this->assertArrayHasKey('style', $footer);
    }
}
