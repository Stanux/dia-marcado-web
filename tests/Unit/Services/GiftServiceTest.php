<?php

namespace Tests\Unit\Services;

use App\Models\GiftItem;
use App\Models\Wedding;
use App\Services\GiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftServiceTest extends TestCase
{
    use RefreshDatabase;

    private GiftService $giftService;
    private Wedding $wedding;

    protected function setUp(): void
    {
        parent::setUp();
        $this->giftService = new GiftService();
        $this->wedding = Wedding::factory()->create();
    }

    /** @test */
    public function it_initializes_default_catalog_with_predefined_items()
    {
        $items = $this->giftService->initializeDefaultCatalog($this->wedding->id);

        $this->assertGreaterThan(0, $items->count());
        
        // Verify each item has original values preserved
        foreach ($items as $item) {
            $this->assertEquals($item->name, $item->original_name);
            $this->assertEquals($item->description, $item->original_description);
            $this->assertEquals($item->price, $item->original_price);
            $this->assertEquals($item->quantity_available, $item->original_quantity);
            $this->assertEquals($this->wedding->id, $item->wedding_id);
            $this->assertTrue($item->is_enabled);
        }
    }

    /** @test */
    public function it_updates_gift_item_with_valid_data()
    {
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'price' => 10000,
        ]);

        $updatedItem = $this->giftService->updateGiftItem($giftItem->id, [
            'name' => 'Updated Name',
            'price' => 15000,
            'quantity_available' => 5,
        ]);

        $this->assertEquals('Updated Name', $updatedItem->name);
        $this->assertEquals(15000, $updatedItem->price);
        $this->assertEquals(5, $updatedItem->quantity_available);
    }

    /** @test */
    public function it_rejects_price_below_minimum()
    {
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'price' => 10000,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('O preço mínimo é R$ 1,00');

        $this->giftService->updateGiftItem($giftItem->id, [
            'price' => 99, // Below R$ 1,00
        ]);
    }

    /** @test */
    public function it_does_not_update_original_values()
    {
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'name' => 'Original Name',
            'price' => 10000,
            'original_name' => 'Original Name',
            'original_price' => 10000,
        ]);

        $this->giftService->updateGiftItem($giftItem->id, [
            'name' => 'Updated Name',
            'price' => 15000,
        ]);

        $giftItem->refresh();
        
        // Original values should remain unchanged
        $this->assertEquals('Original Name', $giftItem->original_name);
        $this->assertEquals(10000, $giftItem->original_price);
        
        // Current values should be updated
        $this->assertEquals('Updated Name', $giftItem->name);
        $this->assertEquals(15000, $giftItem->price);
    }

    /** @test */
    public function it_restores_gift_item_to_original_values()
    {
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'name' => 'Edited Name',
            'description' => 'Edited Description',
            'price' => 15000,
            'quantity_available' => 5,
            'original_name' => 'Original Name',
            'original_description' => 'Original Description',
            'original_price' => 10000,
            'original_quantity' => 3,
        ]);

        $restoredItem = $this->giftService->restoreGiftItem($giftItem->id);

        $this->assertEquals('Original Name', $restoredItem->name);
        $this->assertEquals('Original Description', $restoredItem->description);
        $this->assertEquals(10000, $restoredItem->price);
        $this->assertEquals(3, $restoredItem->quantity_available);
    }

    /** @test */
    public function it_gets_available_gifts_sorted_by_price_ascending()
    {
        GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'price' => 30000,
            'is_enabled' => true,
            'quantity_available' => 1,
        ]);
        
        GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'price' => 10000,
            'is_enabled' => true,
            'quantity_available' => 1,
        ]);
        
        GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'price' => 20000,
            'is_enabled' => true,
            'quantity_available' => 1,
        ]);

        $gifts = $this->giftService->getAvailableGifts($this->wedding->id, 'price_asc');

        $this->assertCount(3, $gifts);
        $this->assertEquals(10000, $gifts[0]->price);
        $this->assertEquals(20000, $gifts[1]->price);
        $this->assertEquals(30000, $gifts[2]->price);
    }

    /** @test */
    public function it_gets_available_gifts_sorted_by_price_descending()
    {
        GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'price' => 10000,
            'is_enabled' => true,
            'quantity_available' => 1,
        ]);
        
        GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'price' => 30000,
            'is_enabled' => true,
            'quantity_available' => 1,
        ]);
        
        GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'price' => 20000,
            'is_enabled' => true,
            'quantity_available' => 1,
        ]);

        $gifts = $this->giftService->getAvailableGifts($this->wedding->id, 'price_desc');

        $this->assertCount(3, $gifts);
        $this->assertEquals(30000, $gifts[0]->price);
        $this->assertEquals(20000, $gifts[1]->price);
        $this->assertEquals(10000, $gifts[2]->price);
    }

    /** @test */
    public function it_filters_out_disabled_gifts()
    {
        GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'is_enabled' => true,
            'quantity_available' => 1,
        ]);
        
        GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'is_enabled' => false,
            'quantity_available' => 1,
        ]);

        $gifts = $this->giftService->getAvailableGifts($this->wedding->id);

        $this->assertCount(1, $gifts);
    }

    /** @test */
    public function it_filters_out_sold_out_gifts()
    {
        GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'is_enabled' => true,
            'quantity_available' => 1,
        ]);
        
        GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'is_enabled' => true,
            'quantity_available' => 0,
        ]);

        $gifts = $this->giftService->getAvailableGifts($this->wedding->id);

        $this->assertCount(1, $gifts);
    }

    /** @test */
    public function it_validates_gift_availability_correctly()
    {
        $availableGift = GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'is_enabled' => true,
            'quantity_available' => 1,
        ]);
        
        $soldOutGift = GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'is_enabled' => true,
            'quantity_available' => 0,
        ]);
        
        $disabledGift = GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'is_enabled' => false,
            'quantity_available' => 1,
        ]);

        $this->assertTrue($this->giftService->validateGiftAvailability($availableGift->id));
        $this->assertFalse($this->giftService->validateGiftAvailability($soldOutGift->id));
        $this->assertFalse($this->giftService->validateGiftAvailability($disabledGift->id));
        $this->assertFalse($this->giftService->validateGiftAvailability('00000000-0000-0000-0000-000000000000'));
    }

    /** @test */
    public function it_decrements_gift_quantity_atomically()
    {
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'quantity_available' => 5,
            'quantity_sold' => 0,
        ]);

        $this->giftService->decrementGiftQuantity($giftItem->id);

        $giftItem->refresh();
        $this->assertEquals(4, $giftItem->quantity_available);
        $this->assertEquals(1, $giftItem->quantity_sold);
    }

    /** @test */
    public function it_disables_gift_when_quantity_reaches_zero()
    {
        $giftItem = GiftItem::factory()->create([
            'wedding_id' => $this->wedding->id,
            'quantity_available' => 1,
            'quantity_sold' => 0,
            'is_enabled' => true,
        ]);

        $this->giftService->decrementGiftQuantity($giftItem->id);

        $giftItem->refresh();
        $this->assertEquals(0, $giftItem->quantity_available);
        $this->assertFalse($giftItem->is_enabled);
    }
}
