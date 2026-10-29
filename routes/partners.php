<?php

declare(strict_types=1);

use DrinkSafe\Partners\Controllers\PartnerRedirectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Partner QR Redirects
|--------------------------------------------------------------------------
|
| Loaded outside the web middleware group, so a scan starts no session, sets
| no cookie and has no per-IP throttle key. The code matches anything,
| slashes included, so a mistyped or truncated link still redirects home
| rather than showing a 404.
|
*/

Route::get('/go/{code?}', PartnerRedirectController::class)
    ->where('code', '.*')
    ->name('partners.go');
