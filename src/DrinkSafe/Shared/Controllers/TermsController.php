<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
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
        return Inertia::render('Terms');
    }
}
