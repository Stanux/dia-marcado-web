<?php

namespace App\Services;

use App\Models\GiftItem;
use App\Models\GiftRegistryConfig;
use Illuminate\Support\Facades\DB;

class GiftRegistryModeService
{
    public const MODE_QUANTITY = 'quantity';

    public const MODE_QUOTA = 'quota';

    public const FALLBACK_MINIMUM_AMOUNT = 5000;

    /**
     * Change registry mode and convert existing items when needed.
     */
    public function changeMode(string $weddingId, string $newMode): GiftRegistryConfig
    {
        $newMode = $this->normalizeMode($newMode);

        return DB::transaction(function () use ($weddingId, $newMode): GiftRegistryConfig {
            $config = GiftRegistryConfig::withoutGlobalScopes()
                ->where('wedding_id', $weddingId)
                ->lockForUpdate()
                ->first();

            if (!$config) {
                $config = GiftRegistryConfig::create([
                    'wedding_id' => $weddingId,
                    'is_enabled' => true,
                    'section_title' => 'Lista de Presentes',
                    'title_font_family' => null,
                    'title_font_size' => null,
                    'title_color' => null,
                    'title_style' => 'normal',
                    'fee_modality' => 'couple_pays',
                    'registry_mode' => self::MODE_QUANTITY,
                ]);
            }

            $currentMode = $this->normalizeMode($config->registry_mode ?? self::MODE_QUANTITY);

            if ($currentMode !== $newMode) {
                $this->convertExistingItemPrices($weddingId, $currentMode, $newMode);
                $config->registry_mode = $newMode;
                $config->save();
            }

            if ($newMode === self::MODE_QUOTA) {
                $this->ensureFallbackDonationItem($weddingId);
            }

            return $config->fresh();
        });
    }

    /**
     * Ensure the fallback donation item exists for a wedding.
     */
    public function ensureFallbackDonationItem(string $weddingId): GiftItem
    {
        $fallback = GiftItem::withoutGlobalScopes()->firstOrCreate(
            [
                'wedding_id' => $weddingId,
                'is_fallback_donation' => true,
            ],
            [
                'name' => 'Doação livre para o casal',
                'description' => 'Contribua com qualquer valor para os noivos.',
                'default_image_key' => 'fundo-livre.png',
                'photo_url' => null,
                'price' => self::FALLBACK_MINIMUM_AMOUNT,
                'quantity_available' => 1000000,
                'quantity_sold' => 0,
                'is_enabled' => true,
                'minimum_custom_amount' => self::FALLBACK_MINIMUM_AMOUNT,
                'original_name' => 'Doação livre para o casal',
                'original_description' => 'Contribua com qualquer valor para os noivos.',
                'original_price' => self::FALLBACK_MINIMUM_AMOUNT,
                'original_quantity' => 1000000,
            ]
        );

        if ($fallback->minimum_custom_amount === null || $fallback->minimum_custom_amount < self::FALLBACK_MINIMUM_AMOUNT) {
            $fallback->minimum_custom_amount = self::FALLBACK_MINIMUM_AMOUNT;
            $fallback->save();
        }

        return $fallback->fresh();
    }

    private function convertExistingItemPrices(string $weddingId, string $fromMode, string $toMode): void
    {
        if ($fromMode === $toMode) {
            return;
        }

        $items = GiftItem::withoutGlobalScopes()
            ->where('wedding_id', $weddingId)
            ->where('is_fallback_donation', false)
            ->lockForUpdate()
            ->get();

        foreach ($items as $item) {
            $quantityTotal = max(1, $item->quantity_available + $item->quantity_sold);

            if ($fromMode === self::MODE_QUANTITY && $toMode === self::MODE_QUOTA) {
                $item->price = (int) ($item->price * $quantityTotal);
            } elseif ($fromMode === self::MODE_QUOTA && $toMode === self::MODE_QUANTITY) {
                $item->price = (int) round($item->price / $quantityTotal);
            }

            $item->save();
        }
    }

    private function normalizeMode(?string $mode): string
    {
        $mode = strtolower(trim((string) $mode));

        if (in_array($mode, [self::MODE_QUANTITY, self::MODE_QUOTA], true)) {
            return $mode;
        }

        return self::MODE_QUANTITY;
    }
}
