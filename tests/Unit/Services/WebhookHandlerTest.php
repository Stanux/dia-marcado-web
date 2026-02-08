<?php

namespace Tests\Unit\Services;

use App\Models\GiftItem;
use App\Models\Transaction;
use App\Models\Wedding;
use App\Services\GiftService;
use App\Services\Payment\PagSeguroSignatureValidator;
use App\Services\Payment\WebhookHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WebhookHandlerTest extends TestCase
{
    use RefreshDatabase;

    private WebhookHandler $webhookHandler;
    private PagSeguroSignatureValidator $signatureValidator;
    private GiftService $giftService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signatureValidator = $this->createMock(PagSeguroSignatureValidator::class);
        $this->giftService = app(GiftService::class);
        $this->webhookHandler = new WebhookHandler(
            $this->giftService,
            $this->signatureValidator
        );
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_signature()
    {
        $this->signatureValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(false);

        $request = Request::create('/webhook', 'POST', [], [], [], [], '{"event_type":"CHARGE.PAID"}');
        $request->headers->set('X-PagSeguro-Signature', 'invalid-signature');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Assinatura de webhook inválida');

        $this->webhookHandler->handle($request);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_json()
    {
        $this->signatureValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(true);

        $request = Request::create('/webhook', 'POST', [], [], [], [], 'invalid-json');
        $request->headers->set('X-PagSeguro-Signature', 'valid-signature');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payload de webhook inválido');

        $this->webhookHandler->handle($request);
    }

    /** @test */
    public function it_processes_payment_confirmation_webhook()
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
            'pagseguro_transaction_id' => 'PAGSEG-12345',
            'status' => 'pending',
            'confirmed_at' => null,
        ]);

        // Mock signature validation
        $this->signatureValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(true);

        // Create webhook request
        $webhookPayload = json_encode([
            'event_type' => 'CHARGE.PAID',
            'data' => [
                'id' => 'PAGSEG-12345',
                'status' => 'PAID',
                'amount' => 10000,
            ],
        ]);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $webhookPayload);
        $request->headers->set('X-PagSeguro-Signature', 'valid-signature');

        // Process webhook
        $this->webhookHandler->handle($request);

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
            'pagseguro_transaction_id' => 'PAGSEG-12345',
            'status' => 'pending',
        ]);

        // Mock signature validation
        $this->signatureValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(true);

        // Create webhook request
        $webhookPayload = json_encode([
            'event_type' => 'CHARGE.DECLINED',
            'data' => [
                'id' => 'PAGSEG-12345',
                'status' => 'DECLINED',
                'error_message' => 'Cartão recusado',
            ],
        ]);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $webhookPayload);
        $request->headers->set('X-PagSeguro-Signature', 'valid-signature');

        // Process webhook
        $this->webhookHandler->handle($request);

        // Assert transaction was marked as failed
        $transaction->refresh();
        $this->assertEquals('failed', $transaction->status);
        $this->assertEquals('Cartão recusado', $transaction->error_message);

        // Assert gift quantity was NOT decremented
        $giftItem->refresh();
        $this->assertEquals(5, $giftItem->quantity_available);
    }

    /** @test */
    public function it_ensures_idempotency_for_duplicate_webhooks()
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
            'pagseguro_transaction_id' => 'PAGSEG-12345',
            'status' => 'pending',
            'confirmed_at' => null,
        ]);

        // Mock signature validation
        $this->signatureValidator
            ->method('validate')
            ->willReturn(true);

        // Create webhook request
        $webhookPayload = json_encode([
            'event_type' => 'CHARGE.PAID',
            'data' => [
                'id' => 'PAGSEG-12345',
                'status' => 'PAID',
            ],
        ]);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $webhookPayload);
        $request->headers->set('X-PagSeguro-Signature', 'valid-signature');

        // Process webhook first time
        $this->webhookHandler->handle($request);

        // Verify first processing
        $transaction->refresh();
        $this->assertEquals('confirmed', $transaction->status);
        $giftItem->refresh();
        $this->assertEquals(4, $giftItem->quantity_available);
        $this->assertEquals(1, $giftItem->quantity_sold);

        // Process same webhook again (duplicate delivery)
        $request2 = Request::create('/webhook', 'POST', [], [], [], [], $webhookPayload);
        $request2->headers->set('X-PagSeguro-Signature', 'valid-signature');
        
        $this->webhookHandler->handle($request2);

        // Verify idempotency - no double processing
        $transaction->refresh();
        $this->assertEquals('confirmed', $transaction->status);
        $giftItem->refresh();
        $this->assertEquals(4, $giftItem->quantity_available); // Still 4, not 3
        $this->assertEquals(1, $giftItem->quantity_sold); // Still 1, not 2
    }

    /** @test */
    public function it_logs_warning_when_transaction_not_found()
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('warning')->once();

        // Mock signature validation
        $this->signatureValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(true);

        // Create webhook with non-existent transaction
        $webhookPayload = json_encode([
            'event_type' => 'CHARGE.PAID',
            'data' => [
                'id' => 'NON-EXISTENT-ID',
                'status' => 'PAID',
            ],
        ]);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $webhookPayload);
        $request->headers->set('X-PagSeguro-Signature', 'valid-signature');

        // Process webhook - should log warning but not throw
        $this->webhookHandler->handle($request);

        // No exception should be thrown for missing transaction
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_authorized_status_as_confirmation()
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
            'pagseguro_transaction_id' => 'PAGSEG-12345',
            'status' => 'pending',
        ]);

        // Mock signature validation
        $this->signatureValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(true);

        // Create webhook with AUTHORIZED status
        $webhookPayload = json_encode([
            'event_type' => 'CHARGE.AUTHORIZED',
            'data' => [
                'id' => 'PAGSEG-12345',
                'status' => 'AUTHORIZED',
            ],
        ]);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $webhookPayload);
        $request->headers->set('X-PagSeguro-Signature', 'valid-signature');

        // Process webhook
        $this->webhookHandler->handle($request);

        // Assert transaction was confirmed
        $transaction->refresh();
        $this->assertEquals('confirmed', $transaction->status);
        $this->assertNotNull($transaction->confirmed_at);
    }
}
