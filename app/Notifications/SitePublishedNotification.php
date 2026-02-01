<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SiteLayout;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a wedding site is published.
 * 
 * Sent to all couple members of the wedding.
 */
class SitePublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param SiteLayout $site The published site
     * @param User $publisher The user who published the site
     */
    public function __construct(
        public readonly SiteLayout $site,
        public readonly User $publisher
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $siteUrl = $this->site->getPublicUrl();
        $siteTitle = $this->getSiteTitle();
        $publishedAt = $this->site->published_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
        $coupleNames = $this->getCoupleNames();

        return (new MailMessage)
            ->subject('Seu site de casamento foi publicado!')
            ->view('emails.site-published', [
                'siteTitle' => $siteTitle,
                'siteUrl' => $siteUrl,
                'publishedAt' => $publishedAt,
                'coupleNames' => $coupleNames,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'site_id' => $this->site->id,
            'site_slug' => $this->site->slug,
            'published_at' => $this->site->published_at,
            'publisher_id' => $this->publisher->id,
        ];
    }

    /**
     * Get the site title from content or generate a default.
     */
    private function getSiteTitle(): string
    {
        $content = $this->site->published_content ?? $this->site->draft_content ?? [];
        
        // Try to get title from meta
        if (isset($content['meta']['title']) && !empty($content['meta']['title'])) {
            return $content['meta']['title'];
        }
        
        // Try to get title from header section
        if (isset($content['sections']['header']['title']) && !empty($content['sections']['header']['title'])) {
            return $content['sections']['header']['title'];
        }
        
        // Fallback to slug-based title
        return 'Site de Casamento - ' . $this->site->slug;
    }

    /**
     * Get the couple names from the wedding.
     */
    private function getCoupleNames(): string
    {
        $this->site->loadMissing('wedding.couple');
        
        $wedding = $this->site->wedding;
        
        if (!$wedding) {
            return 'Casal';
        }
        
        $coupleMembers = $wedding->couple;
        
        if ($coupleMembers->isEmpty()) {
            return 'Casal';
        }
        
        $names = $coupleMembers->pluck('name')->filter()->toArray();
        
        if (empty($names)) {
            return 'Casal';
        }
        
        if (count($names) === 1) {
            return $names[0];
        }
        
        if (count($names) === 2) {
            return $names[0] . ' e ' . $names[1];
        }
        
        // More than 2 names
        $lastName = array_pop($names);
        return implode(', ', $names) . ' e ' . $lastName;
    }
}
