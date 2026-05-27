<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * SupportController
 *
 * Displays the support page with information about how to
 * help fund Drink Safe and support the project.
 */
final class SupportController extends Controller
{
    /**
     * Display the support page.
     *
     * @return Response Inertia response
     */
    public function __invoke(): Response
    {
        return Inertia::render('Support');
    }
}
