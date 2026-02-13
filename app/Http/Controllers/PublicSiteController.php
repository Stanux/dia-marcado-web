<?php

namespace App\Http\Controllers;

use App\Contracts\Site\AccessTokenServiceInterface;
use App\Contracts\Site\PlaceholderServiceInterface;
use App\Http\Requests\Site\AuthenticateSiteRequest;
use App\Models\SiteLayout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for public site access.
 * 
 * Handles displaying published wedding sites, password authentication,
 * and calendar file generation.
 */
class PublicSiteController extends Controller
{
    /**
     * Session key prefix for authenticated sites.
     */
    private const SESSION_KEY_PREFIX = 'site_authenticated:';

    public function __construct(
        private readonly AccessTokenServiceInterface $accessTokenService,
        private readonly PlaceholderServiceInterface $placeholderService,
    ) {}

    /**
     * Display a published site by its slug.
     * 
     * If the site requires authentication and the user is not authenticated,
     * shows the password form instead.
     *
     * @param string $slug The site slug
     * @return Response|\Illuminate\View\View
     */
    public function show(string $slug)
    {
        // Find site by slug (without wedding scope since this is public)
        $site = SiteLayout::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        // Site not found
        if ($site === null) {
            abort(404);
        }

        // Site not published
        if (!$site->is_published || $site->published_content === null) {
            abort(404);
        }

        // Check if site requires authentication
        if ($site->access_token !== null) {
            // Check if user is already authenticated for this site
            if (!$this->isAuthenticated($site)) {
                return view('public.site-password', [
                    'slug' => $slug,
                    'siteTitle' => $site->published_content['meta']['title'] ?? 'Site Protegido',
                ]);
            }
        }

        // Load the wedding for placeholder replacement
        $wedding = $site->wedding;
        
        // Load gift registry config and guest events for the wedding (public access)
        $wedding->load([
            'giftRegistryConfig' => fn ($query) => $query->withoutGlobalScopes(),
            'guestEvents' => fn ($query) => $query->withoutGlobalScopes(),
        ]);

        // Apply placeholders to published content
        $content = $this->placeholderService->replaceInArray(
            $site->published_content,
            $wedding
        );

        $siteData = $site->makeHidden(['draft_content', 'published_content'])->toArray();

        // Use Inertia to render with Vue components (same as preview)
        return inertia('Public/Site', [
            'site' => $siteData,
            'content' => $content,
            'wedding' => $wedding,
        ])->withViewData([
            'pageTitle' => $content['meta']['title'] ?? null,
        ]);
    }

    /**
     * Authenticate access to a password-protected site.
     *
     * @param AuthenticateSiteRequest $request
     * @param string $slug The site slug
     * @return \Illuminate\Http\RedirectResponse
     */
    public function authenticate(AuthenticateSiteRequest $request, string $slug)
    {
        // Find site by slug
        $site = SiteLayout::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        // Site not found - return generic error (don't reveal if site exists)
        if ($site === null) {
            return back()->withErrors([
                'password' => 'Senha incorreta.',
            ]);
        }

        // Check rate limiting
        $identifier = $request->ip() . ':' . $slug;
        if ($this->accessTokenService->isRateLimited($identifier)) {
            $minutes = \App\Models\SystemConfig::get('site.rate_limit_minutes', 15);
            return back()->withErrors([
                'password' => "Muitas tentativas. Tente novamente em {$minutes} minutos.",
            ]);
        }

        // Verify password
        $password = $request->validated('password');
        if (!$this->accessTokenService->verify($site, $password)) {
            // Record failed attempt
            $this->accessTokenService->recordFailedAttempt($identifier);
            
            return back()->withErrors([
                'password' => 'Senha incorreta.',
            ]);
        }

        // Authentication successful - store in session
        $this->setAuthenticated($site);

        return redirect()->route('public.site.show', ['slug' => $slug]);
    }

