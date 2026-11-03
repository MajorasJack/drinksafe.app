<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;

it('loads every map image from the site itself or from CARTO', function (string $path): void {
    Venue::factory()->has(Report::factory())->create(['slug' => 'map-image-origins']);

    $page = visit($path)->wait(3);

    $imageHosts = $page->script(<<<'JS'
        performance.getEntriesByType('resource')
            .filter((entry) => entry.initiatorType === 'img' || /\.(png|jpe?g|gif|svg|webp)(\?|$)/.test(entry.name))
            .map((entry) => new URL(entry.name).host)
            .map((host) => (host === location.host ? 'own-origin' : host))
        JS);
    $unexpectedHosts = collect($imageHosts)
        ->reject(fn (string $host): bool => $host === 'own-origin' || str_ends_with($host, '.basemaps.cartocdn.com'))
        ->values()
        ->all();

    expect($imageHosts)->not->toBeEmpty()
        ->and($unexpectedHosts)->toBe([]);
})->with([
    'venue page' => '/venues/map-image-origins',
    'map page' => '/map',
]);
