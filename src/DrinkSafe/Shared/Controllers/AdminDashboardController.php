<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Shared\Seo\Seo;
use DrinkSafe\Shared\Services\AdminDashboardStats;
use DrinkSafe\Shared\Services\PublicCacheVersion;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AdminDashboardController
 *
 * The admin's landing page: a summary of the review queue, reports received,
 * partner scans and orphaned venues, and a button to refresh the public
 * caches. Access is limited to admins by the admin route group.
 */
final class AdminDashboardController extends Controller
{
    /**
     * Show the dashboard summary.
     *
     * @param  AdminDashboardStats  $adminDashboardStats  The dashboard's numbers
     * @return Response Inertia response for the dashboard
     */
    public function index(AdminDashboardStats $adminDashboardStats): Response
    {
        return Inertia::render('admin/Dashboard', [
            'seo' => Seo::default()->withTitle('Admin dashboard - Drink Safe')->noindex()->toArray(),
            'summary' => $adminDashboardStats->summary(),
        ]);
    }

    /**
     * Rebuild the heatmap and temporal analytics caches on their next request.
     *
     * It bumps their cache version rather than flushing the store, which would
     * also wipe sessions and rate limits.
     *
     * @param  PublicCacheVersion  $publicCacheVersion  The public caches' version
     * @return RedirectResponse Back to the page the admin came from
     */
    public function refreshCaches(PublicCacheVersion $publicCacheVersion): RedirectResponse
    {
        $publicCacheVersion->bump();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Public caches refreshed.']);

        return redirect()->to(url()->previous(route('admin.dashboard')))->with('success', 'Public caches refreshed.');
    }
}
