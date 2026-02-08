<?php

namespace Tests\Feature;

use App\Models\GiftItem;
use App\Models\GiftRegistryConfig;
use App\Models\Transaction;
use App\Models\Wedding;
use App\Services\Payment\IdempotencyService;
use App\Services\Payment\PagSeguroClient;
use App\Services\Payment\PagSeguroException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * End-to-end integration tests for Gift Registry feature.
 * 
 * Tests complete flows from catalog initialization through purchase and webhook confirmation.
 * 
 * @Requirements: 1.1-1.8, 2.1-2.7, 4.1-4.7, 6.1-6.9, 7.1-7.9, 8.1-8.5, 9.1-9.4, 10.1-10.4, 
 *                11.1-11.5, 12.1-12.5, 13.1-13.5, 14.1-14.6, 15.1-15.5
 */
class GiftRegistryEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set webhook secret for testing
        Config::set('services.pagseguro.webhook_secret', 'test-webhook-secret');
    }

    /**
     * Test 17.1: Complete credit card purchase flow
     * 
     * Flow: Create event → Edit catalog → Purchase with card → Confirm via webhook
     * 
     * @test
     */
    public function test_complete_credit_card_purchase_flow()
    {
        // Step 1: Create wedding event (GiftRegistryConfig is auto-created by WeddingObserver)
        $wedding = Wedding::factory()->create([
            'title' => 'Casamento João & Maria',
            'wedding_date' => now()->addMonths(6),
        ]);

        // Step 2: Get the auto-created gift registry config and update it
        $config = GiftRegistryConfig::withoutGlobalScopes()->where('wedding_id', $wedding->id)->first();
        $config->update([
            'is_enabled' => true,
            'section_title' => 'Nossa Lista de Presentes',
            'fee_modality' => 'couple_pays',
        ]);

        // Step 3: Create/edit catalog items
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Jogo de Panelas',
            'description' => 'Jogo de panelas antiaderente 5 peças',
            'price' => 50000, // R$ 500.00
            'quantity_available' => 3,
            'quantity_sold' => 0,
            'is_enabled' => true,
        ]);

        // Step 4: Verify gift is available in public API
        $response = $this->getJson("/api/events/{$wedding->id}/gifts");
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Jogo de Panelas']);

        // Step 5: Mock PagSeguro client for credit card payment
        $mockPagSeguroClient = Mockery::mock(PagSeguroClient::class);
        $mockPagSeguroClient->shouldReceive('createCreditCardCharge')
            ->once()
            ->andReturn([
                'id' => 'PAGSEG-CC-' . Str::random(20),
                'status' => 'PENDING',
                'amount' => 50000,
            ]);
        
        $this->app->instance(PagSeguroClient::class, $mockPagSeguroClient);

        // Step 6: Purchase gift with credit card
        $idempotencyKey = Str::random(32);
        $purchaseData = [
            'payment_method' => 'credit_card',
            'idempotency_key' => $idempotencyKey,
            'payer' => [
                'name' => 'Convidado Teste',
                'email' => 'convidado@example.com',
                'document' => '12345678901',
                'phone' => '11987654321',
            ],
            'card_token' => 'CARD_TOKEN_' . Str::random(20),
            'installments' => 1,
        ];

        $purchaseResponse = $this->postJson(
            "/api/events/{$wedding->id}/gifts/{$giftItem->id}/purchase",
            $purchaseData
        );

        $purchaseResponse->assertStatus(201);
        $purchaseResponse->assertJsonStructure([
            'message',
            'data' => [
                'transaction_id',
                'status',
                'payment_method',
            ],
        ]);

        // Step 7: Verify transaction was created
        $transactionId = $purchaseResponse->json('data.transaction_id');
        $transaction = Transaction::withoutGlobalScopes()->where('internal_id', $transactionId)->first();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('pending', $transaction->status);
        $this->assertEquals('credit_card', $transaction->payment_method);
        $this->assertEquals(50000, $transaction->original_unit_price);
        $this->assertEquals($wedding->id, $transaction->wedding_id);
        $this->assertEquals($giftItem->id, $transaction->gift_item_id);

        // Step 8: Verify gift quantity NOT yet decremented (pending payment)
        $giftItem->refresh();
        $this->assertEquals(3, $giftItem->quantity_available);
        $this->assertEquals(0, $giftItem->quantity_sold);

        // Step 9: Simulate webhook confirmation from PagSeguro
        $webhookPayload = json_encode([
            'event_type' => 'CHARGE.PAID',
            'data' => [
                'id' => $transaction->pagseguro_transaction_id,
                'status' => 'PAID',
                'amount' => 50000,
                'paid_at' => now()->toIso8601String(),
            ],
        ]);

        $signature = hash_hmac('sha256', $webhookPayload, 'test-webhook-secret');

        $webhookResponse = $this->postJson(
            '/api/webhooks/pagseguro',
            json_decode($webhookPayload, true),
            ['X-PagSeguro-Signature' => $signature]
        );

        $webhookResponse->assertStatus(204);

        // Step 10: Verify transaction was confirmed
        $transaction->refresh();
        $this->assertEquals('confirmed', $transaction->status);
        $this->assertNotNull($transaction->confirmed_at);

        // Step 11: Verify gift quantity was decremented
        $giftItem->refresh();
        $this->assertEquals(2, $giftItem->quantity_available);
        $this->assertEquals(1, $giftItem->quantity_sold);

        // Step 12: Verify idempotency - duplicate purchase attempt should return same transaction
        $duplicatePurchaseResponse = $this->postJson(
            "/api/events/{$wedding->id}/gifts/{$giftItem->id}/purchase",
            $purchaseData
        );

        $duplicatePurchaseResponse->assertStatus(201);
        $this->assertEquals(
            $transactionId,
            $duplicatePurchaseResponse->json('data.transaction_id')
        );

        // Verify quantity still correct (no double decrement)
        $giftItem->refresh();
        $this->assertEquals(2, $giftItem->quantity_available);
        $this->assertEquals(1, $giftItem->quantity_sold);
    }

    /**
     * Test 17.2: Complete PIX purchase flow
     * 
     * Flow: Create event → Purchase with PIX → Generate QR Code → Confirm via webhook
     * 
     * @test
     */
    public function test_complete_pix_purchase_flow()
    {
        // Step 1: Create wedding event (GiftRegistryConfig is auto-created by WeddingObserver)
        $wedding = Wedding::factory()->create();

        // Step 2: Get the auto-created gift registry config and update it
        $config = GiftRegistryConfig::withoutGlobalScopes()->where('wedding_id', $wedding->id)->first();
        $config->update([
            'is_enabled' => true,
            'fee_modality' => 'guest_pays',
        ]);

        // Step 3: Create gift item
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Liquidificador',
            'price' => 30000, // R$ 300.00
            'quantity_available' => 5,
            'is_enabled' => true,
        ]);

        // Step 4: Mock PagSeguro client for PIX payment
        $qrCodeData = 'PIX_QR_CODE_' . Str::random(100);
        $mockPagSeguroClient = Mockery::mock(PagSeguroClient::class);
        $mockPagSeguroClient->shouldReceive('createPixCharge')
            ->once()
            ->andReturn([
                'transaction_id' => 'PAGSEG-PIX-' . Str::random(20),
                'status' => 'WAITING',
                'qr_code' => $qrCodeData,
                'qr_code_text' => 'pix.example.com/qr/v2/' . Str::random(50),
                'expires_at' => now()->addMinutes(30)->toIso8601String(),
            ]);
        
        $this->app->instance(PagSeguroClient::class, $mockPagSeguroClient);

        // Step 5: Purchase gift with PIX
        $idempotencyKey = Str::random(32);
        $purchaseData = [
            'payment_method' => 'pix',
            'idempotency_key' => $idempotencyKey,
            'payer' => [
                'name' => 'Convidado PIX',
                'email' => 'pix@example.com',
                'document' => '98765432100',
            ],
        ];

        $purchaseResponse = $this->postJson(
            "/api/events/{$wedding->id}/gifts/{$giftItem->id}/purchase",
            $purchaseData
        );

        $purchaseResponse->assertStatus(201);
        $purchaseResponse->assertJsonStructure([
            'message',
            'data' => [
                'transaction_id',
                'status',
                'payment_method',
                'qr_code',
                'qr_code_text',
            ],
        ]);

        // Step 6: Verify QR code was generated
        $this->assertEquals($qrCodeData, $purchaseResponse->json('data.qr_code'));
        $this->assertNotEmpty($purchaseResponse->json('data.qr_code_text'));

        // Step 7: Verify transaction was created with pending status
        $transactionId = $purchaseResponse->json('data.transaction_id');
        $transaction = Transaction::withoutGlobalScopes()->where('internal_id', $transactionId)->first();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('pending', $transaction->status);
        $this->assertEquals('pix', $transaction->payment_method);

        // Step 8: Verify gift quantity NOT yet decremented
        $giftItem->refresh();
        $this->assertEquals(5, $giftItem->quantity_available);

        // Step 9: Simulate PIX payment confirmation webhook
        $webhookPayload = json_encode([
            'event_type' => 'CHARGE.PAID',
            'data' => [
                'id' => $transaction->pagseguro_transaction_id,
                'status' => 'PAID',
                'amount' => $transaction->gross_amount,
                'paid_at' => now()->toIso8601String(),
            ],
        ]);

        $signature = hash_hmac('sha256', $webhookPayload, 'test-webhook-secret');

        $webhookResponse = $this->postJson(
            '/api/webhooks/pagseguro',
            json_decode($webhookPayload, true),
            ['X-PagSeguro-Signature' => $signature]
        );

        $webhookResponse->assertStatus(204);

        // Step 10: Verify transaction was confirmed
        $transaction->refresh();
        $this->assertEquals('confirmed', $transaction->status);
        $this->assertNotNull($transaction->confirmed_at);

        // Step 11: Verify gift quantity was decremented
        $giftItem->refresh();
        $this->assertEquals(4, $giftItem->quantity_available);
        $this->assertEquals(1, $giftItem->quantity_sold);
    }

    /**
     * Test 17.3: Fee calculations in both modalities
     * 
     * Verify correct values for couple_pays and guest_pays
     * 
     * @test
     */
    public function test_fee_calculations_in_both_modalities()
    {
        // Test couple_pays modality
        $wedding1 = Wedding::factory()->create();
        $config1 = GiftRegistryConfig::withoutGlobalScopes()->where('wedding_id', $wedding1->id)->first();
        $config1->update(['fee_modality' => 'couple_pays']);

        $giftItem1 = GiftItem::factory()->create([
            'wedding_id' => $wedding1->id,
            'price' => 10000, // R$ 100.00
            'quantity_available' => 1,
            'is_enabled' => true,
        ]);

        // Mock PagSeguro for couple_pays
        $mockPagSeguroClient = Mockery::mock(PagSeguroClient::class);
        $mockPagSeguroClient->shouldReceive('createPixCharge')
            ->twice()
            ->andReturn([
                'transaction_id' => 'PAGSEG-PIX-1',
                'status' => 'WAITING',
                'qr_code' => 'QR1',
                'qr_code_text' => 'pix1',
            ], [
                'transaction_id' => 'PAGSEG-PIX-2',
                'status' => 'WAITING',
                'qr_code' => 'QR2',
                'qr_code_text' => 'pix2',
            ]);
        
        $this->app->instance(PagSeguroClient::class, $mockPagSeguroClient);

        // Purchase with couple_pays
        $purchaseResponse1 = $this->postJson(
            "/api/events/{$wedding1->id}/gifts/{$giftItem1->id}/purchase",
            [
                'payment_method' => 'pix',
                'idempotency_key' => Str::random(32),
                'payer' => [
                    'name' => 'Test',
                    'email' => 'test@example.com',
                    'document' => '12345678901',
                ],
            ]
        );

        $purchaseResponse1->assertStatus(201);

        // Verify couple_pays calculations
        $transaction1 = Transaction::withoutGlobalScopes()->where('internal_id', $purchaseResponse1->json('data.transaction_id'))->first();
        
        // In couple_pays: guest pays original price, couple receives less
        $this->assertEquals(10000, $transaction1->original_unit_price);
        $this->assertEquals(10000, $transaction1->gross_amount); // Guest pays original
        $this->assertLessThan(10000, $transaction1->net_amount_couple); // Couple receives less
        $this->assertGreaterThan(0, $transaction1->platform_amount); // Platform gets fee
        $this->assertEquals(
            $transaction1->gross_amount,
            $transaction1->net_amount_couple + $transaction1->platform_amount
        );

        // Test guest_pays modality
        $wedding2 = Wedding::factory()->create();
        $config2 = GiftRegistryConfig::withoutGlobalScopes()->where('wedding_id', $wedding2->id)->first();
        $config2->update(['fee_modality' => 'guest_pays']);

        $giftItem2 = GiftItem::factory()->create([
            'wedding_id' => $wedding2->id,
            'price' => 10000, // R$ 100.00
            'quantity_available' => 1,
            'is_enabled' => true,
        ]);

        // Purchase with guest_pays
        $purchaseResponse2 = $this->postJson(
            "/api/events/{$wedding2->id}/gifts/{$giftItem2->id}/purchase",
            [
                'payment_method' => 'pix',
                'idempotency_key' => Str::random(32),
                'payer' => [
                    'name' => 'Test',
                    'email' => 'test@example.com',
                    'document' => '12345678901',
                ],
            ]
        );

        $purchaseResponse2->assertStatus(201);

        // Verify guest_pays calculations
        $transaction2 = Transaction::withoutGlobalScopes()->where('internal_id', $purchaseResponse2->json('data.transaction_id'))->first();
        
        // In guest_pays: guest pays more, couple receives original price
        $this->assertEquals(10000, $transaction2->original_unit_price);
        $this->assertGreaterThan(10000, $transaction2->gross_amount); // Guest pays more
        $this->assertEquals(10000, $transaction2->net_amount_couple); // Couple receives original
        $this->assertGreaterThan(0, $transaction2->platform_amount); // Platform gets fee
        $this->assertEquals(
            $transaction2->gross_amount,
            $transaction2->net_amount_couple + $transaction2->platform_amount
        );
    }

    /**
     * Test 17.4: Multi-tenant isolation
     * 
     * Verify that events cannot access each other's data
     * 
     * @test
     */
    public function test_multi_tenant_isolation()
    {
        // Create two separate weddings (GiftRegistryConfig auto-created by WeddingObserver)
        $wedding1 = Wedding::factory()->create(['title' => 'Wedding 1']);
        $wedding2 = Wedding::factory()->create(['title' => 'Wedding 2']);

        // Create gift items for each wedding
        $gift1 = GiftItem::factory()->create([
            'wedding_id' => $wedding1->id,
            'name' => 'Gift for Wedding 1',
            'price' => 10000,
            'quantity_available' => 5,
            'is_enabled' => true,
        ]);

        $gift2 = GiftItem::factory()->create([
            'wedding_id' => $wedding2->id,
            'name' => 'Gift for Wedding 2',
            'price' => 20000,
            'quantity_available' => 3,
            'is_enabled' => true,
        ]);

        // Test 1: Wedding 1 should only see its own gifts
        $response1 = $this->getJson("/api/events/{$wedding1->id}/gifts");
        $response1->assertStatus(200);
        $response1->assertJsonFragment(['name' => 'Gift for Wedding 1']);
        $response1->assertJsonMissing(['name' => 'Gift for Wedding 2']);

        // Test 2: Wedding 2 should only see its own gifts
        $response2 = $this->getJson("/api/events/{$wedding2->id}/gifts");
        $response2->assertStatus(200);
        $response2->assertJsonFragment(['name' => 'Gift for Wedding 2']);
        $response2->assertJsonMissing(['name' => 'Gift for Wedding 1']);

        // Test 3: Cannot access gift from another wedding
        $response3 = $this->getJson("/api/events/{$wedding1->id}/gifts/{$gift2->id}");
        $response3->assertStatus(404);

        $response4 = $this->getJson("/api/events/{$wedding2->id}/gifts/{$gift1->id}");
        $response4->assertStatus(404);

        // Test 4: Cannot purchase gift from another wedding
        $mockPagSeguroClient = Mockery::mock(PagSeguroClient::class);
        $this->app->instance(PagSeguroClient::class, $mockPagSeguroClient);

        $purchaseData = [
            'payment_method' => 'pix',
            'idempotency_key' => Str::random(32),
            'payer' => [
                'name' => 'Test',
                'email' => 'test@example.com',
                'document' => '12345678901',
            ],
        ];

        $response5 = $this->postJson(
            "/api/events/{$wedding1->id}/gifts/{$gift2->id}/purchase",
            $purchaseData
        );
        $response5->assertStatus(404);

        // Test 5: Create transactions for both weddings
        $transaction1 = Transaction::factory()->create([
            'wedding_id' => $wedding1->id,
            'gift_item_id' => $gift1->id,
            'status' => 'confirmed',
        ]);

        $transaction2 = Transaction::factory()->create([
            'wedding_id' => $wedding2->id,
            'gift_item_id' => $gift2->id,
            'status' => 'confirmed',
        ]);

        // Verify transactions are isolated
        $wedding1Transactions = Transaction::withoutGlobalScopes()->where('wedding_id', $wedding1->id)->get();
        $this->assertCount(1, $wedding1Transactions);
        $this->assertEquals($transaction1->id, $wedding1Transactions->first()->id);

        $wedding2Transactions = Transaction::withoutGlobalScopes()->where('wedding_id', $wedding2->id)->get();
        $this->assertCount(1, $wedding2Transactions);
        $this->assertEquals($transaction2->id, $wedding2Transactions->first()->id);
    }

    /**
     * Test: Complete flow with payment failure
     * 
     * @test
     */
    public function test_payment_failure_flow()
    {
        $wedding = Wedding::factory()->create();
        // GiftRegistryConfig is auto-created by WeddingObserver

        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'price' => 15000,
            'quantity_available' => 5,
            'is_enabled' => true,
        ]);

        // Mock PagSeguro to return error
        $mockPagSeguroClient = Mockery::mock(PagSeguroClient::class);
        $mockPagSeguroClient->shouldReceive('createCreditCardCharge')
            ->once()
            ->andThrow(new PagSeguroException('Cartão recusado pelo banco emissor'));
        
        $this->app->instance(PagSeguroClient::class, $mockPagSeguroClient);

        // Attempt purchase
        $purchaseResponse = $this->postJson(
            "/api/events/{$wedding->id}/gifts/{$giftItem->id}/purchase",
            [
                'payment_method' => 'credit_card',
                'idempotency_key' => Str::random(32),
                'payer' => [
                    'name' => 'Test',
                    'email' => 'test@example.com',
                    'document' => '12345678901',
                    'phone' => '11987654321',
                ],
                'card_token' => 'INVALID_TOKEN',
                'installments' => 1,
            ]
        );

        // Should return error
        $purchaseResponse->assertStatus(400);
        $purchaseResponse->assertJsonStructure(['error', 'message']);

        // Verify transaction was created with failed status
        $transaction = Transaction::withoutGlobalScopes()->where('gift_item_id', $giftItem->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('failed', $transaction->status);
        $this->assertNotEmpty($transaction->error_message);

        // Verify gift quantity was NOT decremented
        $giftItem->refresh();
        $this->assertEquals(5, $giftItem->quantity_available);
        $this->assertEquals(0, $giftItem->quantity_sold);
    }

    /**
     * Test: Sold out prevention
     * 
     * @test
     */
    public function test_sold_out_prevention()
    {
        $wedding = Wedding::factory()->create();
        // GiftRegistryConfig is auto-created by WeddingObserver

        // Create gift with only 1 available
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'price' => 10000,
            'quantity_available' => 1,
            'is_enabled' => true,
        ]);

        // Mock PagSeguro
        $mockPagSeguroClient = Mockery::mock(PagSeguroClient::class);
        $mockPagSeguroClient->shouldReceive('createPixCharge')
            ->once()
            ->andReturn([
                'transaction_id' => 'PAGSEG-PIX-TEST',
                'status' => 'WAITING',
                'qr_code' => 'QR',
                'qr_code_text' => 'pix',
            ]);
        
        $this->app->instance(PagSeguroClient::class, $mockPagSeguroClient);

        // First purchase - should succeed
        $purchaseData = [
            'payment_method' => 'pix',
            'idempotency_key' => Str::random(32),
            'payer' => [
                'name' => 'Test',
                'email' => 'test@example.com',
                'document' => '12345678901',
            ],
        ];

        $response1 = $this->postJson(
            "/api/events/{$wedding->id}/gifts/{$giftItem->id}/purchase",
            $purchaseData
        );
        $response1->assertStatus(201);

        $transaction = Transaction::withoutGlobalScopes()->where('internal_id', $response1->json('data.transaction_id'))->first();

        // Confirm payment via webhook
        $webhookPayload = json_encode([
            'event_type' => 'CHARGE.PAID',
            'data' => [
                'id' => $transaction->pagseguro_transaction_id,
                'status' => 'PAID',
            ],
        ]);

        $signature = hash_hmac('sha256', $webhookPayload, 'test-webhook-secret');
        $this->postJson(
            '/api/webhooks/pagseguro',
            json_decode($webhookPayload, true),
            ['X-PagSeguro-Signature' => $signature]
        );

        // Verify item is now sold out
        $giftItem->refresh();
        $this->assertEquals(0, $giftItem->quantity_available);
        $this->assertEquals(1, $giftItem->quantity_sold);

        // Second purchase attempt - should fail
        $purchaseData2 = [
            'payment_method' => 'pix',
            'idempotency_key' => Str::random(32),
            'payer' => [
                'name' => 'Test 2',
                'email' => 'test2@example.com',
                'document' => '98765432100',
            ],
        ];

        $response2 = $this->postJson(
            "/api/events/{$wedding->id}/gifts/{$giftItem->id}/purchase",
            $purchaseData2
        );

        $response2->assertStatus(422);
        $response2->assertJson([
            'error' => 'Unavailable',
            'message' => 'Este presente não está mais disponível.',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
