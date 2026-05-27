<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Resources\VenueResource;
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
            'venue' => (new VenueResource($venue))->resolve(),
        ]);
    }
}