    /**
     * Generate and download a calendar (.ics) file for the wedding.
     *
     * @param string $slug The site slug
     * @return StreamedResponse
     */
    public function calendar(string $slug): StreamedResponse
    {
        // Find site by slug
        $site = SiteLayout::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        // Site not found
        if ($site === null) {
            abort(404);
        }

        // Site not published
        if (!$site->is_published) {
            abort(404);
        }

        // Check authentication if required
        if ($site->access_token !== null && !$this->isAuthenticated($site)) {
            abort(403);
        }

        $wedding = $site->wedding;

        // Generate ICS content
        $icsContent = $this->generateIcsContent($wedding, $site);

        // Generate filename
        $filename = $this->generateCalendarFilename($wedding);

        return response()->streamDownload(function () use ($icsContent) {
            echo $icsContent;
        }, $filename, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Check if the current session is authenticated for a site.
     *
     * @param SiteLayout $site
     * @return bool
     */
    private function isAuthenticated(SiteLayout $site): bool
    {
        $sessionKey = self::SESSION_KEY_PREFIX . $site->id;
        return Session::get($sessionKey, false) === true;
    }

    /**
     * Mark the current session as authenticated for a site.
     *
     * @param SiteLayout $site
     * @return void
     */
    private function setAuthenticated(SiteLayout $site): void
    {
        $sessionKey = self::SESSION_KEY_PREFIX . $site->id;
        Session::put($sessionKey, true);
    }

    /**
     * Generate ICS calendar content for a wedding.
     *
     * @param \App\Models\Wedding $wedding
     * @param SiteLayout $site
     * @return string
     */
    private function generateIcsContent($wedding, SiteLayout $site): string
    {
        $now = now()->format('Ymd\THis\Z');
        $uid = $site->id . '@' . parse_url(config('app.url'), PHP_URL_HOST);
        
        // Get event details
        $title = $wedding->title ?? 'Casamento';
        $venue = $wedding->venue ?? '';
        $city = $wedding->city ?? '';
        $state = $wedding->state ?? '';
        
        // Build location string
        $location = trim(implode(', ', array_filter([$venue, $city, $state])));
        
        // Format date (all-day event if no time specified)
        $eventDate = $wedding->wedding_date;
        if ($eventDate) {
            $dtStart = $eventDate->format('Ymd');
            $dtEnd = $eventDate->copy()->addDay()->format('Ymd');
        } else {
            // Fallback to today if no date
            $dtStart = now()->format('Ymd');
            $dtEnd = now()->addDay()->format('Ymd');
        }

        // Get description from site content
        $description = '';
        if (isset($site->published_content['sections']['saveTheDate']['description'])) {
            $description = $site->published_content['sections']['saveTheDate']['description'];
            // Apply placeholders
            $description = $this->placeholderService->replacePlaceholders($description, $wedding);
        }

        // Escape special characters for ICS format
        $title = $this->escapeIcsText($title);
        $location = $this->escapeIcsText($location);
        $description = $this->escapeIcsText($description);

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Wedding Site Builder//PT\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "DTSTAMP:{$now}\r\n";
        $ics .= "DTSTART;VALUE=DATE:{$dtStart}\r\n";
        $ics .= "DTEND;VALUE=DATE:{$dtEnd}\r\n";
        $ics .= "SUMMARY:{$title}\r\n";
        
        if (!empty($location)) {
            $ics .= "LOCATION:{$location}\r\n";
        }
        
        if (!empty($description)) {
            $ics .= "DESCRIPTION:{$description}\r\n";
        }
        
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    /**
     * Escape text for ICS format.
     *
     * @param string $text
     * @return string
     */
    private function escapeIcsText(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Escape special characters
        $text = str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $text);
        
        return $text;
    }

    /**
     * Generate a filename for the calendar download.
     *
     * @param \App\Models\Wedding $wedding
     * @return string
     */
    private function generateCalendarFilename($wedding): string
    {
        $title = $wedding->title ?? 'casamento';
        
        // Normalize filename
        $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $title);
        $filename = preg_replace('/-+/', '-', $filename);
        $filename = trim($filename, '-');
        $filename = strtolower($filename);
        
        if (empty($filename)) {
            $filename = 'casamento';
        }

        return $filename . '.ics';
    }
}
