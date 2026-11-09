<?php

declare(strict_types=1);

use DrinkSafe\Analytics\Controllers\TemporalAnalyticsController;
use DrinkSafe\Heatmap\Controllers\HeatmapController;
use DrinkSafe\Partners\Controllers\AdminPartnerController;
use DrinkSafe\Reports\Controllers\AdminCoverageController;
use DrinkSafe\Reports\Controllers\AdminReportSearchController;
use DrinkSafe\Reports\Controllers\ReportController;
use DrinkSafe\Reports\Controllers\ReportModerationController;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Controllers\AdminVenueController;
use DrinkSafe\Venues\Controllers\VenueController;
use DrinkSafe\Venues\Controllers\VenueSafetyScoreController;
use DrinkSafe\Venues\Controllers\VenueSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| DrinkSafe API Routes
|--------------------------------------------------------------------------
|
| API routes for the DrinkSafe application, organised by module, prefixed
| with 'api/'. The admin area sits at the end under 'admin/'.
|
*/

// Venues Module Routes
Route::prefix('api/venues')->group(function (): void {
    Route::get('/', [VenueController::class, 'index'])
        ->middleware('throttle:240,1,venues-index')
        ->name('api.venues.index');

    Route::get('/search', [VenueSearchController::class, 'search'])
        ->middleware('throttle:120,1,venues-search')
        ->name('api.venues.search');

    Route::get('/{uuid}', [VenueController::class, 'show'])
        ->middleware('throttle:240,1,venues-show')
        ->name('api.venues.show');

    Route::get('/{uuid}/safety-score', VenueSafetyScoreController::class)
        ->middleware('throttle:240,1,venues-safety-score')
        ->name('api.venues.safety-score');

    Route::post('/', [VenueController::class, 'store'])
        ->middleware('throttle:40,1,venues-store')
        ->name('api.venues.store');
});

// Reports Module Routes
Route::prefix('api/reports')->group(function (): void {
    Route::get('/', [ReportController::class, 'index'])
        ->middleware('throttle:240,1,reports-index')
        ->name('api.reports.index');

    Route::get('/{uuid}', [ReportController::class, 'show'])
        ->middleware('throttle:240,1,reports-show')
        ->name('api.reports.show');

    Route::post('/', [ReportController::class, 'store'])
        ->middleware('throttle:reports')
        ->name('api.reports.store');
});

// Analytics Module Routes
Route::prefix('api/analytics')->group(function (): void {
    Route::get('/temporal', TemporalAnalyticsController::class)
        ->middleware('throttle:240,1,analytics-temporal')
        ->name('api.analytics.temporal');
});

// Heatmap Module Routes
Route::prefix('api')->group(function (): void {
    Route::get('/heatmap', HeatmapController::class)
        ->middleware('throttle:240,1,heatmap')
        ->name('api.heatmap');
});

// Admin Routes
// `can` runs before the two-factor check so users who are not admins get a 403, not a setup prompt.
Route::prefix('admin')
    ->middleware(['auth', 'auth.session', sprintf('can:moderate,%s', Report::class), 'two-factor.confirmed'])
    ->name('admin.')
    ->group(function (): void {
        Route::inertia('/', 'admin/Dashboard')->name('dashboard');

        Route::get('/reports', [ReportModerationController::class, 'index'])->name('reports.index');
        Route::get('/reports/all', AdminReportSearchController::class)->name('reports.search');
        Route::post('/reports/bulk', [ReportModerationController::class, 'bulk'])->name('reports.bulk');
        Route::patch('/reports/{uuid}', [ReportModerationController::class, 'update'])->name('reports.update');

        Route::get('/venues', [AdminVenueController::class, 'index'])->name('venues.index');
        Route::get('/venues/{uuid}', [AdminVenueController::class, 'show'])->name('venues.show');
        Route::patch('/venues/{uuid}', [AdminVenueController::class, 'update'])->name('venues.update');
        Route::post('/venues/{uuid}/publish', [AdminVenueController::class, 'publish'])->name('venues.publish');
        Route::post('/venues/{uuid}/unpublish', [AdminVenueController::class, 'unpublish'])->name('venues.unpublish');

        Route::get('/partners', [AdminPartnerController::class, 'index'])->name('partners.index');
        Route::patch('/partners/{code}', [AdminPartnerController::class, 'update'])->name('partners.update');

        Route::get('/coverage', AdminCoverageController::class)->name('coverage');
    });
