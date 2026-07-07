<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Shared\Seo\Seo;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AboutController
 *
 * Displays the about page with information about Drink Safe.
 * Static page with no dynamic data required.
 */
final class AboutController extends Controller
{
    /**
     * Display the about page.
     *
     * @return Response Inertia response
     */
    public function __invoke(): Response
    {
        $seo = Seo::default()
            ->withTitle('About Drink Safe — How the Platform Works')
            ->withDescription('Learn how Drink Safe works — an anonymous, community-driven platform for sharing and viewing drink-spiking reports to help people stay safe on nights out.');

        return Inertia::render('About', [
            'seo' => $seo->toArray(),
        ]);
    }
}
