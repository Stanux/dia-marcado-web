<?php

namespace Tests\Unit\Services;

use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\Wedding;
use App\Services\Site\QAResult;
use App\Services\Site\SiteContentSchema;
use App\Services\Site\SiteValidatorService;
use App\Services\Site\ValidationResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for SiteValidatorService.
 * 
 * Tests validation of required fields, URLs, and accessibility checks.
 * 
 * Validates: Requirements 17.1-17.5
 */
class SiteValidatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private SiteValidatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SiteValidatorService();
    }

    /**
     * @test
     */
    public function validate_for_publish_fails_when_meta_title_is_empty(): void
    {
        $wedding = Wedding::factory()->create();
        $site = SiteLayout::factory()->create([
            'wedding_id' => $wedding->id,
            'draft_content' => array_merge(
                SiteContentSchema::getDefaultContent(),
                ['meta' => ['title' => '', 'description' => '', 'ogImage' => '', 'canonical' => '']]
            ),
        ]);

        $result = $this->service->validateForPublish($site);

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
        $this->assertStringContainsString('título', strtolower(implode(' ', $result->getErrors())));
    }

    /**
     * @test
     */
    public function validate_for_publish_fails_when_no_section_enabled(): void
    {
        $wedding = Wedding::factory()->create();
        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Test Site';
        $content['sections']['header']['enabled'] = false;
        $content['sections']['hero']['enabled'] = false;

        $site = SiteLayout::factory()->create([
            'wedding_id' => $wedding->id,
            'draft_content' => $content,
        ]);

        $result = $this->service->validateForPublish($site);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('seção', strtolower(implode(' ', $result->getErrors())));
    }

    /**
     * @test
     */
    public function validate_for_publish_passes_with_valid_content(): void
    {
        $wedding = Wedding::factory()->create();
        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Our Wedding';
        $content['sections']['header']['enabled'] = true;
        $content['sections']['header']['title'] = 'Welcome';
        // Disable hero to avoid validation issues
        $content['sections']['hero']['enabled'] = false;
        // Disable saveTheDate map or provide coordinates
        $content['sections']['saveTheDate']['showMap'] = false;

        $site = SiteLayout::factory()->create([
            'wedding_id' => $wedding->id,
            'draft_content' => $content,
        ]);

        $result = $this->service->validateForPublish($site);

        $this->assertTrue($result->isValid(), 'Errors: ' . implode(', ', $result->getErrors()));
        $this->assertEmpty($result->getErrors());
    }


    /**
     * @test
     */
    public function validate_header_section_fails_when_title_empty(): void
    {
        $content = SiteContentSchema::getHeaderSection();
        $content['enabled'] = true;
        $content['title'] = '';

        $result = $this->service->validateSection('header', $content);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Header', implode(' ', $result->getErrors()));
    }

    /**
     * @test
     */
    public function validate_header_section_warns_when_logo_missing_alt(): void
    {
        $content = SiteContentSchema::getHeaderSection();
        $content['enabled'] = true;
        $content['title'] = 'Test Title';
        $content['logo']['url'] = 'https://example.com/logo.png';
        $content['logo']['alt'] = '';

        $result = $this->service->validateSection('header', $content);

        $this->assertTrue($result->hasWarnings());
        $this->assertStringContainsString('alt', strtolower(implode(' ', $result->getWarnings())));
    }

    /**
     * @test
     */
    public function validate_hero_section_fails_when_no_media_or_title(): void
    {
        $content = SiteContentSchema::getHeroSection();
        $content['enabled'] = true;
        $content['media']['url'] = '';
        $content['title'] = '';

        $result = $this->service->validateSection('hero', $content);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Hero', implode(' ', $result->getErrors()));
    }

    /**
     * @test
     */
    public function validate_hero_section_passes_with_title_only(): void
    {
        $content = SiteContentSchema::getHeroSection();
        $content['enabled'] = true;
        $content['media']['url'] = '';
        $content['title'] = 'Our Wedding Day';

        $result = $this->service->validateSection('hero', $content);

        $this->assertTrue($result->isValid());
    }

    /**
     * @test
     */
    public function validate_save_the_date_fails_when_map_enabled_without_coordinates(): void
    {
        $content = SiteContentSchema::getSaveTheDateSection();
        $content['enabled'] = true;
        $content['showMap'] = true;
        $content['mapCoordinates']['lat'] = null;
        $content['mapCoordinates']['lng'] = null;

        $result = $this->service->validateSection('saveTheDate', $content);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('coordenadas', strtolower(implode(' ', $result->getErrors())));
    }

    /**
     * @test
     */
    public function validate_save_the_date_fails_with_invalid_coordinates(): void
    {
        $content = SiteContentSchema::getSaveTheDateSection();
        $content['enabled'] = true;
        $content['showMap'] = true;
        $content['mapCoordinates']['lat'] = 100; // Invalid: > 90
        $content['mapCoordinates']['lng'] = -45;

        $result = $this->service->validateSection('saveTheDate', $content);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('inválidas', strtolower(implode(' ', $result->getErrors())));
    }

    /**
     * @test
     */
    public function validate_save_the_date_passes_with_valid_coordinates(): void
    {
        $content = SiteContentSchema::getSaveTheDateSection();
        $content['enabled'] = true;
        $content['showMap'] = true;
        $content['mapCoordinates']['lat'] = -23.5505;
        $content['mapCoordinates']['lng'] = -46.6333;

        $result = $this->service->validateSection('saveTheDate', $content);

        $this->assertTrue($result->isValid());
    }

    /**
     * @test
     */
    public function validate_footer_fails_when_privacy_enabled_without_url(): void
    {
        $content = SiteContentSchema::getFooterSection();
        $content['enabled'] = true;
        $content['showPrivacyPolicy'] = true;
        $content['privacyPolicyUrl'] = '';

        $result = $this->service->validateSection('footer', $content);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('privacidade', strtolower(implode(' ', $result->getErrors())));
    }

    /**
     * @test
     */
    public function validate_footer_fails_with_invalid_privacy_url(): void
    {
        $content = SiteContentSchema::getFooterSection();
        $content['enabled'] = true;
        $content['showPrivacyPolicy'] = true;
        $content['privacyPolicyUrl'] = 'not-a-valid-url';

        $result = $this->service->validateSection('footer', $content);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('inválida', strtolower(implode(' ', $result->getErrors())));
    }


    /**
     * @test
     */
    public function check_accessibility_detects_missing_logo_alt(): void
    {
        $content = SiteContentSchema::getDefaultContent();
        $content['sections']['header']['enabled'] = true;
        $content['sections']['header']['logo']['url'] = 'https://example.com/logo.png';
        $content['sections']['header']['logo']['alt'] = '';

        $warnings = $this->service->checkAccessibility($content);

        $this->assertNotEmpty($warnings);
        $altWarnings = array_filter($warnings, fn($w) => $w['type'] === 'missing_alt');
        $this->assertNotEmpty($altWarnings);
    }

    /**
     * @test
     */
    public function check_accessibility_detects_low_contrast(): void
    {
        $content = SiteContentSchema::getDefaultContent();
        $content['sections']['header']['enabled'] = true;
        $content['sections']['header']['style']['backgroundColor'] = '#ffffff';
        $content['theme']['primaryColor'] = '#cccccc'; // Low contrast with white

        $warnings = $this->service->checkAccessibility($content);

        $contrastWarnings = array_filter($warnings, fn($w) => $w['type'] === 'low_contrast');
        $this->assertNotEmpty($contrastWarnings);
    }

    /**
     * @test
     */
    public function check_accessibility_passes_with_good_contrast(): void
    {
        $content = SiteContentSchema::getDefaultContent();
        $content['sections']['header']['enabled'] = true;
        $content['sections']['header']['style']['backgroundColor'] = '#ffffff';
        $content['theme']['primaryColor'] = '#000000'; // High contrast

        $warnings = $this->service->checkAccessibility($content);

        $contrastWarnings = array_filter($warnings, fn($w) => $w['type'] === 'low_contrast');
        $this->assertEmpty($contrastWarnings);
    }

    /**
     * @test
     */
    public function run_qa_checklist_returns_qa_result(): void
    {
        $wedding = Wedding::factory()->create();
        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Test Wedding';
        $content['sections']['header']['enabled'] = true;
        $content['sections']['header']['title'] = 'Welcome';

        $site = SiteLayout::factory()->create([
            'wedding_id' => $wedding->id,
            'draft_content' => $content,
        ]);

        $result = $this->service->runQAChecklist($site);

        $this->assertInstanceOf(QAResult::class, $result);
        $this->assertNotEmpty($result->getChecks());
    }

    /**
     * @test
     */
    public function run_qa_checklist_checks_required_fields(): void
    {
        $wedding = Wedding::factory()->create();
        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = ''; // Missing required field

        $site = SiteLayout::factory()->create([
            'wedding_id' => $wedding->id,
            'draft_content' => $content,
        ]);

        $result = $this->service->runQAChecklist($site);

        $failedChecks = $result->getFailedChecks();
        $requiredFieldsCheck = array_filter($failedChecks, fn($c) => $c['name'] === 'required_fields');
        $this->assertNotEmpty($requiredFieldsCheck);
    }

    /**
     * @test
     */
    public function run_qa_checklist_checks_valid_links(): void
    {
        $wedding = Wedding::factory()->create();
        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Test';
        $content['sections']['header']['enabled'] = true;
        $content['sections']['header']['title'] = 'Test';
        $content['sections']['footer']['enabled'] = true;
        $content['sections']['footer']['socialLinks'] = [
            ['platform' => 'instagram', 'url' => 'invalid-url', 'icon' => 'instagram'],
        ];

        $site = SiteLayout::factory()->create([
            'wedding_id' => $wedding->id,
            'draft_content' => $content,
        ]);

        $result = $this->service->runQAChecklist($site);

        $failedChecks = $result->getFailedChecks();
        $linksCheck = array_filter($failedChecks, fn($c) => $c['name'] === 'valid_links');
        $this->assertNotEmpty($linksCheck);
    }

    /**
     * @test
     */
    public function qa_result_can_publish_when_no_failures(): void
    {
        $wedding = Wedding::factory()->create();
        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Our Wedding';
        $content['sections']['header']['enabled'] = true;
        $content['sections']['header']['title'] = 'Welcome';

        $site = SiteLayout::factory()->create([
            'wedding_id' => $wedding->id,
            'draft_content' => $content,
        ]);

        $result = $this->service->runQAChecklist($site);

        $this->assertTrue($result->canPublish());
    }

    /**
     * @test
     */
    public function qa_result_cannot_publish_when_failures_exist(): void
    {
        $wedding = Wedding::factory()->create();
        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = ''; // Missing required

        $site = SiteLayout::factory()->create([
            'wedding_id' => $wedding->id,
            'draft_content' => $content,
        ]);

        $result = $this->service->runQAChecklist($site);

        $this->assertFalse($result->canPublish());
    }

    /**
     * @test
     */
    public function validate_section_returns_success_for_disabled_section(): void
    {
        $content = SiteContentSchema::getHeaderSection();
        $content['enabled'] = false;
        $content['title'] = ''; // Would fail if enabled

        $result = $this->service->validateSection('header', $content);

        $this->assertTrue($result->isValid());
    }

    /**
     * @test
     */
    public function validate_photo_gallery_warns_about_missing_alt_text(): void
    {
        $content = SiteContentSchema::getPhotoGallerySection();
        $content['enabled'] = true;
        $content['albums']['before']['photos'] = [
            ['url' => 'https://example.com/photo1.jpg', 'alt' => ''],
            ['url' => 'https://example.com/photo2.jpg', 'alt' => 'Nice photo'],
        ];

        $result = $this->service->validateSection('photoGallery', $content);

        $this->assertTrue($result->hasWarnings());
    }
}
