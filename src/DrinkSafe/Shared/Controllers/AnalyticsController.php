<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Shared\Seo\Seo;
use DrinkSafe\Venues\Models\Venue;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AnalyticsController
 *
 * Displays the time-based analytics page with temporal patterns
 * for incident reports.
 */
final class AnalyticsController extends Controller
{
    /**
     * Display the analytics page.
     *
     * Provides available cities for filtering and renders the Analytics page.
     * The actual analytics data is fetched via API from the frontend.
     */
    public function __invoke(): Response
    {
        $cities = Venue::select('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->toArray();

        $seo = Seo::default()
            ->withTitle('Spiking Incident Analytics & Trends — Drink Safe')
            ->withDescription('Explore time-based analytics on community-reported drink-spiking incidents. Discover temporal patterns and trends by city to understand venue safety.');

        return Inertia::render('Analytics', [
            'seo' => $seo->toArray(),
            'cities' => $cities,
        ]);
    }
}
