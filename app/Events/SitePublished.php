<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SiteLayout;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a site is published.
 * 
 * Used to trigger notifications to couple members.
 */
class SitePublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param SiteLayout $site The published site layout
     * @param User $publisher The user who published the site
     */
    public function __construct(
        public readonly SiteLayout $site,
        public readonly User $publisher
    ) {}
}
