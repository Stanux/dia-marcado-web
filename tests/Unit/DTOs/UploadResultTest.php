<?php

namespace Tests\Unit\DTOs;

use App\DTOs\UploadResult;
use App\Models\SiteMedia;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UploadResult DTO.
 * 
 * Tests upload result creation and property access for both
 * successful and failed upload scenarios.
 * 
 * Validates: Requirements 1.4, 1.5
 */
class UploadResultTest extends TestCase
{
    #[Test]
    public function it_creates_successful_upload_result(): void
    {
        $media = $this->createMock(SiteMedia::class);
        
        $result = new UploadResult(
            mediaId: 'media-123',
            success: true,
            media: $media,
        );

        $this->assertEquals('media-123', $result->mediaId);
        $this->assertTrue($result->success);
        $this->assertSame($media, $result->media);
        $this->assertNull($result->error);
    }

    #[Test]
    public function it_creates_failed_upload_result(): void
    {
        $result = new UploadResult(
            mediaId: 'media-456',
            success: false,
            error: 'File too large',
        );

        $this->assertEquals('media-456', $result->mediaId);
        $this->assertFalse($result->success);
        $this->assertNull($result->media);
        $this->assertEquals('File too large', $result->error);
    }

    #[Test]
    public function it_creates_result_with_all_properties(): void
    {
        $media = $this->createMock(SiteMedia::class);
        
        $result = new UploadResult(
            mediaId: 'media-789',
            success: true,
            media: $media,
            error: null,
        );

        $this->assertEquals('media-789', $result->mediaId);
        $this->assertTrue($result->success);
        $this->assertSame($media, $result->media);
        $this->assertNull($result->error);
    }

    #[Test]
    public function it_creates_result_with_only_required_properties(): void
    {
        $result = new UploadResult(
            mediaId: 'media-abc',
            success: false,
        );

        $this->assertEquals('media-abc', $result->mediaId);
        $this->assertFalse($result->success);
        $this->assertNull($result->media);
        $this->assertNull($result->error);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $result = new UploadResult(
            mediaId: 'media-123',
            success: true,
        );

        // Verify the class is readonly by checking reflection
        $reflection = new \ReflectionClass($result);
        $this->assertTrue($reflection->isReadOnly());
    }

    #[Test]
    public function it_handles_uuid_media_id(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        
        $result = new UploadResult(
            mediaId: $uuid,
            success: true,
        );

        $this->assertEquals($uuid, $result->mediaId);
    }

    #[Test]
    public function it_handles_detailed_error_message(): void
    {
        $errorMessage = 'UPLOAD_001: Arquivo muito grande. O tamanho máximo permitido é 10MB.';
        
        $result = new UploadResult(
            mediaId: 'media-error',
            success: false,
            error: $errorMessage,
        );

        $this->assertFalse($result->success);
        $this->assertEquals($errorMessage, $result->error);
    }

    #[Test]
    public function it_handles_invalid_file_type_error(): void
    {
        $result = new UploadResult(
            mediaId: 'media-invalid',
            success: false,
            error: 'UPLOAD_002: Tipo de arquivo não permitido. Extensões válidas: jpg, jpeg, png, gif, webp, mp4, webm',
        );

        $this->assertFalse($result->success);
        $this->assertNull($result->media);
        $this->assertStringContainsString('UPLOAD_002', $result->error);
    }

    #[Test]
    public function it_handles_quota_exceeded_error(): void
    {
        $result = new UploadResult(
            mediaId: 'media-quota',
            success: false,
            error: 'UPLOAD_005: Cota de arquivos excedida',
        );

        $this->assertFalse($result->success);
        $this->assertStringContainsString('UPLOAD_005', $result->error);
    }
}
