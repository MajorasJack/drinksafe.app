<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Reports\Requests\AdminCoverageRequest;
use DrinkSafe\Reports\Services\CoverageReport;
use DrinkSafe\Shared\Seo\Seo;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AdminCoverageController
 *
 * The admin's coverage table, the same one `drinksafe:coverage` prints.
 * Access is limited to admins by the admin route group.
 */
final class AdminCoverageController extends Controller
{
    /**
     * Show venue and report counts for the cities typed, or the default cities.
     *
     * @param  AdminCoverageRequest  $request  The validated cities
     * @param  CoverageReport  $coverageReport  The counting shared with the command
     * @return Response Inertia response for the coverage page
     */
    public function __invoke(AdminCoverageRequest $request, CoverageReport $coverageReport): Response
    {
        return Inertia::render('admin/Coverage', [
            'seo' => Seo::default()->withTitle('Coverage - Drink Safe')->noindex()->toArray(),
            'coverage' => $coverageReport->forCities($request->cities()),
            'cities' => implode(', ', $request->cities()),
        ]);
    }
}
