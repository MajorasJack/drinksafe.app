<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Shared\Seo\Seo;
use Inertia\Inertia;
use Inertia\Response;

/**
 * TermsController
 *
 * Displays the terms of service page.
 * Static page with no dynamic data required.
 */
final class TermsController extends Controller
{
    /**
     * Display the terms of service page.
     *
     * @return Response Inertia response
     */
    public function __invoke(): Response
    {
        $seo = Seo::default()
            ->withTitle('Terms of Service - Drink Safe')
            ->withDescription('Read the Drink Safe terms of service covering acceptable use, community reporting guidelines, and your responsibilities when using the platform.');

        return Inertia::render('Terms', [
            'seo' => $seo->toArray(),
        ]);
    }
}
