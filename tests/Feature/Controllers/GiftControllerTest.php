<?php

namespace Tests\Feature\Controllers;

use App\Models\GiftItem;
use App\Models\GiftRegistryConfig;
use App\Models\Transaction;
use App\Models\Wedding;
use App\Services\Payment\IdempotencyService;
use App\Services\Payment\PagSeguroClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GiftControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_available_gifts_for_an_event()
    {
        // Disable observer to prevent auto-creation of default catalog
        Wedding::withoutEvents(function () use (&$wedding) {
            $wedding = Wedding::factory()->create();
        });
        
        // Manually create config since observer is disabled
        GiftRegistryConfig::create([
            'wedding_id' => $wedding->id,
            'is_enabled' => true,
            'section_title' => 'Lista de Presentes',
            'fee_modality' => 'couple_pays',
        ]);

        // Create available gifts
        $gift1 = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Gift 1',
            'price' => 10000, // R$ 100.00
            'quantity_available' => 5,
            'is_enabled' => true,
        ]);

        $gift2 = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Gift 2',
            'price' => 20000, // R$ 200.00
            'quantity_available' => 3,
            'is_enabled' => true,
        ]);

        // Create unavailable gift (should not appear)
        GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Unavailable Gift',
            'price' => 15000,
            'quantity_available' => 0,
            'is_enabled' => true,
        ]);

        // Create disabled gift (should not appear)
        GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Disabled Gift',
            'price' => 15000,
            'quantity_available' => 5,
            'is_enabled' => false,
        ]);

        // Request gifts
        $response = $this->getJson("/api/events/{$wedding->id}/gifts");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['name' => 'Gift 1']);
        $response->assertJsonFragment(['name' => 'Gift 2']);
        $response->assertJsonMissing(['name' => 'Unavailable Gift']);
        $response->assertJsonMissing(['name' => 'Disabled Gift']);
    }

    /** @test */
    public function it_sorts_gifts_by_price()
    {
        // Disable observer to prevent auto-creation of default catalog
        Wedding::withoutEvents(function () use (&$wedding) {
            $wedding = Wedding::factory()->create();
        });
        
        // Manually create config since observer is disabled
        GiftRegistryConfig::create([
            'wedding_id' => $wedding->id,
            'is_enabled' => true,
            'section_title' => 'Lista de Presentes',
            'fee_modality' => 'couple_pays',
        ]);

        // Create gifts with different prices
        GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Expensive Gift',
            'price' => 50000,
            'quantity_available' => 1,
            'is_enabled' => true,
        ]);

        GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Cheap Gift',
            'price' => 10000,
            'quantity_available' => 1,
            'is_enabled' => true,
        ]);

        GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Medium Gift',
            'price' => 25000,
            'quantity_available' => 1,
            'is_enabled' => true,
        ]);

        // Request with ascending sort (default)
        $response = $this->getJson("/api/events/{$wedding->id}/gifts?sort=price_asc");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('Cheap Gift', $data[0]['name']);
        $this->assertEquals('Medium Gift', $data[1]['name']);
        $this->assertEquals('Expensive Gift', $data[2]['name']);
    }

    /** @test */
    public function it_shows_gift_details()
    {
        $wedding = Wedding::factory()->create();
        
        // Update the auto-created gift registry config
        $config = GiftRegistryConfig::withoutGlobalScopes()->where('wedding_id', $wedding->id)->first();
        $config->update(['fee_modality' => 'couple_pays']);

        $gift = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Test Gift',
            'description' => 'Test Description',
            'price' => 15000,
            'quantity_available' => 3,
            'quantity_sold' => 2,
            'is_enabled' => true,
        ]);

        $response = $this->getJson("/api/events/{$wedding->id}/gifts/{$gift->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $gift->id,
                'name' => 'Test Gift',
                'description' => 'Test Description',
                'display_price' => 15000,
                'quantity_available' => 3,
                'quantity_sold' => 2,
                'is_available' => true,
                'is_sold_out' => false,
            ],
        ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_gift()
    {
        $wedding = Wedding::factory()->create();

        $response = $this->getJson("/api/events/{$wedding->id}/gifts/non-existent-id");

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Not Found',
            'message' => 'Presente não encontrado.',
        ]);
    }

    /** @test */
    public function it_enforces_multi_tenancy_for_gifts()
    {
        $wedding1 = Wedding::factory()->create();
        $wedding2 = Wedding::factory()->create();

        $gift = GiftItem::factory()->create([
            'wedding_id' => $wedding1->id,
        ]);

        // Try to access gift from different wedding
        $response = $this->getJson("/api/events/{$wedding2->id}/gifts/{$gift->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_calculates_display_price_based_on_fee_modality()
    {
        $wedding = Wedding::factory()->create();
        
        // Update the auto-created gift registry config to guest_pays
        $config = GiftRegistryConfig::withoutGlobalScopes()->where('wedding_id', $wedding->id)->first();
        $config->update(['fee_modality' => 'guest_pays']);

        $gift = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'price' => 10000, // R$ 100.00
            'quantity_available' => 1,
            'is_enabled' => true,
        ]);

        $response = $this->getJson("/api/events/{$wedding->id}/gifts/{$gift->id}");

        $response->assertStatus(200);
        $displayPrice = $response->json('data.display_price');
        
        // Display price should be higher than original when guest pays
        $this->assertGreaterThan(10000, $displayPrice);
    }

    /** @test */
    public function it_rejects_purchase_of_unavailable_gift()
    {
        $wedding = Wedding::factory()->create();
        
        // Config is auto-created by observer

        $gift = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'quantity_available' => 0, // Sold out
            'is_enabled' => true,
        ]);

        $purchaseData = [
            'payment_method' => 'pix',
            'idempotency_key' => Str::random(32),
            'payer' => [
                'name' => 'Test Buyer',
                'email' => 'buyer@example.com',
                'document' => '12345678901',
            ],
        ];

        $response = $this->postJson("/api/events/{$wedding->id}/gifts/{$gift->id}/purchase", $purchaseData);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'Unavailable',
            'message' => 'Este presente não está mais disponível.',
        ]);
    }

    /** @test */
    public function it_validates_purchase_request_data()
    {
        $wedding = Wedding::factory()->create();
        $gift = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'quantity_available' => 1,
            'is_enabled' => true,
        ]);

        // Missing required fields
        $response = $this->postJson("/api/events/{$wedding->id}/gifts/{$gift->id}/purchase", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_method', 'idempotency_key', 'payer.name', 'payer.email', 'payer.document']);
    }

    /** @test */
    public function it_validates_payment_method()
    {
        $wedding = Wedding::factory()->create();
        $gift = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'quantity_available' => 1,
            'is_enabled' => true,
        ]);

        $purchaseData = [
            'payment_method' => 'invalid_method',
            'idempotency_key' => Str::random(32),
            'payer' => [
                'name' => 'Test Buyer',
                'email' => 'buyer@example.com',
                'document' => '12345678901',
            ],
        ];

        $response = $this->postJson("/api/events/{$wedding->id}/gifts/{$gift->id}/purchase", $purchaseData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_method']);
    }

    /** @test */
    public function it_validates_document_format()
    {
        $wedding = Wedding::factory()->create();
        $gift = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'quantity_available' => 1,
            'is_enabled' => true,
        ]);

        $purchaseData = [
            'payment_method' => 'pix',
            'idempotency_key' => Str::random(32),
            'payer' => [
                'name' => 'Test Buyer',
                'email' => 'buyer@example.com',
                'document' => '123', // Invalid format
            ],
        ];

        $response = $this->postJson("/api/events/{$wedding->id}/gifts/{$gift->id}/purchase", $purchaseData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payer.document']);
    }

    /** @test */
    public function it_requires_card_token_for_credit_card_payment()
    {
        $wedding = Wedding::factory()->create();
        $gift = GiftItem::factory()->create([
            'wedding_id' => $wedding->id,
            'quantity_available' => 1,
            'is_enabled' => true,
        ]);

        $purchaseData = [
            'payment_method' => 'credit_card',
            'idempotency_key' => Str::random(32),
            'payer' => [
                'name' => 'Test Buyer',
                'email' => 'buyer@example.com',
                'document' => '12345678901',
            ],
            // Missing card_token
        ];

        $response = $this->postJson("/api/events/{$wedding->id}/gifts/{$gift->id}/purchase", $purchaseData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['card_token']);
    }
}
