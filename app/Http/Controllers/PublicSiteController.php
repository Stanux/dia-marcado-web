<?php

namespace App\Http\Controllers;

use App\Contracts\Site\AccessTokenServiceInterface;
use App\Contracts\Site\PlaceholderServiceInterface;
use App\Http\Requests\Site\AuthenticateSiteRequest;
use App\Models\SiteLayout;
use App\Models\SiteTemplate;
use App\Services\Guests\RsvpSubmissionException;
use App\Services\Guests\InviteValidationService;
use App\Services\Site\SiteContentSchema;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
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
        private readonly InviteValidationService $inviteValidationService,
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
    public function show(Request $request, string $slug)
    {
        // Find site by slug (without wedding scope since this is public)
        $site = SiteLayout::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        // Site not found
        if ($site === null) {
            abort(404);
        }

        $publishedSite = $site->is_published && $site->published_content !== null;
        $inviteToken = $request->query('token');
        $inviteTokenState = $this->resolveInviteTokenState($inviteToken, (string) $site->wedding_id);

        if (!$publishedSite && !in_array($inviteTokenState, ['valid', 'limit_reached'], true)) {
            abort(404);
        }

        // Check if site requires authentication
        if ($publishedSite && $site->access_token !== null) {
            // Check if user is already authenticated for this site
            if (!$this->isAuthenticated($site)) {
                return response()->view('public.site-password', [
                    'slug' => $slug,
                    'siteTitle' => $site->published_content['meta']['title'] ?? 'Site Protegido',
                ], 200, [
                    'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
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

        $rawContent = $publishedSite
            ? (array) $site->published_content
            : $this->buildRsvpOnlyContent($site);

        $normalizedContent = SiteContentSchema::normalize($rawContent);

        // Apply placeholders to published content
        $content = $this->placeholderService->replaceInArray(
            $normalizedContent,
            $wedding
        );
        $content = $this->rewriteLocalUrlsToCurrentHost($content, $request);

        $siteData = $site->makeHidden(['draft_content', 'published_content'])->toArray();

        // Use Inertia to render with Vue components (same as preview)
        $response = inertia('Public/Site', [
            'site' => $siteData,
            'content' => $content,
            'wedding' => $wedding,
            'inviteTokenState' => $inviteTokenState,
        ])->withViewData([
            'pageTitle' => $content['meta']['title'] ?? null,
        ])->toResponse($request);

        if ($this->shouldUsePublicCaching($publishedSite, $site, $inviteToken, $inviteTokenState)) {
            $etag = $this->generatePublicEtag($site);
            $lastModified = $this->resolvePublicLastModified($site);
            $headers = $this->buildPublicCacheHeaders($etag, $lastModified);

            if ($this->isClientCacheFresh($request, $etag, $lastModified)) {
                return response('', 304, $headers);
            }

            foreach ($headers as $header => $value) {
                $response->headers->set($header, $value);
            }
        } else {
            $this->applyPrivateNoStoreHeaders($response);
        }

        return $response;
    }

    /**
     * Display a public preview for a published system template.
     */
    public function showTemplate(Request $request, string $slug)
    {
        $template = SiteTemplate::query()
            ->where('slug', $slug)
            ->where('is_public', true)
            ->whereNull('wedding_id')
            ->firstOrFail();

        $previewWedding = new \App\Models\Wedding([
            'title' => $template->name,
            'wedding_date' => null,
            'venue' => '',
            'city' => '',
            'state' => '',
            'settings' => [],
            'is_active' => true,
        ]);

        $content = SiteContentSchema::normalize((array) ($template->content ?? []));
        $content = $this->placeholderService->replaceInArray($content, $previewWedding);
        $content = $this->rewriteLocalUrlsToCurrentHost($content, $request);

        $siteData = [
            'id' => $template->id,
            'slug' => $template->slug,
            'is_published' => true,
            'public_url' => route('public.site.template.preview', ['slug' => $template->slug]),
        ];

        $weddingData = [
            'id' => 0,
            'title' => $template->name,
            'wedding_date' => null,
            'venue' => null,
            'city' => null,
            'state' => null,
            'settings' => [],
            'gift_registry_config' => null,
            'guest_events' => [],
            'site_slug' => $template->slug,
        ];

        return inertia('Public/Site', [
            'site' => $siteData,
            'content' => $content,
            'wedding' => $weddingData,
            'inviteTokenState' => null,
            'isTemplatePreview' => true,
        ])->withViewData([
            'pageTitle' => $content['meta']['title'] ?? ('Template - ' . $template->name),
        ])->toResponse($request);
    }

    private function resolveInviteTokenState(?string $token, string $weddingId): string
    {
        if (!$token) {
            return 'missing';
        }

        try {
            $this->inviteValidationService->resolveForWedding($token, $weddingId);

            return 'valid';
        } catch (RsvpSubmissionException $exception) {
            if ($exception->statusCode() === 409) {
                return 'limit_reached';
            }

            return 'invalid';
        } catch (\Throwable) {
            return 'invalid';
        }
    }

    private function buildRsvpOnlyContent(SiteLayout $site): array
    {
        $content = SiteContentSchema::getDefaultContent();

        foreach ($content['sections'] as $sectionKey => $section) {
            $content['sections'][$sectionKey]['enabled'] = false;
        }

        $sourceContent = is_array($site->draft_content)
            ? $site->draft_content
            : (is_array($site->published_content) ? $site->published_content : []);

        $sourceRsvp = $sourceContent['sections']['rsvp'] ?? [];
        if (!is_array($sourceRsvp)) {
            $sourceRsvp = [];
        }

        $content['sections']['rsvp'] = array_replace_recursive(
            $content['sections']['rsvp'],
            $sourceRsvp
        );
        $content['sections']['rsvp']['enabled'] = true;

        if (isset($sourceContent['theme']) && is_array($sourceContent['theme'])) {
            $content['theme'] = array_replace($content['theme'], $sourceContent['theme']);
        }

        $weddingTitle = $site->wedding?->title ?: 'Casamento';
        $content['meta']['title'] = 'RSVP - ' . $weddingTitle;
        $content['meta']['description'] = 'Confirmação de presença do convite.';

        return $content;
    }

    private function shouldUsePublicCaching(bool $publishedSite, SiteLayout $site, ?string $inviteToken, string $inviteTokenState): bool
    {
        return $publishedSite
            && $site->access_token === null
            && !$inviteToken
            && $inviteTokenState === 'missing';
    }

    private function rewriteLocalUrlsToCurrentHost(array $content, Request $request): array
    {
        $currentOrigin = rtrim($request->getSchemeAndHttpHost(), '/');
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        $localHosts = array_values(array_filter(array_unique([
            '127.0.0.1',
            'localhost',
            $appHost,
        ])));

        return $this->rewriteContentValue($content, $currentOrigin, $localHosts);
    }

    private function rewriteContentValue(mixed $value, string $currentOrigin, array $localHosts): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $child) {
                $value[$key] = $this->rewriteContentValue($child, $currentOrigin, $localHosts);
            }

            return $value;
        }

        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        $parts = parse_url($value);
        if ($parts === false) {
            return $value;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || !in_array($host, $localHosts, true)) {
            return $value;
        }

        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $currentOrigin . $path . $query . $fragment;
    }

    private function generatePublicEtag(SiteLayout $site): string
    {
        $contentHash = sha1(
            json_encode(
                $site->published_content ?? [],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ) ?: '{}'
        );

        $fingerprint = implode('|', [
            (string) $site->id,
            (string) optional($site->published_at)->getTimestamp(),
            (string) optional($site->updated_at)->getTimestamp(),
            $contentHash,
        ]);

        return '"' . sha1($fingerprint) . '"';
    }

    private function resolvePublicLastModified(SiteLayout $site): \DateTimeImmutable
    {
        $timestamp = optional($site->published_at)->getTimestamp()
            ?? optional($site->updated_at)->getTimestamp()
            ?? time();

        return (new \DateTimeImmutable('@' . $timestamp))->setTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * @return array<string, string>
     */
    private function buildPublicCacheHeaders(string $etag, \DateTimeImmutable $lastModified): array
    {
        return [
            'Cache-Control' => 'public, no-cache, max-age=0, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'ETag' => $etag,
            'Last-Modified' => $lastModified->format('D, d M Y H:i:s') . ' GMT',
        ];
    }

    private function isClientCacheFresh(Request $request, string $etag, \DateTimeImmutable $lastModified): bool
    {
        $ifNoneMatch = $request->headers->get('If-None-Match');
        if ($ifNoneMatch !== null && $this->matchesEtag($ifNoneMatch, $etag)) {
            return true;
        }

        $ifModifiedSince = $request->headers->get('If-Modified-Since');
        if ($ifModifiedSince !== null) {
            $clientTimestamp = strtotime($ifModifiedSince);

            if ($clientTimestamp !== false && $clientTimestamp >= $lastModified->getTimestamp()) {
                return true;
            }
        }

        return false;
    }

    private function matchesEtag(string $ifNoneMatch, string $etag): bool
    {
        $normalizedCurrent = trim($etag, '"');

        foreach (explode(',', $ifNoneMatch) as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '*') {
                return true;
            }

            if (str_starts_with($candidate, 'W/')) {
                $candidate = substr($candidate, 2);
            }

            $candidate = trim($candidate, '"');

            if ($candidate !== '' && hash_equals($normalizedCurrent, $candidate)) {
                return true;
            }
        }

        return false;
    }

    private function applyPrivateNoStoreHeaders(SymfonyResponse $response): void
    {
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
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
        if ($site->access_token === null) {
            return true;
        }

        $sessionKey = self::SESSION_KEY_PREFIX . $site->id;
        $sessionData = Session::get($sessionKey);

        if (!is_array($sessionData) || !isset($sessionData['fingerprint']) || !is_string($sessionData['fingerprint'])) {
            Session::forget($sessionKey);
            return false;
        }

        $currentFingerprint = $this->buildAccessTokenFingerprint($site);
        $isValid = hash_equals($currentFingerprint, $sessionData['fingerprint']);

        if (!$isValid) {
            Session::forget($sessionKey);
        }

        return $isValid;
    }

    /**
     * Mark the current session as authenticated for a site.
     *
     * @param SiteLayout $site
     * @return void
     */
    private function setAuthenticated(SiteLayout $site): void
    {
        if ($site->access_token === null) {
            return;
        }

        $sessionKey = self::SESSION_KEY_PREFIX . $site->id;
        Session::put($sessionKey, [
            'fingerprint' => $this->buildAccessTokenFingerprint($site),
            'authenticated_at' => now()->timestamp,
        ]);
    }

    /**
     * Build a deterministic fingerprint for the current site password.
     * If the password changes, the fingerprint changes and prior sessions are invalidated.
     */
    private function buildAccessTokenFingerprint(SiteLayout $site): string
    {
        $appKey = (string) (config('app.key') ?: 'dia-marcado-site-auth');
        $token = (string) $site->access_token;

        return hash_hmac('sha256', "{$site->id}|{$token}", $appKey);
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
