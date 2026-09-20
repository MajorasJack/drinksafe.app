<?php

use App\Http\Middleware\EnforceProduction;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // AddLinkHeadersForPreloadedAssets is deliberately not registered. It
        // repeats every Vite preload tag in a `Link` header, which on the
        // heavier pages pushes the response headers past nginx's 4 KB FastCGI
        // buffer and the origin answers with a 502. The <link rel="preload">
        // tags in the HTML give browsers the same hint without the header.
        $middleware->web(append: [
            EnforceProduction::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
