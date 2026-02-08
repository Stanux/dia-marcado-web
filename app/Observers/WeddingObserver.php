<?php

namespace App\Observers;

use App\Models\GiftRegistryConfig;
use App\Models\Wedding;
use App\Services\GiftService;

class WeddingObserver
{
    /**
     * The gift service instance.
     */
    protected GiftService $giftService;

    /**
     * Create a new observer instance.
     */
    public function __construct(GiftService $giftService)
    {
        $this->giftService = $giftService;
    }

    /**
     * Handle the Wedding "created" event.
     *
     * @param Wedding $wedding
     * @return void
     */
    public function created(Wedding $wedding): void
    {
        // Initialize default gift catalog
        $this->giftService->initializeDefaultCatalog($wedding->id);

        // Create default gift registry config only if it doesn't exist
        GiftRegistryConfig::firstOrCreate(
            ['wedding_id' => $wedding->id],
            [
                'is_enabled' => true,
                'section_title' => 'Lista de Presentes',
                'title_font_family' => null,
                'title_font_size' => null,
                'title_color' => null,
                'title_style' => 'normal',
                'fee_modality' => 'couple_pays',
            ]
        );
    }
}
