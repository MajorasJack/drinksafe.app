<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Shared\Seo\Seo;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PartnersController
 *
 * Displays the page for venues and partners who are deciding whether to
 * put up Drink Safe material. Static page with no dynamic data required.
 */
final class PartnersController extends Controller
{
    /**
     * Display the venues and partners page.
     *
     * @return Response Inertia response
     */
    public function __invoke(): Response
    {
        $seo = Seo::default()
            ->withTitle('For Venues and Partners - Drink Safe')
            ->withDescription('How Drink Safe reviews reports, where to put up our posters and stickers, and free printable downloads for venues, students\' unions and community groups.');

        return Inertia::render('Partners', [
            'seo' => $seo->toArray(),
        ]);
    }
}
