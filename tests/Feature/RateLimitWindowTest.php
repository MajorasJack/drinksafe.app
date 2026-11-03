<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

it('limits every throttled route over a one-minute window', function (): void {
    $throttleMiddleware = collect(Route::getRoutes()->getRoutes())
        ->flatMap(fn (RoutingRoute $route): array => $route->gatherMiddleware())
        ->filter(fn (mixed $middleware): bool => is_string($middleware) && Str::startsWith($middleware, 'throttle:'))
        ->unique()
        ->values();

    $windowsInSeconds = $throttleMiddleware->mapWithKeys(function (string $middleware): array {
        $limiterArguments = explode(',', Str::after($middleware, 'throttle:'));

        if (count($limiterArguments) > 1) {
            return [$middleware => [(int) $limiterArguments[1] * 60]];
        }

        $request = tap(Request::create('/'))->setLaravelSession(app('session.store'));
        $namedLimits = Collection::wrap(RateLimiter::limiter($limiterArguments[0])($request));

        return [$middleware => $namedLimits->map(fn (Limit $limit): int => $limit->decaySeconds)->all()];
    });

    expect($throttleMiddleware)->not->toBeEmpty()
        ->and($windowsInSeconds->flatten()->unique()->values()->all())->toBe([60]);
});

it('expires sessions after two hours', function (): void {
    expect(config('session.lifetime'))->toBe(120);
});
