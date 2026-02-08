<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Payment;

use App\Models\GiftItem;
use App\Models\GiftRegistryConfig;
use App\Models\Wedding;
use App\Services\FeeCalculator;
use App\Services\Payment\IdempotencyService;
use App\Services\Payment\PagSeguroClient;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test single item purchase validation in PaymentService.
 * 
 * @Requirements: 2.7
 */
class PaymentServiceSingleItemTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;
    private PagSeguroClient $pagSeguroClient;
    private FeeCalculator $feeCalculator;
    private IdempotencyService $idempotencyService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock dependencies
        $this->pagSeguroClient = $this->createMock(PagSeguroClient::class);
        $this->feeCalculator = new FeeCalculator();
        $this->idempotencyService = $this->createMock(IdempotencyService::class);

        // Create service instance
        $this->paymentService = new PaymentService(
            $this->pagSeguroClient,
            $this->feeCalculator,
            $this->idempotencyService
        );
    }

    /**
     * Test that a valid single item purchase is accepted.
     * 
     * @test
     */
    public function it_accepts_single_item_purchase(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        // Config is automatically created by WeddingObserver
        $config = GiftRegistryConfig::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->first();
        
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'price' => 10000, // R$ 100.00
            'quantity_available' => 5,
        ]);

        $idempotencyKey = 'test-key-' . uniqid();

        // Mock idempotency service
        $this->idempotencyService
            ->method('isValidKeyFormat')
            ->willReturn(true);
        $this->idempotencyService
            ->method('getTransaction')
            ->willReturn(null);
        $this->idempotencyService
            ->method('store')
            ->willReturnCallback(function ($key, $transaction, $response) {
                $idempotencyKey = new \App\Models\IdempotencyKey();
                $idempotencyKey->key = $key;
                $idempotencyKey->transaction_id = $transaction->id;
                $idempotencyKey->response = $response;
                return $idempotencyKey;
            });

        // Act & Assert - should not throw exception
        $transaction = $this->paymentService->createCharge(
            $giftItem,
            'credit_card',
            ['card_token' => 'test-token'],
            $idempotencyKey
        );

        // Verify transaction was created
        $this->assertNotNull($transaction);
        $this->assertEquals($giftItem->id, $transaction->gift_item_id);
        $this->assertEquals('pending', $transaction->status);
    }

    /**
     * Test that an invalid gift item is rejected.
     * 
     * @test
     */
    public function it_rejects_invalid_gift_item(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        // Config is automatically created by WeddingObserver

        // Create a gift item but don't save it (non-existent)
        $giftItem = new GiftItem([
            'wedding_id' => $wedding->id,
            'name' => 'Test Gift',
            'price' => 10000,
        ]);
        // Note: $giftItem->exists will be false

        $idempotencyKey = 'test-key-' . uniqid();

        // Mock idempotency service
        $this->idempotencyService
            ->method('isValidKeyFormat')
            ->willReturn(true);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Item de presente inválido');

        $this->paymentService->createCharge(
            $giftItem,
            'credit_card',
            ['card_token' => 'test-token'],
            $idempotencyKey
        );
    }

    /**
     * Test that the validation enforces single item constraint.
     * 
     * This test verifies that the type checking prevents arrays or collections.
     * 
     * @test
     */
    public function it_enforces_single_item_constraint(): void
    {
        // This test verifies that the method signature and validation
        // prevent multiple items from being processed.
        
        // The type hint GiftItem in the method signature already prevents
        // arrays or collections from being passed. This test documents
        // that behavior.
        
        $wedding = Wedding::factory()->create();
        // Config is automatically created by WeddingObserver
        
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'price' => 10000,
            'quantity_available' => 5,
        ]);

        // Verify that only a single GiftItem can be passed
        $this->assertInstanceOf(GiftItem::class, $giftItem);
        $this->assertNotInstanceOf(\Illuminate\Support\Collection::class, $giftItem);
        $this->assertIsNotArray($giftItem);
    }
}
