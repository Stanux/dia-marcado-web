<?php

namespace Tests\Feature;

use App\Models\GiftItem;
use App\Models\Transaction;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WebhookProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set webhook secret for testing
        Config::set('services.pagseguro.webhook_secret', 'test-webhook-secret');
    }

    /** @test */
    public function it_processes_valid_payment_confirmation_webhook()
    {
        // Create test data
        $wedding = Wedding::factory()->create();
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'quantity_available' => 5,
            'quantity_sold' => 0,
        ]);

        $transaction = Transaction::factory()->create([
            'wedding_id' => $wedding->id,
            'gift_item_id' => $giftItem->id,
            'pagseguro_transaction_id' => 'PAGSEG-TEST-12345',
            'status' => 'pending',
            'confirmed_at' => null,
        ]);

        // Prepare webhook payload
        $payload = json_encode([
            'event_type' => 'CHARGE.PAID',
            'data' => [
                'id' => 'PAGSEG-TEST-12345',
                'status' => 'PAID',
                'amount' => 10000,
            ],
        ]);

        // Generate valid signature
        $signature = hash_hmac('sha256', $payload, 'test-webhook-secret');

        // Send webhook request
        $response = $this->postJson('/api/webhooks/pagseguro', json_decode($payload, true), [
            'X-PagSeguro-Signature' => $signature,
        ]);

        // Assert response
        $response->assertStatus(204);

        // Assert transaction was confirmed
        $transaction->refresh();
        $this->assertEquals('confirmed', $transaction->status);
        $this->assertNotNull($transaction->confirmed_at);

        // Assert gift quantity was decremented
        $giftItem->refresh();
        $this->assertEquals(4, $giftItem->quantity_available);
        $this->assertEquals(1, $giftItem->quantity_sold);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_signature()
    {
        $payload = json_encode([
            'event_type' => 'CHARGE.PAID',
            'data' => [
                'id' => 'PAGSEG-12345',
                'status' => 'PAID',
            ],
        ]);

        // Send webhook with invalid signature
        $response = $this->postJson('/api/webhooks/pagseguro', json_decode($payload, true), [
            'X-PagSeguro-Signature' => 'invalid-signature',
        ]);

        // Assert rejection
        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Assinatura de webhook inválida',
        ]);
    }

    /** @test */
    public function it_handles_duplicate_webhook_deliveries_idempotently()
    {
        // Create test data
        $wedding = Wedding::factory()->create();
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'quantity_available' => 5,
            'quantity_sold' => 0,
        ]);

        $transaction = Transaction::factory()->create([
            'wedding_id' => $wedding->id,
            'gift_item_id' => $giftItem->id,
            'pagseguro_transaction_id' => 'PAGSEG-TEST-12345',
            'status' => 'pending',
        ]);

        // Prepare webhook payload
        $payload = json_encode([
            'event_type' => 'CHARGE.PAID',
            'data' => [
                'id' => 'PAGSEG-TEST-12345',
                'status' => 'PAID',
            ],
        ]);

        $signature = hash_hmac('sha256', $payload, 'test-webhook-secret');

        // Send webhook first time
        $response1 = $this->postJson('/api/webhooks/pagseguro', json_decode($payload, true), [
            'X-PagSeguro-Signature' => $signature,
        ]);
        $response1->assertStatus(204);

        // Verify first processing
        $transaction->refresh();
        $this->assertEquals('confirmed', $transaction->status);
        $giftItem->refresh();
        $this->assertEquals(4, $giftItem->quantity_available);

        // Send same webhook again (duplicate delivery)
        $response2 = $this->postJson('/api/webhooks/pagseguro', json_decode($payload, true), [
            'X-PagSeguro-Signature' => $signature,
        ]);
        $response2->assertStatus(204);

        // Verify idempotency - no double processing
        $transaction->refresh();
        $this->assertEquals('confirmed', $transaction->status);
        $giftItem->refresh();
        $this->assertEquals(4, $giftItem->quantity_available); // Still 4, not 3
        $this->assertEquals(1, $giftItem->quantity_sold); // Still 1, not 2
    }

    /** @test */
    public function it_handles_payment_failure_webhook()
    {
        // Create test data
        $wedding = Wedding::factory()->create();
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'quantity_available' => 5,
        ]);

        $transaction = Transaction::factory()->create([
            'wedding_id' => $wedding->id,
            'gift_item_id' => $giftItem->id,
            'pagseguro_transaction_id' => 'PAGSEG-TEST-12345',
            'status' => 'pending',
        ]);

        // Prepare failure webhook payload
        $payload = json_encode([
            'event_type' => 'CHARGE.DECLINED',
            'data' => [
                'id' => 'PAGSEG-TEST-12345',
                'status' => 'DECLINED',
                'error_message' => 'Cartão recusado',
            ],
        ]);

        $signature = hash_hmac('sha256', $payload, 'test-webhook-secret');

        // Send webhook
        $response = $this->postJson('/api/webhooks/pagseguro', json_decode($payload, true), [
            'X-PagSeguro-Signature' => $signature,
        ]);

        $response->assertStatus(204);

        // Assert transaction was marked as failed
        $transaction->refresh();
        $this->assertEquals('failed', $transaction->status);
        $this->assertEquals('Cartão recusado', $transaction->error_message);

        // Assert gift quantity was NOT decremented
        $giftItem->refresh();
        $this->assertEquals(5, $giftItem->quantity_available);
    }

    /** @test */
    public function it_handles_webhook_for_non_existent_transaction_gracefully()
    {
        $payload = json_encode([
            'event_type' => 'CHARGE.PAID',
            'data' => [
                'id' => 'NON-EXISTENT-TRANSACTION',
                'status' => 'PAID',
            ],
        ]);

        $signature = hash_hmac('sha256', $payload, 'test-webhook-secret');

        // Send webhook
        $response = $this->postJson('/api/webhooks/pagseguro', json_decode($payload, true), [
            'X-PagSeguro-Signature' => $signature,
        ]);

        // Should still return 204 (webhook was processed, just no matching transaction)
        $response->assertStatus(204);
    }

    /** @test */
    public function it_rejects_webhook_with_missing_signature()
    {
        $payload = [
            'event_type' => 'CHARGE.PAID',
            'data' => [
                'id' => 'PAGSEG-12345',
                'status' => 'PAID',
            ],
        ];

        // Send webhook without signature header
        $response = $this->postJson('/api/webhooks/pagseguro', $payload);

        $response->assertStatus(400);
    }
}
