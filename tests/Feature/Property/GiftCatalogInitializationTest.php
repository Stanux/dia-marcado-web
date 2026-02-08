<?php

namespace Tests\Feature\Property;

use App\Models\GiftItem;
use App\Models\GiftRegistryConfig;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: gift-registry, Property: Automatic Catalog Initialization
 * 
 * When a new wedding is created, the system must automatically:
 * 1. Initialize a default gift catalog with predefined items
 * 2. Create a default GiftRegistryConfig
 * 
 * Validates: Requirements 1.1
 */
class GiftCatalogInitializationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: Default catalog is created when wedding is created
     * @test
     */
    public function default_catalog_is_created_when_wedding_is_created(): void
    {
        // Create a wedding
        $wedding = Wedding::factory()->create([
            'title' => 'Test Wedding',
            'wedding_date' => now()->addMonths(6),
        ]);

        // Verify gift items were created (without global scopes)
        $giftItems = GiftItem::withoutGlobalScopes()->where('wedding_id', $wedding->id)->get();
        
        $this->assertGreaterThan(
            0,
            $giftItems->count(),
            'Gift catalog should be automatically initialized with items'
        );

        // Verify all items have original values preserved
        foreach ($giftItems as $item) {
            $this->assertNotNull($item->original_name, 'Original name should be set');
            $this->assertNotNull($item->original_price, 'Original price should be set');
            $this->assertNotNull($item->original_quantity, 'Original quantity should be set');
            $this->assertEquals($item->name, $item->original_name, 'Name should match original');
            $this->assertEquals($item->price, $item->original_price, 'Price should match original');
            $this->assertEquals($item->quantity_available, $item->original_quantity, 'Quantity should match original');
        }
    }

    /**
     * Property: Default GiftRegistryConfig is created when wedding is created
     * @test
     */
    public function default_gift_registry_config_is_created_when_wedding_is_created(): void
    {
        // Create a wedding
        $wedding = Wedding::factory()->create([
            'title' => 'Test Wedding',
            'wedding_date' => now()->addMonths(6),
        ]);

        // Verify GiftRegistryConfig was created (without global scopes)
        $config = GiftRegistryConfig::withoutGlobalScopes()->where('wedding_id', $wedding->id)->first();
        
        $this->assertNotNull($config, 'GiftRegistryConfig should be automatically created');
        $this->assertTrue($config->is_enabled, 'Config should be enabled by default');
        $this->assertEquals('Lista de Presentes', $config->section_title, 'Default title should be set');
        $this->assertEquals('couple_pays', $config->fee_modality, 'Default fee modality should be couple_pays');
        $this->assertEquals('normal', $config->title_style, 'Default title style should be normal');
    }

    /**
     * Property: Catalog items have valid prices (minimum R$ 1,00)
     * @test
     */
    public function catalog_items_have_valid_minimum_prices(): void
    {
        // Create a wedding
        $wedding = Wedding::factory()->create([
            'title' => 'Test Wedding',
            'wedding_date' => now()->addMonths(6),
        ]);

        // Verify all items have prices >= 100 centavos (R$ 1,00) (without global scopes)
        $giftItems = GiftItem::withoutGlobalScopes()->where('wedding_id', $wedding->id)->get();
        
        foreach ($giftItems as $item) {
            $this->assertGreaterThanOrEqual(
                100,
                $item->price,
                "Gift item '{$item->name}' should have price >= R$ 1,00 (100 centavos), got {$item->price}"
            );
        }
    }

    /**
     * Property: Catalog items are enabled by default
     * @test
     */
    public function catalog_items_are_enabled_by_default(): void
    {
        // Create a wedding
        $wedding = Wedding::factory()->create([
            'title' => 'Test Wedding',
            'wedding_date' => now()->addMonths(6),
        ]);

        // Verify all items are enabled (without global scopes)
        $giftItems = GiftItem::withoutGlobalScopes()->where('wedding_id', $wedding->id)->get();
        
        foreach ($giftItems as $item) {
            $this->assertTrue(
                $item->is_enabled,
                "Gift item '{$item->name}' should be enabled by default"
            );
        }
    }

    /**
     * Property: Multiple weddings get independent catalogs
     * @test
     */
    public function multiple_weddings_get_independent_catalogs(): void
    {
        // Create two weddings
        $wedding1 = Wedding::factory()->create(['title' => 'Wedding 1']);
        $wedding2 = Wedding::factory()->create(['title' => 'Wedding 2']);

        // Get gift items for each wedding (without global scopes)
        $items1 = GiftItem::withoutGlobalScopes()->where('wedding_id', $wedding1->id)->get();
        $items2 = GiftItem::withoutGlobalScopes()->where('wedding_id', $wedding2->id)->get();

        // Verify both have items
        $this->assertGreaterThan(0, $items1->count(), 'Wedding 1 should have gift items');
        $this->assertGreaterThan(0, $items2->count(), 'Wedding 2 should have gift items');

        // Verify items are different (different IDs)
        $ids1 = $items1->pluck('id')->toArray();
        $ids2 = $items2->pluck('id')->toArray();
        
        $this->assertEmpty(
            array_intersect($ids1, $ids2),
            'Gift items should be independent between weddings'
        );

        // Verify each wedding has its own config (without global scopes)
        $config1 = GiftRegistryConfig::withoutGlobalScopes()->where('wedding_id', $wedding1->id)->first();
        $config2 = GiftRegistryConfig::withoutGlobalScopes()->where('wedding_id', $wedding2->id)->first();

        $this->assertNotNull($config1, 'Wedding 1 should have config');
        $this->assertNotNull($config2, 'Wedding 2 should have config');
        $this->assertNotEquals($config1->id, $config2->id, 'Configs should be different');
    }
}
