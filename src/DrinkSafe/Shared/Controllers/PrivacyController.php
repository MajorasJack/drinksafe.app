<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Shared\Seo\Seo;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PrivacyController
 *
 * Displays the privacy policy page.
 * Static page with no dynamic data required.
 */
final class PrivacyController extends Controller
{
    /**
     * Display the privacy policy page.
     *
     * @return Response Inertia response
     */
    public function __invoke(): Response
    {
        $seo = Seo::default()
            ->withTitle('Privacy Policy — Drink Safe')
            ->withDescription('Read the Drink Safe privacy policy to understand how we protect your anonymity and handle data across our community drink-spiking awareness platform.');

        return Inertia::render('Privacy', [
            'seo' => $seo->toArray(),
        ]);
    }
}
