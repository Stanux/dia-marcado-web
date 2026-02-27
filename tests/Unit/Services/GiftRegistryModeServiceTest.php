<?php

namespace Tests\Unit\Services;

use App\Models\GiftItem;
use App\Models\GiftRegistryConfig;
use App\Models\Wedding;
use App\Services\GiftRegistryModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftRegistryModeServiceTest extends TestCase
{
    use RefreshDatabase;

    private GiftRegistryModeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GiftRegistryModeService::class);
    }

    /** @test */
    public function it_converts_prices_when_switching_between_quantity_and_quota_modes()
    {
        $wedding = Wedding::factory()->create();
        GiftRegistryConfig::withoutGlobalScopes()->where('wedding_id', $wedding->id)->update([
            'registry_mode' => GiftRegistryModeService::MODE_QUANTITY,
        ]);

        $item = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'price' => 10000,
            'quantity_available' => 2,
            'quantity_sold' => 1,
            'is_fallback_donation' => false,
        ]);

        $this->service->changeMode($wedding->id, GiftRegistryModeService::MODE_QUOTA);

        $item->refresh();
        $config = GiftRegistryConfig::withoutGlobalScopes()->where('wedding_id', $wedding->id)->firstOrFail();

        $this->assertEquals(GiftRegistryModeService::MODE_QUOTA, $config->registry_mode);
        $this->assertEquals(30000, $item->price);

        $fallback = GiftItem::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->where('is_fallback_donation', true)
            ->first();

        $this->assertNotNull($fallback);
        $this->assertEquals(5000, $fallback->minimum_custom_amount);

        $this->service->changeMode($wedding->id, GiftRegistryModeService::MODE_QUANTITY);

        $item->refresh();
        $config->refresh();

        $this->assertEquals(GiftRegistryModeService::MODE_QUANTITY, $config->registry_mode);
        $this->assertEquals(10000, $item->price);
    }

    /** @test */
    public function it_does_not_convert_fallback_item_price_on_mode_switch()
    {
        $wedding = Wedding::factory()->create();
        GiftRegistryConfig::withoutGlobalScopes()->where('wedding_id', $wedding->id)->update([
            'registry_mode' => GiftRegistryModeService::MODE_QUANTITY,
        ]);

        $regular = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'price' => 8000,
            'quantity_available' => 4,
            'quantity_sold' => 0,
            'is_fallback_donation' => false,
        ]);

        $fallback = GiftItem::factory()->fallbackDonation()->create([
            'wedding_id' => $wedding->id,
            'price' => 5000,
            'minimum_custom_amount' => 5000,
        ]);

        $this->service->changeMode($wedding->id, GiftRegistryModeService::MODE_QUOTA);

        $regular->refresh();
        $fallback->refresh();

        $this->assertEquals(32000, $regular->price);
        $this->assertEquals(5000, $fallback->price);
        $this->assertEquals(5000, $fallback->minimum_custom_amount);
    }
}
