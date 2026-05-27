<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
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
        return Inertia::render('About');
    }
}
