<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Shared\Seo\Seo;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ContactController
 *
 * Displays the contact page.
 * Static page with no dynamic data required.
 */
final class ContactController extends Controller
{
    /**
     * Display the contact page.
     *
     * @return Response Inertia response
     */
    public function __invoke(): Response
    {
        $seo = Seo::default()
            ->withTitle('Contact the Drink Safe Team')
            ->withDescription('Get in touch with the Drink Safe team. Contact us with questions, feedback, venue corrections, or media enquiries about our community safety platform.');

        return Inertia::render('Contact', [
            'seo' => $seo->toArray(),
        ]);
    }
}
