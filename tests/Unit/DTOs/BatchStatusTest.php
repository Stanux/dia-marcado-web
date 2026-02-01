<?php

namespace Tests\Unit\DTOs;

use App\DTOs\BatchStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BatchStatus DTO.
 * 
 * Tests batch completion detection and progress calculation.
 * 
 * Validates: Requirements 1.5
 */
class BatchStatusTest extends TestCase
{
    #[Test]
    public function it_creates_batch_status_with_all_properties(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 10,
            completed: 5,
            failed: 2,
            pending: 3,
            errors: ['Error 1', 'Error 2'],
        );

        $this->assertEquals('batch-123', $status->batchId);
        $this->assertEquals(10, $status->total);
        $this->assertEquals(5, $status->completed);
        $this->assertEquals(2, $status->failed);
        $this->assertEquals(3, $status->pending);
        $this->assertEquals(['Error 1', 'Error 2'], $status->errors);
    }

    #[Test]
    public function is_complete_returns_true_when_pending_is_zero(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 10,
            completed: 8,
            failed: 2,
            pending: 0,
            errors: [],
        );

        $this->assertTrue($status->isComplete());
    }

    #[Test]
    public function is_complete_returns_false_when_pending_is_greater_than_zero(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 10,
            completed: 5,
            failed: 2,
            pending: 3,
            errors: [],
        );

        $this->assertFalse($status->isComplete());
    }

    #[Test]
    public function is_complete_returns_true_when_all_files_completed(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 10,
            completed: 10,
            failed: 0,
            pending: 0,
            errors: [],
        );

        $this->assertTrue($status->isComplete());
    }

    #[Test]
    public function is_complete_returns_true_when_all_files_failed(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 10,
            completed: 0,
            failed: 10,
            pending: 0,
            errors: ['Error 1', 'Error 2'],
        );

        $this->assertTrue($status->isComplete());
    }

    #[Test]
    public function is_complete_returns_true_for_empty_batch(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 0,
            completed: 0,
            failed: 0,
            pending: 0,
            errors: [],
        );

        $this->assertTrue($status->isComplete());
    }

    #[Test]
    public function get_progress_percentage_calculates_correctly(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 10,
            completed: 5,
            failed: 2,
            pending: 3,
            errors: [],
        );

        // (5 + 2) / 10 * 100 = 70%
        $this->assertEquals(70.0, $status->getProgressPercentage());
    }

    #[Test]
    public function get_progress_percentage_returns_100_when_all_completed(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 10,
            completed: 10,
            failed: 0,
            pending: 0,
            errors: [],
        );

        $this->assertEquals(100.0, $status->getProgressPercentage());
    }

    #[Test]
    public function get_progress_percentage_returns_100_when_all_failed(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 10,
            completed: 0,
            failed: 10,
            pending: 0,
            errors: [],
        );

        $this->assertEquals(100.0, $status->getProgressPercentage());
    }

    #[Test]
    public function get_progress_percentage_returns_0_when_nothing_processed(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 10,
            completed: 0,
            failed: 0,
            pending: 10,
            errors: [],
        );

        $this->assertEquals(0.0, $status->getProgressPercentage());
    }

    #[Test]
    public function get_progress_percentage_returns_100_for_empty_batch(): void
    {
        // Edge case: total = 0 should return 100% to avoid division by zero
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 0,
            completed: 0,
            failed: 0,
            pending: 0,
            errors: [],
        );

        $this->assertEquals(100.0, $status->getProgressPercentage());
    }

    #[Test]
    public function get_progress_percentage_handles_mixed_results(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 100,
            completed: 75,
            failed: 15,
            pending: 10,
            errors: [],
        );

        // (75 + 15) / 100 * 100 = 90%
        $this->assertEquals(90.0, $status->getProgressPercentage());
    }

    #[Test]
    public function get_progress_percentage_handles_decimal_results(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 3,
            completed: 1,
            failed: 0,
            pending: 2,
            errors: [],
        );

        // (1 + 0) / 3 * 100 = 33.333...%
        $this->assertEqualsWithDelta(33.33, $status->getProgressPercentage(), 0.01);
    }

    #[Test]
    public function batch_status_is_readonly(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 10,
            completed: 5,
            failed: 2,
            pending: 3,
            errors: [],
        );

        // Verify the class is readonly by checking reflection
        $reflection = new \ReflectionClass($status);
        $this->assertTrue($reflection->isReadOnly());
    }

    #[Test]
    public function errors_array_can_be_empty(): void
    {
        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 10,
            completed: 10,
            failed: 0,
            pending: 0,
            errors: [],
        );

        $this->assertEmpty($status->errors);
        $this->assertIsArray($status->errors);
    }

    #[Test]
    public function errors_array_contains_error_messages(): void
    {
        $errors = [
            'File too large: image1.jpg',
            'Invalid file type: document.pdf',
            'Upload failed: image2.jpg',
        ];

        $status = new BatchStatus(
            batchId: 'batch-123',
            total: 10,
            completed: 7,
            failed: 3,
            pending: 0,
            errors: $errors,
        );

        $this->assertCount(3, $status->errors);
        $this->assertEquals($errors, $status->errors);
    }
}
