<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
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
        return Inertia::render('Privacy');
    }
}
