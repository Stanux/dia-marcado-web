<?php

namespace Tests\Unit\DTOs;

use App\DTOs\QuotaCheckResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for QuotaCheckResult DTO.
 * 
 * Tests quota check result creation and property access.
 * 
 * Validates: Requirements 4.3, 4.4, 5.4
 */
class QuotaCheckResultTest extends TestCase
{
    #[Test]
    public function it_creates_result_allowing_upload(): void
    {
        $result = new QuotaCheckResult(
            canUpload: true,
        );

        $this->assertTrue($result->canUpload);
        $this->assertNull($result->reason);
        $this->assertNull($result->upgradeMessage);
    }

    #[Test]
    public function it_creates_result_blocking_upload_with_reason(): void
    {
        $result = new QuotaCheckResult(
            canUpload: false,
            reason: 'Cota de arquivos excedida',
        );

        $this->assertFalse($result->canUpload);
        $this->assertEquals('Cota de arquivos excedida', $result->reason);
        $this->assertNull($result->upgradeMessage);
    }

    #[Test]
    public function it_creates_result_blocking_upload_with_storage_reason(): void
    {
        $result = new QuotaCheckResult(
            canUpload: false,
            reason: 'Cota de armazenamento excedida',
        );

        $this->assertFalse($result->canUpload);
        $this->assertEquals('Cota de armazenamento excedida', $result->reason);
        $this->assertNull($result->upgradeMessage);
    }

    #[Test]
    public function it_creates_result_with_upgrade_message_for_basic_plan(): void
    {
        $result = new QuotaCheckResult(
            canUpload: false,
            reason: 'Cota de arquivos excedida',
            upgradeMessage: 'Faça upgrade para o plano premium para aumentar seu limite de arquivos.',
        );

        $this->assertFalse($result->canUpload);
        $this->assertEquals('Cota de arquivos excedida', $result->reason);
        $this->assertEquals(
            'Faça upgrade para o plano premium para aumentar seu limite de arquivos.',
            $result->upgradeMessage
        );
    }

    #[Test]
    public function it_creates_result_with_all_properties(): void
    {
        $result = new QuotaCheckResult(
            canUpload: false,
            reason: 'Limite de armazenamento atingido',
            upgradeMessage: 'Atualize seu plano para obter mais espaço de armazenamento.',
        );

        $this->assertFalse($result->canUpload);
        $this->assertEquals('Limite de armazenamento atingido', $result->reason);
        $this->assertEquals(
            'Atualize seu plano para obter mais espaço de armazenamento.',
            $result->upgradeMessage
        );
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $result = new QuotaCheckResult(
            canUpload: true,
        );

        // Verify the class is readonly by checking that properties exist and are accessible
        $this->assertTrue($result->canUpload);
        
        // The readonly class prevents modification at compile time
        // This test verifies the object was created correctly
        $reflection = new \ReflectionClass($result);
        $this->assertTrue($reflection->isReadOnly());
    }

    #[Test]
    public function it_allows_upload_without_optional_parameters(): void
    {
        $result = new QuotaCheckResult(canUpload: true);

        $this->assertTrue($result->canUpload);
        $this->assertNull($result->reason);
        $this->assertNull($result->upgradeMessage);
    }

    #[Test]
    public function it_blocks_upload_with_only_reason(): void
    {
        $result = new QuotaCheckResult(
            canUpload: false,
            reason: 'Você atingiu o limite máximo de 100 arquivos do seu plano.',
        );

        $this->assertFalse($result->canUpload);
        $this->assertNotNull($result->reason);
        $this->assertNull($result->upgradeMessage);
    }
}
