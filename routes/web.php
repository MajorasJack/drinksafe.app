<?php

use DrinkSafe\Reports\Controllers\SubmitReportController;
use DrinkSafe\Shared\Controllers\AboutController;
use DrinkSafe\Shared\Controllers\HomeController;
use DrinkSafe\Shared\Controllers\MapController;
use DrinkSafe\Venues\Controllers\VenueDetailController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

/*
|--------------------------------------------------------------------------
| DrinkSafe Public Routes
|--------------------------------------------------------------------------
|
| Public-facing pages for the DrinkSafe application.
| These routes render Inertia pages for end users.
|
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/map', MapController::class)->name('map');
Route::get('/venues/{venue}', VenueDetailController::class)->name('venues.show');
Route::get('/about', AboutController::class)->name('about');

Route::get('/submit-report', [SubmitReportController::class, 'create'])->name('reports.create');
Route::post('/submit-report', [SubmitReportController::class, 'store'])->name('reports.store');

/*
|--------------------------------------------------------------------------
| Authentication Routes (Future)
|--------------------------------------------------------------------------
|
| Authentication routes will be added here when admin features are implemented.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/drinksafe.php';
