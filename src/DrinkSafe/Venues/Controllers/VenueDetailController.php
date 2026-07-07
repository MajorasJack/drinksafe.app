<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Shared\Seo\Seo;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Resources\VenueResource;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * VenueDetailController
 *
 * Displays a single venue's detail page with all associated reports.
 * Shows venue information and complete report history.
 *
 * Uses route model binding with slug-based URLs for SEO and security.
 */
final class VenueDetailController extends Controller
{
    /**
     * Display the venue detail page.
     *
     * Loads the venue with its reports relationship for display on the page.
     * Laravel automatically handles 404 via route model binding.
     *
     * @param  Venue  $venue  The venue resolved via slug-based route model binding
     * @return Response Inertia response with venue data
     */
    public function __invoke(Venue $venue): Response
    {
        $venue->loadMissing('reports')->loadCount('reports');

        return Inertia::render('VenueDetail', [
            'seo' => $this->buildSeo($venue)->toArray(),
            'venue' => (new VenueResource($venue))->resolve(),
        ]);
    }

    /**
     * Build the search-engine and social-share metadata for a venue.
     *
     * The copy is deliberately neutral: it describes the page as a collection of
     * community-submitted safety reports for the venue's location and never
     * asserts wrongdoing. Structured data is limited to a BreadcrumbList and a
     * neutral Place descriptor (no Review/AggregateRating markup) to avoid any
     * defamation exposure on this sensitive domain.
     */
    private function buildSeo(Venue $venue): Seo
    {
        $canonical = sprintf(
            '%s/venues/%s',
            rtrim((string) config('app.url'), '/'),
            $venue->slug,
        );

        $title = sprintf(
            '%s, %s - community safety reports | Drink Safe',
            Str::limit($venue->name, 40),
            $venue->city,
        );

        $description = sprintf(
            'View community-submitted safety reports for %s in %s on Drink Safe. Read anonymous incident information shared to help people stay informed and safe.',
            $venue->name,
            $venue->city,
        );

        return Seo::default()
            ->withTitle($title)
            ->withDescription($description)
            ->withCanonical($canonical)
            ->withJsonLd($this->buildBreadcrumbSchema($venue, $canonical))
            ->withJsonLd($this->buildPlaceSchema($venue, $canonical));
    }

    /**
     * Build the BreadcrumbList JSON-LD block for the venue (Home › Map › Venue).
     *
     * @return array<string, mixed>
     */
    private function buildBreadcrumbSchema(Venue $venue, string $canonical): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => $baseUrl,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Map',
                    'item' => sprintf('%s/map', $baseUrl),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $venue->name,
                    'item' => $canonical,
                ],
            ],
        ];
    }

    /**
     * Build the neutral Place JSON-LD block for the venue.
     *
     * Contains only a location descriptor (name, address, geo, url). It
     * intentionally omits any Review, AggregateRating, rating, or incident
     * markup for legal/defamation reasons.
     *
     * @return array<string, mixed>
     */
    private function buildPlaceSchema(Venue $venue, string $canonical): array
    {
        $address = [
            '@type' => 'PostalAddress',
            'addressLocality' => $venue->city,
            'addressCountry' => 'GB',
        ];

        if ($venue->address !== null && $venue->address !== '') {
            $address['streetAddress'] = $venue->address;
        }

        $place = [
            '@context' => 'https://schema.org',
            '@type' => 'Place',
            'name' => $venue->name,
            'address' => $address,
            'url' => $canonical,
        ];

        // Only advertise a geo point when both coordinates are present, so a
        // missing coordinate never emits a misleading location at (0, 0).
        if ($venue->latitude !== null && $venue->longitude !== null) {
            $place['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $venue->latitude,
                'longitude' => (float) $venue->longitude,
            ];
        }

        return $place;
    }
}
