<?php

declare(strict_types=1);

use DrinkSafe\Reports\Controllers\ReportController;
use DrinkSafe\Venues\Controllers\VenueController;
use DrinkSafe\Venues\Controllers\VenueSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| DrinkSafe API Routes
|--------------------------------------------------------------------------
|
| API routes for the DrinkSafe application, organized by module.
| All routes are prefixed with 'api/'.
|
*/

// Venues Module Routes
Route::prefix('api/venues')->group(function (): void {
    Route::get('/', [VenueController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('api.venues.index');

    Route::get('/search', [VenueSearchController::class, 'search'])
        ->middleware('throttle:30,1')
        ->name('api.venues.search');

    Route::get('/{uuid}', [VenueController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('api.venues.show');

    Route::post('/', [VenueController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('api.venues.store');
});

// Reports Module Routes
Route::prefix('api/reports')->group(function (): void {
    Route::get('/', [ReportController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('api.reports.index');

    Route::get('/{uuid}', [ReportController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('api.reports.show');

    Route::post('/', [ReportController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('api.reports.store');
});
