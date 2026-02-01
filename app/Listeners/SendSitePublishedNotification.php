<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SitePublished;
use App\Notifications\SitePublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener that sends notifications when a site is published.
 * 
 * Sends email notifications to all couple members of the wedding.
 */
class SendSitePublishedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(SitePublished $event): void
    {
        $site = $event->site;
        $publisher = $event->publisher;
        
        // Load the wedding relationship if not already loaded
        $site->loadMissing('wedding.couple');
        
        $wedding = $site->wedding;
        
        if (!$wedding) {
            Log::warning('SitePublished: No wedding found for site', [
                'site_id' => $site->id,
                'slug' => $site->slug,
            ]);
            return;
        }
        
        $coupleMembers = $wedding->couple;
        
        if ($coupleMembers->isEmpty()) {
            Log::warning('SitePublished: No couple members found for wedding', [
                'site_id' => $site->id,
                'wedding_id' => $wedding->id,
            ]);
            return;
        }
        
        $notification = new SitePublishedNotification($site, $publisher);
        
        foreach ($coupleMembers as $member) {
            try {
                $member->notify($notification);
                
                Log::info('SitePublished: Notification sent', [
                    'site_id' => $site->id,
                    'slug' => $site->slug,
                    'user_id' => $member->id,
                    'user_email' => $member->email,
                    'published_at' => $site->published_at,
                ]);
            } catch (\Throwable $e) {
                Log::error('SitePublished: Failed to send notification', [
                    'site_id' => $site->id,
                    'user_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Log::info('SitePublished: All notifications processed', [
            'site_id' => $site->id,
            'slug' => $site->slug,
            'total_recipients' => $coupleMembers->count(),
        ]);
    }
}
