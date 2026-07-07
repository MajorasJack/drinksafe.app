<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Shared\Seo\Seo;
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
        $seo = Seo::default()
            ->withTitle('Support Drink Safe - Help Keep People Safe')
            ->withDescription('Support Drink Safe with a donation to help cover hosting, security, and development costs - keeping this community safety platform free for everyone.');

        return Inertia::render('Support', [
            'seo' => $seo->toArray(),
        ]);
    }
}
