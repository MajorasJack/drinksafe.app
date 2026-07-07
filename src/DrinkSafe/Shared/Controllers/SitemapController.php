<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * SitemapController
 *
 * Generates a dynamic XML sitemap listing every public, indexable URL:
 * the static marketing/legal pages plus every venue detail page. The
 * rendered XML is cached for six hours so it is not rebuilt per request.
 *
 * Non-indexable routes (report submission, dashboard, settings, auth and
 * the API) are deliberately excluded.
 */
final class SitemapController extends Controller
{
    /**
     * Cache key for the rendered sitemap XML.
     */
    private const CACHE_KEY = 'sitemap.xml';

    /**
     * Static public routes to include, keyed by route name with their
     * associated change frequency and crawl priority hints.
     *
     * @var array<string, array{changefreq: string, priority: string}>
     */
    private const STATIC_ROUTES = [
        'home' => ['changefreq' => 'weekly', 'priority' => '1.0'],
        'map' => ['changefreq' => 'weekly', 'priority' => '0.9'],
        'about' => ['changefreq' => 'monthly', 'priority' => '0.5'],
        'support' => ['changefreq' => 'monthly', 'priority' => '0.5'],
        'analytics' => ['changefreq' => 'weekly', 'priority' => '0.6'],
        'privacy' => ['changefreq' => 'yearly', 'priority' => '0.3'],
        'terms' => ['changefreq' => 'yearly', 'priority' => '0.3'],
        'contact' => ['changefreq' => 'monthly', 'priority' => '0.5'],
    ];

    /**
     * Return the cached XML sitemap as an application/xml response.
     */
    public function __invoke(): Response
    {
        $sitemap = Cache::remember(
            self::CACHE_KEY,
            now()->addHours(6),
            fn (): string => $this->generateSitemap(),
        );

        return response($sitemap, Response::HTTP_OK)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Build the complete sitemap XML document.
     */
    private function generateSitemap(): string
    {
        $urls = [];

        foreach (self::STATIC_ROUTES as $routeName => $hints) {
            $urls[] = $this->buildUrl(
                $this->absoluteUrl($routeName),
                null,
                $hints['changefreq'],
                $hints['priority'],
            );
        }

        Venue::query()
            ->select(['slug', 'updated_at'])
            ->lazy()
            ->each(function (Venue $venue) use (&$urls): void {
                $urls[] = $this->buildUrl(
                    $this->absoluteUrl('venues.show', $venue->slug),
                    $venue->updated_at?->toAtomString(),
                    'weekly',
                    '0.7',
                );
            });

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>%s<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">%s</urlset>%s',
            PHP_EOL,
            implode('', $urls),
            PHP_EOL,
        );
    }

    /**
     * Build an absolute URL from the configured application host.
     *
     * Uses the config host (not the request host) so the cached sitemap stays
     * host-stable and consistent with the canonical/OG URLs across the app.
     * The trailing slash is trimmed so the home URL matches its canonical.
     *
     * @param  string  $routeName  Named route to resolve
     * @param  mixed  $parameters  Route parameters
     */
    private function absoluteUrl(string $routeName, mixed $parameters = []): string
    {
        return rtrim(
            rtrim((string) config('app.url'), '/').route($routeName, $parameters, false),
            '/',
        );
    }

    /**
     * Build a single <url> entry, escaping all dynamic values for XML.
     *
     * @param  string  $location  Absolute URL for the page
     * @param  string|null  $lastModified  ISO 8601 last-modified timestamp
     * @param  string  $changeFrequency  Expected crawl change frequency
     * @param  string  $priority  Relative crawl priority (0.0 - 1.0)
     */
    private function buildUrl(string $location, ?string $lastModified, string $changeFrequency, string $priority): string
    {
        $lastModifiedTag = $lastModified !== null
            ? sprintf('<lastmod>%s</lastmod>', e($lastModified))
            : '';

        return sprintf(
            '<url><loc>%s</loc>%s<changefreq>%s</changefreq><priority>%s</priority></url>',
            e($location),
            $lastModifiedTag,
            $changeFrequency,
            $priority,
        );
    }
}
