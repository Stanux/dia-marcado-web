<?php

namespace Tests\Unit\Models;

use App\Models\GiftItem;
use App\Models\GiftRegistryConfig;
use App\Models\IdempotencyKey;
use App\Models\Transaction;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftRegistryModelsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_gift_item_with_factory()
    {
        $wedding = Wedding::factory()->create();
        $giftItem = GiftItem::factory()->create(['wedding_id' => $wedding->id]);

        $this->assertDatabaseHas('gift_items', [
            'id' => $giftItem->id,
            'wedding_id' => $wedding->id,
        ]);

        $this->assertGreaterThanOrEqual(100, $giftItem->price);
        $this->assertEquals($giftItem->original_name, $giftItem->name);
        $this->assertEquals($giftItem->original_price, $giftItem->price);
    }

    /** @test */
    public function it_can_create_a_transaction_with_factory()
    {
        $wedding = Wedding::factory()->create();
        $giftItem = GiftItem::factory()->create(['wedding_id' => $wedding->id]);
        
        $transaction = Transaction::factory()->create([
            'wedding_id' => $wedding->id,
            'gift_item_id' => $giftItem->id,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'wedding_id' => $wedding->id,
            'gift_item_id' => $giftItem->id,
        ]);

        $this->assertContains($transaction->fee_modality, ['couple_pays', 'guest_pays']);
        $this->assertContains($transaction->payment_method, ['credit_card', 'pix']);
        $this->assertContains($transaction->status, ['pending', 'confirmed', 'failed', 'refunded']);
    }

    /** @test */
    public function it_can_create_a_gift_registry_config_with_factory()
    {
        $wedding = Wedding::factory()->create();
        $config = GiftRegistryConfig::factory()->create(['wedding_id' => $wedding->id]);

        $this->assertDatabaseHas('gift_registry_configs', [
            'id' => $config->id,
            'wedding_id' => $wedding->id,
        ]);

        $this->assertContains($config->fee_modality, ['couple_pays', 'guest_pays']);
        $this->assertContains($config->title_style, ['normal', 'bold', 'italic', 'bold_italic']);
    }

    /** @test */
    public function it_can_create_an_idempotency_key_with_factory()
    {
        $idempotencyKey = IdempotencyKey::factory()->create();

        $this->assertDatabaseHas('idempotency_keys', [
            'id' => $idempotencyKey->id,
            'key' => $idempotencyKey->key,
        ]);
    }

    /** @test */
    public function gift_item_can_check_if_available()
    {
        $wedding = Wedding::factory()->create();
        
        $availableItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'is_enabled' => true,
            'quantity_available' => 5,
        ]);
        
        $soldOutItem = GiftItem::factory()->soldOut()->create(['wedding_id' => $wedding->id]);
        
        $this->assertTrue($availableItem->isAvailable());
        $this->assertFalse($soldOutItem->isAvailable());
    }

    /** @test */
    public function gift_item_can_check_if_sold_out()
    {
        $wedding = Wedding::factory()->create();
        
        $availableItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'quantity_available' => 5,
        ]);
        
        $soldOutItem = GiftItem::factory()->soldOut()->create(['wedding_id' => $wedding->id]);
        
        $this->assertFalse($availableItem->isSoldOut());
        $this->assertTrue($soldOutItem->isSoldOut());
    }

    /** @test */
    public function gift_item_can_restore_to_original_values()
    {
        $wedding = Wedding::factory()->create();
        $giftItem = GiftItem::factory()->edited()->create(['wedding_id' => $wedding->id]);

        // Values should be different from original
        $this->assertNotEquals($giftItem->original_name, $giftItem->name);
        $this->assertNotEquals($giftItem->original_price, $giftItem->price);

        // Restore to original
        $giftItem->restoreOriginal();

        // Values should now match original
        $this->assertEquals($giftItem->original_name, $giftItem->name);
        $this->assertEquals($giftItem->original_price, $giftItem->price);
        $this->assertEquals($giftItem->original_quantity, $giftItem->quantity_available);
    }

    /** @test */
    public function transaction_can_be_marked_as_confirmed()
    {
        $wedding = Wedding::factory()->create();
        $giftItem = GiftItem::factory()->create(['wedding_id' => $wedding->id]);
        $transaction = Transaction::factory()->create([
            'wedding_id' => $wedding->id,
            'gift_item_id' => $giftItem->id,
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $transaction->status);
        $this->assertNull($transaction->confirmed_at);

        $transaction->markAsConfirmed();

        $this->assertEquals('confirmed', $transaction->status);
        $this->assertNotNull($transaction->confirmed_at);
    }

    /** @test */
    public function transaction_can_be_marked_as_failed()
    {
        $wedding = Wedding::factory()->create();
        $giftItem = GiftItem::factory()->create(['wedding_id' => $wedding->id]);
        $transaction = Transaction::factory()->create([
            'wedding_id' => $wedding->id,
            'gift_item_id' => $giftItem->id,
            'status' => 'pending',
        ]);

        $errorMessage = 'Cartão recusado';
        $transaction->markAsFailed($errorMessage);

        $this->assertEquals('failed', $transaction->status);
        $this->assertEquals($errorMessage, $transaction->error_message);
    }

    /** @test */
    public function idempotency_key_can_check_if_expired()
    {
        $expiredKey = IdempotencyKey::factory()->expired()->create();
        $validKey = IdempotencyKey::factory()->create(['expires_at' => now()->addHours(24)]);
        $neverExpiresKey = IdempotencyKey::factory()->neverExpires()->create();

        $this->assertTrue($expiredKey->isExpired());
        $this->assertFalse($validKey->isExpired());
        $this->assertFalse($neverExpiresKey->isExpired());
    }

    /** @test */
    public function gift_item_decrement_quantity_marks_as_sold_out_when_zero()
    {
        $wedding = Wedding::factory()->create();
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'quantity_available' => 1,
            'is_enabled' => true,
        ]);

        $this->assertTrue($giftItem->is_enabled);
        $this->assertEquals(1, $giftItem->quantity_available);

        $giftItem->decrementQuantity();

        $this->assertFalse($giftItem->is_enabled);
        $this->assertEquals(0, $giftItem->quantity_available);
        $this->assertEquals(1, $giftItem->quantity_sold);
    }
}
