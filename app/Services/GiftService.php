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
                    'default_image_key' => $item['default_image_key'],
                    'photo_url' => null,
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
                        'name' => 'Passagem Aérea da Lua de Mel',
                        'description' => 'Ajude com as passagens para nossa lua de mel dos sonhos.',
                        'default_image_key' => 'passagem-lua-mel.png',
                        'price' => 200000, // R$ 2.000,00
                        'quantity' => 1,
                    ],
                    [
                        'name' => 'Noite no Hotel da Lua de Mel',
                        'description' => 'Uma noite especial em nosso destino romântico.',
                        'default_image_key' => 'noite-hotel.png',
                        'price' => 300000, // R$ 3.000,00
                        'quantity' => 1,
                    ],
                    [
                        'name' => 'Jantar Romântico na Viagem',
                        'description' => 'Um jantar inesquecível para celebrarmos o amor.',
                        'default_image_key' => 'jantar-romantico.png',
                        'price' => 15000, // R$ 150,00
                        'quantity' => 5,
                    ],
                    [
                        'name' => 'Drinks na Piscina do Resort',
                        'description' => 'Brinde conosco na piscina durante a lua de mel.',
                        'default_image_key' => 'drinks-piscina.png',
                        'price' => 8000, // R$ 80,00
                        'quantity' => 10,
                    ],
                    [
                        'name' => 'Ensaio Fotográfico Pós-Casamento',
                        'description' => 'Sessão especial para eternizar esse momento.',
                        'default_image_key' => 'ensaio-fotografico.png',
                        'price' => 250000, // R$ 2.500,00
                        'quantity' => 1,
                    ],
                    [
                        'name' => 'Vídeo Cinematográfico da Cerimônia',
                        'description' => 'Ajude a registrar nosso grande dia em vídeo.',
                        'default_image_key' => 'video-cerimonia.png',
                        'price' => 350000, // R$ 3.500,00
                        'quantity' => 1,
                    ],
                    [
                        'name' => 'Sofá da Nova Casa',
                        'description' => 'Contribua para o conforto da nossa sala.',
                        'default_image_key' => 'sofa.png',
                        'price' => 450000, // R$ 4.500,00
                        'quantity' => 1,
                    ],
                    [
                        'name' => 'Cama dos Sonhos',
                        'description' => 'Nossa cama perfeita para começar essa nova fase.',
                        'default_image_key' => 'cama.png',
                        'price' => 800000, // R$ 8.000,00
                        'quantity' => 1,
                    ],
                    [
                        'name' => 'Cozinha Equipada',
                        'description' => 'Ajude a montar nossa cozinha moderna.',
                        'default_image_key' => 'cozinha.png',
                        'price' => 120000, // R$ 1.200,00
                        'quantity' => 3,
                    ],
                    [
                        'name' => 'Entrada do Apartamento',
                        'description' => 'Contribuição para realizarmos o sonho da casa própria.',
                        'default_image_key' => 'entrada-apartamento.png',
                        'price' => 1500000, // R$ 15.000,00
                        'quantity' => 1,
                    ],
                    [
                        'name' => 'Fundo Primeiro Ano de Casados',
                        'description' => 'Ajude nas despesas e experiências do nosso primeiro ano juntos.',
                        'default_image_key' => 'primeiro-ano.png',
                        'price' => 50000, // R$ 500,00
                        'quantity' => 10,
                    ],
                    [
                        'name' => 'Noite de Jogos e Diversão',
                        'description' => 'Um momento especial de diversão a dois.',
                        'default_image_key' => 'noite-jogos.png',
                        'price' => 12000, // R$ 120,00
                        'quantity' => 5,
                    ],
                    [
                        'name' => 'Aluguel de Carro na Lua de Mel',
                        'description' => 'Para explorarmos nosso destino com liberdade.',
                        'default_image_key' => 'aluguel-carro.png',
                        'price' => 180000, // R$ 1.800,00
                        'quantity' => 1,
                    ],
                    [
                        'name' => 'Passeio de Balão',
                        'description' => 'Uma experiência inesquecível nas alturas.',
                        'default_image_key' => 'passeio-balao.png',
                        'price' => 220000, // R$ 2.200,00
                        'quantity' => 2,
                    ],
                    [
                        'name' => 'Fundo Livre para Realizar Sonhos',
                        'description' => 'Contribua com qualquer valor para realizarmos nossos sonhos.',
                        'default_image_key' => 'fundo-livre.png',
                        'price' => 10000, // R$ 100,00
                        'quantity' => 20,
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
