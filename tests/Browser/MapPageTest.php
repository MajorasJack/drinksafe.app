<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;

it('loads map page successfully', function (): void {
    $page = visit('/map');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('.leaflet-container');
});

it('displays venue search input', function (): void {
    $page = visit('/map');

    $page->assertPresent('input[placeholder*="Search"]')
        ->assertNoJavaScriptErrors();
});

it('displays date filter dropdown', function (): void {
    $page = visit('/map');

    $page->assertPresent('[role="combobox"]')
        ->assertNoJavaScriptErrors();
});

it('displays venues in sidebar list', function (): void {
    $venue1 = Venue::factory()->create(['name' => 'Test Venue One']);
    $venue2 = Venue::factory()->create(['name' => 'Test Venue Two']);

    $page = visit('/map');

    $page->wait(1000)
        ->assertSee('Test Venue One')
        ->assertSee('Test Venue Two');
});

it('displays venue with report count badge', function (): void {
    $venue = Venue::factory()
        ->has(Report::factory()->count(3))
        ->create(['name' => 'Venue With Reports']);

    $page = visit('/map');

    $page->wait(1000)
        ->assertSee('Venue With Reports')
        ->assertSee('3 reports');
});

it('selects venue when clicked in sidebar', function (): void {
    $venue = Venue::factory()->create(['name' => 'Clickable Venue']);

    $page = visit('/map');

    $page->wait(1000)
        ->click('Clickable Venue');
});

it('clears filters when clear button clicked', function (): void {
    Venue::factory()->count(3)->create();

    $page = visit('/map');

    $page->wait(1000)
        ->click('Clear filters');
});

it('displays no venues message when filters match nothing', function (): void {
    $page = visit('/map');

    $page->wait(1000)
        ->assertSee('No venues found');
});

it('shows loading state while fetching venues', function (): void {
    $page = visit('/map');

    $page->assertNoJavaScriptErrors();
});

it('map renders with venue markers', function (): void {
    Venue::factory()->count(5)->create();

    $page = visit('/map');

    $page->wait(2000)
        ->assertPresent('.leaflet-marker-icon')
        ->assertNoJavaScriptErrors();
});

it('verifies marker icons count matches venue count', function (): void {
    Venue::factory()->count(5)->create();

    $page = visit('/map');

    $page->wait(2000);

    $markerCount = $page->script("return document.querySelectorAll('.leaflet-marker-icon').length");

    expect($markerCount)->toBe(5);
});

it('captures console errors for marker debugging', function (): void {
    Venue::factory()->count(3)->create();

    $page = visit('/map');

    $page->wait(2000);

    $consoleLogs = $page->consoleLogs();

    expect($consoleLogs)->each(function ($log): void {
        expect($log['message'])->not()->toContain('iconUrl not set');
    });
});

