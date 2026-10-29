<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Partners\Models\PartnerLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * PartnerRedirectController
 *
 * Sends a scanned partner QR code on to its destination and adds one to the
 * partner's total for the day. A printed code must never dead-end, so an
 * unknown or inactive code goes to the home page instead.
 *
 * The route sits outside the web middleware group, so no session is started
 * and no cookie is set. The user agent is read only to skip obvious bots and
 * link preview fetchers, and is never stored or logged.
 */
final class PartnerRedirectController extends Controller
{
    /**
     * User agents that are not a person scanning a code, for the crawlers and
     * fetchers that start their user agent with `Mozilla/` like a browser does.
     *
     * The bare `bot` also matches some phone model names (e.g. CUBOT), so scans
     * from those phones are not counted.
     */
    private const string NON_SCAN_AGENTS = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview|curl|wget|python-requests|headless|inspectiontool|embedly|viber|http-client|node-fetch|axios|okhttp|googleother|-user\b|externalfetcher|iframely/i';

    /**
     * Redirect a partner code, counting the scan if it came from a person.
     */
    public function __invoke(Request $request, ?string $code = null): RedirectResponse
    {
        $partnerLink = PartnerLink::query()
            ->where('code', Str::lower((string) $code))
            ->where('active', true)
            ->first();

        if ($partnerLink === null) {
            return redirect('/');
        }

        if ($this->isScan($request)) {
            // a failed count is still reported, but must not stop a printed code redirecting
            rescue($partnerLink->recordScan(...));
        }

        return redirect($partnerLink->redirectPath());
    }

    /**
     * Whether the request looks like a person opening the link, rather than a
     * HEAD check, a bot, or a script.
     *
     * Every phone browser and in-app browser sends a user agent starting with
     * `Mozilla/`, and almost no script client does, so anything else (including
     * no user agent at all) is not counted.
     */
    private function isScan(Request $request): bool
    {
        return $request->isMethod('GET')
            && str_starts_with((string) $request->userAgent(), 'Mozilla/')
            && preg_match(self::NON_SCAN_AGENTS, (string) $request->userAgent()) !== 1;
    }
}
