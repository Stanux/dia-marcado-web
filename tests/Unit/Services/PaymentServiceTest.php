<?php

namespace Tests\Unit\Services;

use App\Models\GiftItem;
use App\Models\GiftRegistryConfig;
use App\Models\Transaction;
use App\Models\Wedding;
use App\Services\FeeCalculator;
use App\Services\Payment\IdempotencyService;
use App\Services\Payment\PagSeguroClient;
use App\Services\Payment\PagSeguroException;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;
    private PagSeguroClient $mockPagSeguroClient;
    private FeeCalculator $feeCalculator;
    private IdempotencyService $idempotencyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPagSeguroClient = Mockery::mock(PagSeguroClient::class);
        $this->feeCalculator = new FeeCalculator();
        $this->idempotencyService = new IdempotencyService();

        $this->paymentService = new PaymentService(
            $this->mockPagSeguroClient,
            $this->feeCalculator,
            $this->idempotencyService
        );
    }

    public function test_create_charge_creates_transaction_with_all_required_fields(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        
        $config = GiftRegistryConfig::factory()->create([
            'wedding_id' => $wedding->id,
            'fee_modality' => 'couple_pays',
        ]);
        
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'price' => 10000, // R$ 100,00
            'quantity_available' => 5,
            'is_enabled' => true,
        ]);

        $idempotencyKey = 'test-key-' . uniqid();

        // Act
        $transaction = $this->paymentService->createCharge(
            $giftItem,
            'credit_card',
            ['test' => 'data'],
            $idempotencyKey
        );

        // Assert
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertNotNull($transaction->internal_id);
        $this->assertEquals($wedding->id, $transaction->wedding_id);
        $this->assertEquals($giftItem->id, $transaction->gift_item_id);
        $this->assertEquals(10000, $transaction->original_unit_price);
        $this->assertNotNull($transaction->fee_percentage);
        $this->assertEquals('couple_pays', $transaction->fee_modality);
        $this->assertNotNull($transaction->fee_amount);
        $this->assertNotNull($transaction->gross_amount);
        $this->assertNotNull($transaction->net_amount_couple);
        $this->assertNotNull($transaction->platform_amount);
        $this->assertEquals('credit_card', $transaction->payment_method);
        $this->assertEquals('pending', $transaction->status);
        $this->assertNotNull($transaction->created_at);
    }

    public function test_create_charge_with_duplicate_idempotency_key_returns_existing_transaction(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        
        GiftRegistryConfig::factory()->create([
            'wedding_id' => $wedding->id,
            'fee_modality' => 'couple_pays',
        ]);
        
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'price' => 10000,
            'quantity_available' => 5,
            'is_enabled' => true,
        ]);

        $idempotencyKey = 'test-key-' . uniqid();

        // Act - Create first transaction
        $firstTransaction = $this->paymentService->createCharge(
            $giftItem,
            'credit_card',
            ['test' => 'data'],
            $idempotencyKey
        );

        // Act - Try to create second transaction with same key
        $secondTransaction = $this->paymentService->createCharge(
            $giftItem,
            'credit_card',
            ['test' => 'data'],
            $idempotencyKey
        );

        // Assert
        $this->assertEquals($firstTransaction->id, $secondTransaction->id);
        $this->assertEquals(1, Transaction::withoutGlobalScopes()->count()); // Only one transaction created
    }

    public function test_process_credit_card_payment_marks_transaction_as_failed_on_exception(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        
        GiftRegistryConfig::factory()->create([
            'wedding_id' => $wedding->id,
            'fee_modality' => 'couple_pays',
        ]);
        
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'price' => 10000,
            'quantity_available' => 5,
            'is_enabled' => true,
        ]);

        $idempotencyKey = 'test-key-' . uniqid();

            // Mock PagSeguro to throw exception
        $this->mockPagSeguroClient
            ->shouldReceive('createCreditCardCharge')
            ->once()
            ->andThrow(new PagSeguroException('Cartão recusado'));

        // Act & Assert
        try {
            $this->paymentService->processCreditCardPayment(
                $giftItem,
                'fake-token',
                [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'document' => '12345678900',
                ],
                $idempotencyKey
            );
            $this->fail('Expected PagSeguroException was not thrown');
        } catch (PagSeguroException $e) {
            // Expected exception
            $transaction = Transaction::withoutGlobalScopes()->where('internal_id', 'like', 'TXN-%')->first();
            $this->assertNotNull($transaction);
            $this->assertEquals('failed', $transaction->status);
            $this->assertNotNull($transaction->error_message);
            $this->assertStringContainsString('Cartão recusado', $transaction->error_message);
        }
    }

    public function test_calculate_payment_amounts_uses_fee_calculator(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'price' => 10000, // R$ 100,00
        ]);

        $config = GiftRegistryConfig::factory()->create([
            'wedding_id' => $wedding->id,
            'fee_modality' => 'couple_pays',
        ]);

        // Act
        $amounts = $this->paymentService->calculatePaymentAmounts($giftItem, $config);

        // Assert
        $this->assertEquals(10000, $amounts->displayPrice);
        $this->assertEquals(10000, $amounts->grossAmount);
        $this->assertGreaterThan(0, $amounts->feeAmount);
        $this->assertLessThan(10000, $amounts->netAmountCouple);
    }

    public function test_process_credit_card_payment_throws_exception_for_unavailable_gift(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'price' => 10000,
            'quantity_available' => 0, // Sold out
            'is_enabled' => true,
        ]);

        GiftRegistryConfig::factory()->create([
            'wedding_id' => $wedding->id,
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Presente não está disponível para compra');

        $this->paymentService->processCreditCardPayment(
            $giftItem,
            'fake-token',
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'document' => '12345678900',
            ],
            'test-key-' . uniqid()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