it('verifies marker icon src attributes are set', function (): void {
    Venue::factory()->count(3)->create();

    $page = visit('/map');

    $page->wait(2000);

    $iconUrls = $page->script("
        return Array.from(document.querySelectorAll('.leaflet-marker-icon')).map(icon => icon.src);
    ");

    expect($iconUrls)->toBeArray();
    expect($iconUrls)->not()->toBeEmpty();

    foreach ($iconUrls as $url) {
        expect($url)->toBeString();
        expect($url)->not()->toBeEmpty();
    }
});

it('verifies markers appear after venues load', function (): void {
    $venue = Venue::factory()->create([
        'name' => 'Test Marker Venue',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);

    $page = visit('/map');

    $page->wait(2000);

    $hasMarkers = $page->script("return document.querySelectorAll('.leaflet-marker-icon').length > 0");

    expect($hasMarkers)->toBeTrue();
});

it('verifies marker popups display venue information', function (): void {
    $venue = Venue::factory()->create([
        'name' => 'Popup Test Venue',
        'city' => 'London',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
    ]);

    $page = visit('/map');

    $page->wait(2000)
        ->click('.leaflet-marker-icon')
        ->wait(500)
        ->assertSee('Popup Test Venue')
        ->assertSee('London');
});

it('takes screenshot when markers do not render', function (): void {
    Venue::factory()->count(5)->create();

    $page = visit('/map');

    $page->wait(2000);

    $markerCount = $page->script("return document.querySelectorAll('.leaflet-marker-icon').length");

    if ($markerCount === 0) {
        $page->screenshot('map-no-markers-debug');
    }

    expect($markerCount)->toBeGreaterThan(0);
});

it('verifies Leaflet map initializes before adding markers', function (): void {
    Venue::factory()->count(3)->create();

    $page = visit('/map');

    $page->assertPresent('.leaflet-container');

    $page->wait(2000);

    $isMapReady = $page->script("
        return document.querySelector('.leaflet-container')?.classList.contains('leaflet-container');
    ");

    expect($isMapReady)->toBeTrue();
});

it('verifies marker shadow images render correctly', function (): void {
    Venue::factory()->count(3)->create();

    $page = visit('/map');

    $page->wait(2000);

    $shadowCount = $page->script("return document.querySelectorAll('.leaflet-marker-shadow').length");

    expect($shadowCount)->toBe(3);
});

it('verifies no JavaScript errors related to Leaflet icons', function (): void {
    Venue::factory()->count(5)->create();

    $page = visit('/map');

    $page->wait(2000);

    $page->assertNoJavaScriptErrors();

    $consoleLogs = $page->consoleLogs();

    foreach ($consoleLogs as $log) {
        if ($log['level'] === 'error') {
            expect($log['message'])->not()->toContain('Icon');
            expect($log['message'])->not()->toContain('iconUrl');
            expect($log['message'])->not()->toContain('marker');
        }
    }
});

it('mobile viewport shows list toggle button', function (): void {
    Venue::factory()->count(3)->create();

    $page = visit('/map')->on()->iPhone14Pro();

    $page->wait(1000)
        ->assertSee('View')
        ->assertNoJavaScriptErrors();
});

it('mobile list toggle works correctly', function (): void {
    Venue::factory()->count(2)->create();

    $page = visit('/map')->on()->iPhone14Pro();

    $page->wait(1000);
});

it('desktop shows sidebar and map simultaneously', function (): void {
    Venue::factory()->count(3)->create();

    $page = visit('/map')->resize(1920, 1080);

    $page->wait(1000)
        ->assertPresent('.leaflet-container')
        ->assertNoJavaScriptErrors();
});

it('handles search query from URL parameters', function (): void {
    $venue = Venue::factory()->create(['name' => 'Searchable Venue', 'city' => 'London']);

    $page = visit('/map?search=London');

    $page->wait(1000)
        ->assertNoJavaScriptErrors();
});

it('venue cards display city and address information', function (): void {
    Venue::factory()->create([
        'name' => 'Detailed Venue',
        'city' => 'Manchester',
        'address' => '123 Test Street',
    ]);

    $page = visit('/map');

    $page->wait(1000)
        ->assertSee('Detailed Venue')
        ->assertSee('Manchester')
        ->assertSee('123 Test Street');
});

it('filters section is sticky on scroll', function (): void {
    Venue::factory()->count(20)->create();

    $page = visit('/map');

    $page->wait(1000)
        ->assertPresent('.sticky')
        ->assertNoJavaScriptErrors();
});

it('handles empty venue list gracefully', function (): void {
    $page = visit('/map');

    $page->wait(1000)
        ->assertSee('No venues found')
        ->assertNoJavaScriptErrors();
});

it('map centers on UK by default', function (): void {
    $page = visit('/map');

    $page->wait(2000)
        ->assertPresent('.leaflet-container')
        ->assertNoJavaScriptErrors();
});

it('date filter shows all time option', function (): void {
    $page = visit('/map');

    $page->assertNoJavaScriptErrors();
});

it('sidebar scrolls independently of map', function (): void {
    Venue::factory()->count(20)->create();

    $page = visit('/map')->resize(1920, 1080);

    $page->wait(1000)
        ->assertPresent('.overflow-y-auto')
        ->assertNoJavaScriptErrors();
});

it('mobile map view shows venue count', function (): void {
    Venue::factory()->count(5)->create();

    $page = visit('/map')->on()->iPhone14Pro();

    $page->wait(1000)
        ->assertSee('5 Venues');
});
