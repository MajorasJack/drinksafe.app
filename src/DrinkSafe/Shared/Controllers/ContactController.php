<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
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
        return Inertia::render('Contact');
    }
}
