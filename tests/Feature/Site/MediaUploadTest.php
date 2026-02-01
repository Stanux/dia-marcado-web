<?php

namespace Tests\Feature\Site;

use App\Contracts\Site\MediaUploadServiceInterface;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\SystemConfig;
use App\Models\User;
use App\Models\Wedding;
use App\Services\Site\SiteContentSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Integration tests for media upload functionality.
 * 
 * @Requirements: 16.1-16.8
 */
class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private MediaUploadServiceInterface $mediaService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->mediaService = app(MediaUploadServiceInterface::class);
        
        // Seed system configs
        $this->seedSystemConfigs();
    }

    private function seedSystemConfigs(): void
    {
        SystemConfig::updateOrCreate(
            ['key' => 'site.allowed_extensions'],
            ['value' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']]
        );
        SystemConfig::updateOrCreate(
            ['key' => 'site.blocked_extensions'],
            ['value' => ['exe', 'bat', 'sh', 'php', 'js', 'html']]
        );
        SystemConfig::updateOrCreate(
            ['key' => 'site.max_file_size'],
            ['value' => 10485760] // 10MB
        );
        SystemConfig::updateOrCreate(
            ['key' => 'site.max_storage_per_wedding'],
            ['value' => 524288000] // 500MB
        );
    }

    /**
     * @test
     */
    public function valid_image_upload_is_accepted(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'upload-test-site',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        // Act
        $result = $this->mediaService->validateFile($file);

        // Assert
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * @test
     */
    public function blocked_extension_is_rejected(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        // Act
        $result = $this->mediaService->validateFile($file);

        // Assert
        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
    }

    /**
     * @test
     */
    public function non_allowed_extension_is_rejected(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Act
        $result = $this->mediaService->validateFile($file);

        // Assert
        $this->assertFalse($result->isValid());
    }

    /**
     * @test
     */
    public function file_exceeding_max_size_is_rejected(): void
    {
        // Arrange
        // Create file larger than 10MB
        $file = UploadedFile::fake()->create('large-file.jpg', 15000, 'image/jpeg');

        // Act
        $result = $this->mediaService->validateFile($file);

        // Assert
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('limite', strtolower(implode(' ', $result->getErrors())));
    }

    /**
     * @test
     */
    public function upload_creates_media_record(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'media-record-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        $file = UploadedFile::fake()->image('test-photo.jpg', 800, 600);

        // Act
        $media = $this->mediaService->upload($file, $site);

        // Assert
        $this->assertInstanceOf(SiteMedia::class, $media);
        $this->assertEquals($site->id, $media->site_layout_id);
        $this->assertEquals($wedding->id, $media->wedding_id);
        $this->assertEquals('test-photo.jpg', $media->original_name);
        $this->assertEquals('image/jpeg', $media->mime_type);
        $this->assertGreaterThan(0, $media->size);
    }

    /**
     * @test
     */
    public function upload_stores_file_in_correct_directory(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'storage-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        $file = UploadedFile::fake()->image('storage-test.jpg', 400, 300);

        // Act
        $media = $this->mediaService->upload($file, $site);

        // Assert
        $this->assertStringContainsString($wedding->id, $media->path);
        Storage::disk('local')->assertExists($media->path);
    }

    /**
     * @test
     */
    public function get_storage_usage_returns_correct_total(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'usage-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        // Upload multiple files
        $file1 = UploadedFile::fake()->image('photo1.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('photo2.jpg', 800, 600);
        
        $media1 = $this->mediaService->upload($file1, $site);
        $media2 = $this->mediaService->upload($file2, $site);

        // Act
        $usage = $this->mediaService->getStorageUsage($wedding);

        // Assert
        $this->assertEquals($media1->size + $media2->size, $usage);
    }

    /**
     * @test
     */
    public function delete_removes_file_and_record(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'delete-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        $file = UploadedFile::fake()->image('to-delete.jpg', 400, 300);
        $media = $this->mediaService->upload($file, $site);
        $mediaId = $media->id;
        $filePath = $media->path;

        // Act
        $result = $this->mediaService->delete($media);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('site_media', ['id' => $mediaId]);
        Storage::disk('local')->assertMissing($filePath);
    }

    /**
     * @test
     */
    public function api_upload_endpoint_works(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding->id;
        $couple->save();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'api-upload-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        Sanctum::actingAs($couple);

        $file = UploadedFile::fake()->image('api-photo.jpg', 800, 600);

        // Act
        $response = $this->postJson("/api/sites/{$site->id}/media", [
            'file' => $file,
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'original_name',
                'url',
                'size',
                'mime_type',
            ],
        ]);
    }

    /**
     * @test
     */
    public function api_upload_rejects_invalid_file(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding->id;
        $couple->save();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'api-reject-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        Sanctum::actingAs($couple);

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        // Act
        $response = $this->postJson("/api/sites/{$site->id}/media", [
            'file' => $file,
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(422);
    }

    /**
     * @test
     */
    public function api_usage_endpoint_returns_storage_info(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding->id;
        $couple->save();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'usage-api-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        Sanctum::actingAs($couple);

        // Act
        $response = $this->getJson("/api/sites/{$site->id}/media/usage", [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'used',
                'limit',
                'percentage',
            ],
        ]);
    }

    /**
     * @test
     */
    public function guest_cannot_upload_media(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $guest = User::factory()->create();
        $wedding->users()->attach($guest->id, ['role' => 'guest']);
        $guest->current_wedding_id = $wedding->id;
        $guest->save();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'guest-upload-test',
            'draft_content' => SiteContentSchema::getDefaultContent(),
        ]);

        Sanctum::actingAs($guest);

        $file = UploadedFile::fake()->image('guest-photo.jpg', 400, 300);

        // Act
        $response = $this->postJson("/api/sites/{$site->id}/media", [
            'file' => $file,
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(403);
    }
}
