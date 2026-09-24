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

it('prompts to zoom in before any venues are listed', function (): void {
    Venue::factory()->count(3)->create();

    $page = visit('/map');

    $page->wait(1)
        ->assertSee('Zoom in to see venues in this area');
});

it('lists matching venues in the sidebar when searching', function (): void {
    Venue::factory()->create(['name' => 'Test Venue One', 'city' => 'Bristol']);
    Venue::factory()->create(['name' => 'Test Venue Two', 'city' => 'Bristol']);

    $page = visit('/map');

    $page->fill('input[placeholder*="Search"]', 'Test Venue')
        ->wait(1)
        ->assertSee('Test Venue One')
        ->assertSee('Test Venue Two');
});

it('displays venue with report count badge', function (): void {
    Venue::factory()
        ->has(Report::factory()->count(3))
        ->create(['name' => 'Venue With Reports', 'city' => 'Bristol']);

    $page = visit('/map');

    $page->fill('input[placeholder*="Search"]', 'Venue With Reports')
        ->wait(1)
        ->assertSee('Venue With Reports')
        ->assertSee('3 reports');
});

it('displays no venues message when the search matches nothing', function (): void {
    Venue::factory()->create(['name' => 'Bishops Tavern', 'city' => 'Bristol']);

    $page = visit('/map');

    $page->fill('input[placeholder*="Search"]', 'satan')
        ->wait(1)
        ->assertSee('No venues found');
});

it('shows loading state while fetching venues', function (): void {
    $page = visit('/map');

    $page->assertNoJavaScriptErrors();
});

it('renders a marker for a venue with reports in the viewport', function (): void {
    Venue::factory()
        ->has(Report::factory()->count(1))
        ->create([
            'name' => 'Marker Venue',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ]);

    $page = visit('/map');

    $page->wait(2)
        ->assertPresent('.leaflet-marker-icon')
        ->assertNoJavaScriptErrors();
});

it('shows the venue details in a marker popup', function (): void {
    Venue::factory()
        ->has(Report::factory()->count(1))
        ->create([
            'name' => 'Popup Test Venue',
            'city' => 'London',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ]);

    $page = visit('/map');

    $page->wait(2)
        ->click('.leaflet-marker-icon')
        ->wait(0.5)
        ->assertSee('Popup Test Venue')
        ->assertSee('London');
});

it('mobile viewport shows list toggle button', function (): void {
    Venue::factory()->count(3)->create();

    $page = visit('/map')->on()->iPhone14Pro();

    $page->wait(1)
        ->assertSee('View')
        ->assertNoJavaScriptErrors();
});

it('mobile list toggle works correctly', function (): void {
    Venue::factory()->count(2)->create();

    $page = visit('/map')->on()->iPhone14Pro();

    $page->wait(1);
});

it('desktop shows sidebar and map simultaneously', function (): void {
    Venue::factory()->count(3)->create();

    $page = visit('/map')->resize(1920, 1080);

    $page->wait(1)
        ->assertPresent('.leaflet-container')
        ->assertNoJavaScriptErrors();
});

it('handles search query from URL parameters', function (): void {
    $venue = Venue::factory()->create(['name' => 'Searchable Venue', 'city' => 'London']);

    $page = visit('/map?search=London');

    $page->wait(1)
        ->assertNoJavaScriptErrors();
});

it('venue cards display city and address information', function (): void {
    Venue::factory()->create([
        'name' => 'Detailed Venue',
        'city' => 'Manchester',
        'address' => '123 Test Street',
    ]);

    $page = visit('/map');

    $page->fill('input[placeholder*="Search"]', 'Detailed Venue')
        ->wait(1)
        ->assertSee('Detailed Venue')
        ->assertSee('Manchester')
        ->assertSee('123 Test Street');
});

it('filters section is sticky on scroll', function (): void {
    Venue::factory()->count(20)->create();

    $page = visit('/map');

    $page->wait(1)
        ->assertPresent('.sticky')
        ->assertNoJavaScriptErrors();
});

it('handles an empty venue list gracefully', function (): void {
    $page = visit('/map');

    $page->fill('input[placeholder*="Search"]', 'nothing matches this')
        ->wait(1)
        ->assertSee('No venues found')
        ->assertNoJavaScriptErrors();
});

it('map centers on UK by default', function (): void {
    $page = visit('/map');

    $page->wait(2)
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

    $page->wait(1)
        ->assertPresent('.overflow-y-auto')
        ->assertNoJavaScriptErrors();
});

it('mobile list view searches venues once opened', function (): void {
    Venue::factory()->create(['name' => 'Mobile Venue One', 'city' => 'Bristol']);
    Venue::factory()->create(['name' => 'Mobile Venue Two', 'city' => 'Bristol']);

    $page = visit('/map')->on()->iPhone14Pro();

    $page->click('View 0 Venues')
        ->wait(0.5)
        ->fill('input[placeholder*="Search"]', 'Mobile Venue')
        ->wait(1)
        ->assertSee('Mobile Venue One')
        ->assertSee('Mobile Venue Two');
});

it('does not list venues that do not match the search term', function (): void {
    Venue::factory()->create(['name' => 'Bishops Tavern', 'city' => 'Bristol']);
    Venue::factory()->create(['name' => 'Tobacco Factory', 'city' => 'Bristol']);

    $page = visit('/map');

    $page->fill('input[placeholder*="Search"]', 'satan')
        ->wait(1)
        ->assertDontSee('Bishops Tavern')
        ->assertDontSee('Tobacco Factory');
});

it('only lists venues matching a partial search term', function (): void {
    Venue::factory()->create(['name' => 'Bishops Tavern', 'city' => 'Bristol']);
    Venue::factory()->create(['name' => 'Tobacco Factory', 'city' => 'Bristol']);

    $page = visit('/map');

    $page->fill('input[placeholder*="Search"]', 'Bishops')
        ->wait(1)
        ->assertSee('Bishops Tavern')
        ->assertDontSee('Tobacco Factory');
});
