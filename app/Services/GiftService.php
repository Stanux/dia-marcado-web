<?php

namespace App\Services;

use App\Models\GiftItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GiftService
{
    /**
     * Initialize default catalog for a new wedding event.
     *
     * @param string $weddingId
     * @return Collection
     */
    public function initializeDefaultCatalog(string $weddingId): Collection
    {
        $defaultItems = $this->getDefaultCatalogItems();
        $createdItems = collect();

        DB::transaction(function () use ($weddingId, $defaultItems, &$createdItems) {
            foreach ($defaultItems as $item) {
                $giftItem = GiftItem::create([
                    'wedding_id' => $weddingId,
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'photo_url' => $item['photo_url'],
                    'price' => $item['price'],
                    'quantity_available' => $item['quantity'],
                    'quantity_sold' => 0,
                    'is_enabled' => true,
                    // Store original values for restoration
                    'original_name' => $item['name'],
                    'original_description' => $item['description'],
                    'original_price' => $item['price'],
                    'original_quantity' => $item['quantity'],
                ]);

                $createdItems->push($giftItem);
            }
        });

        return $createdItems;
    }

    /**
     * Get the default catalog items.
     *
     * @return array
     */
    private function getDefaultCatalogItems(): array
    {
        return [
            [
                'name' => 'Jogo de Panelas',
                'description' => 'Jogo de panelas antiaderente com 5 peças',
                'photo_url' => '/images/gifts/panelas.jpg',
                'price' => 25000, // R$ 250,00 em centavos
                'quantity' => 1,
            ],
            [
                'name' => 'Jogo de Cama',
                'description' => 'Jogo de cama casal 100% algodão',
                'photo_url' => '/images/gifts/jogo-cama.jpg',
                'price' => 15000, // R$ 150,00
                'quantity' => 2,
            ],
            [
                'name' => 'Liquidificador',
                'description' => 'Liquidificador potente 1000W',
                'photo_url' => '/images/gifts/liquidificador.jpg',
                'price' => 20000, // R$ 200,00
                'quantity' => 1,
            ],
            [
                'name' => 'Cafeteira Elétrica',
                'description' => 'Cafeteira elétrica programável',
                'photo_url' => '/images/gifts/cafeteira.jpg',
                'price' => 18000, // R$ 180,00
                'quantity' => 1,
            ],
            [
                'name' => 'Jogo de Toalhas',
                'description' => 'Jogo de toalhas de banho 100% algodão',
                'photo_url' => '/images/gifts/toalhas.jpg',
                'price' => 12000, // R$ 120,00
                'quantity' => 3,
            ],
            [
                'name' => 'Aparelho de Jantar',
                'description' => 'Aparelho de jantar 20 peças em porcelana',
                'photo_url' => '/images/gifts/aparelho-jantar.jpg',
                'price' => 30000, // R$ 300,00
                'quantity' => 1,
            ],
            [
                'name' => 'Ferro de Passar',
                'description' => 'Ferro de passar a vapor',
                'photo_url' => '/images/gifts/ferro.jpg',
                'price' => 15000, // R$ 150,00
                'quantity' => 1,
            ],
            [
                'name' => 'Aspirador de Pó',
                'description' => 'Aspirador de pó portátil',
                'photo_url' => '/images/gifts/aspirador.jpg',
                'price' => 35000, // R$ 350,00
                'quantity' => 1,
            ],
            [
                'name' => 'Micro-ondas',
                'description' => 'Micro-ondas 30 litros',
                'photo_url' => '/images/gifts/microondas.jpg',
                'price' => 40000, // R$ 400,00
                'quantity' => 1,
            ],
            [
                'name' => 'Jogo de Taças',
                'description' => 'Jogo de taças de cristal 12 peças',
                'photo_url' => '/images/gifts/tacas.jpg',
                'price' => 10000, // R$ 100,00
                'quantity' => 2,
            ],
        ];
    }

    /**
     * Update a gift item with validation.
     *
     * @param string $itemId
     * @param array $data
     * @return GiftItem
     * @throws \InvalidArgumentException
     */
    public function updateGiftItem(string $itemId, array $data): GiftItem
    {
        $giftItem = GiftItem::withoutGlobalScopes()->findOrFail($itemId);

        // Validate minimum price if price is being updated
        if (isset($data['price']) && $data['price'] < 100) {
            throw new \InvalidArgumentException('O preço mínimo é R$ 1,00');
        }

        // Only update allowed fields (not original values)
        $allowedFields = ['name', 'description', 'photo_url', 'price', 'quantity_available', 'is_enabled'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        $giftItem->update($updateData);

        return $giftItem->fresh();
    }

    /**
     * Restore a gift item to its original values.
     *
     * @param string $itemId
     * @return GiftItem
     */
    public function restoreGiftItem(string $itemId): GiftItem
    {
        $giftItem = GiftItem::withoutGlobalScopes()->findOrFail($itemId);
        $giftItem->restoreOriginal();

        return $giftItem->fresh();
    }

    /**
     * Get available gifts for public display.
     *
     * @param string $weddingId
     * @param string|null $sortBy
     * @return Collection
     */
    public function getAvailableGifts(string $weddingId, ?string $sortBy = 'price'): Collection
    {
        $query = GiftItem::withoutGlobalScopes()
            ->where('wedding_id', $weddingId)
            ->where('is_enabled', true)
            ->where('quantity_available', '>', 0);

        // Apply sorting
        if ($sortBy === 'price' || $sortBy === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sortBy === 'price_desc') {
            $query->orderBy('price', 'desc');
        }

        return $query->get();
    }

    /**
     * Validate if a gift is available for purchase.
     *
     * @param string $itemId
     * @return bool
     */
    public function validateGiftAvailability(string $itemId): bool
    {
        $giftItem = GiftItem::withoutGlobalScopes()->find($itemId);

        if (!$giftItem) {
            return false;
        }

        return $giftItem->isAvailable();
    }

    /**
     * Decrement gift quantity after confirmed payment.
     *
     * @param string $itemId
     * @return void
     */
    public function decrementGiftQuantity(string $itemId): void
    {
        DB::transaction(function () use ($itemId) {
            $giftItem = GiftItem::withoutGlobalScopes()->lockForUpdate()->findOrFail($itemId);
            $giftItem->decrementQuantity();
        });
    }
}
