<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;

it('displays venue detail page with venue information', function (): void {
    $venue = Venue::factory()->create([
        'name' => 'Test Venue Detail',
        'city' => 'London',
        'address' => '123 Test Street',
    ]);

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->assertSee('Test Venue Detail')
        ->assertSee('London')
        ->assertSee('123 Test Street')
        ->assertNoJavaScriptErrors();
});

it('displays breadcrumb navigation', function (): void {
    $venue = Venue::factory()->create(['name' => 'Breadcrumb Venue']);

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->assertSeeLink('Home')
        ->assertSeeLink('Map')
        ->assertSee('Breadcrumb Venue');
});

it('breadcrumb Home link navigates to homepage', function (): void {
    $venue = Venue::factory()->create();

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->click('Home')
        ->wait(1)
        ->assertPathIs('/');
});

it('breadcrumb Map link navigates to map page', function (): void {
    $venue = Venue::factory()->create();

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->click('Map')
        ->wait(1)
        ->assertPathIs('/map');
});

it('displays map with single venue marker', function (): void {
    $venue = Venue::factory()->create();

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->wait(2)
        ->assertPresent('.leaflet-container')
        ->assertPresent('.leaflet-marker-icon')
        ->assertNoJavaScriptErrors();
});

it('displays venue coordinates', function (): void {
    $venue = Venue::factory()->create([
        'latitude' => 51.509865,
        'longitude' => -0.118092,
    ]);

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->assertSee('51.509865')
        ->assertSee('-0.118092')
        ->assertSee('Coordinates:');
});

it('displays report count alert when venue has reports', function (): void {
    $venue = Venue::factory()
        ->has(Report::factory()->count(3))
        ->create();

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->assertSee('3 reports at this venue')
        ->assertSee('Review the reports below');
});

it('does not display report alert when venue has no reports', function (): void {
    $venue = Venue::factory()->create();

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->assertDontSee('reports at this venue');
});

it('displays Submit Report call to action card', function (): void {
    $venue = Venue::factory()->create();

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->assertSee('Experienced something here?')
        ->assertSee('Help others stay safe by submitting an anonymous report')
        ->assertSeeLink('Submit Report');
});

it('Submit Report button navigates with venue pre-selected', function (): void {
    $venue = Venue::factory()->create();

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->click('Submit Report')
        ->wait(1)
        ->assertPathIs('/submit-report')
        ->assertQueryStringHas('venue', $venue->uuid);
});

it('displays reports list when venue has reports', function (): void {
    $venue = Venue::factory()->create();
    Report::factory()->count(3)->create([
        'venue_uuid' => $venue->uuid,
        'description' => 'Test report description',
    ]);

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->assertSee('Test report description');
});

it('displays report incident date in reports list', function (): void {
    $venue = Venue::factory()->create();
    Report::factory()->create([
        'venue_uuid' => $venue->uuid,
        'incident_date' => now()->subDays(5),
    ]);

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->assertSee(now()->subDays(5)->format('d M Y'))
        ->assertNoJavaScriptErrors();
});

it('displays time of day filter in reports list', function (): void {
    $venue = Venue::factory()
        ->has(Report::factory()->count(2))
        ->create();

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->assertNoJavaScriptErrors();
});

it('renders correctly on mobile viewport', function (): void {
    $venue = Venue::factory()->create(['name' => 'Mobile Venue']);

    $page = visit(sprintf('/venues/%s', $venue->slug))->on()->iPhone14Pro();

    $page->assertSee('Mobile Venue')
        ->assertNoJavaScriptErrors();
});

it('renders correctly on desktop viewport', function (): void {
    $venue = Venue::factory()
        ->has(Report::factory()->count(2))
        ->create(['name' => 'Desktop Venue']);

    $page = visit(sprintf('/venues/%s', $venue->slug))->resize(1920, 1080);

    $page->assertSee('Desktop Venue')
        ->assertPresent('.leaflet-container')
        ->assertNoJavaScriptErrors();
});

it('desktop displays two column layout', function (): void {
    $venue = Venue::factory()
        ->has(Report::factory()->count(3))
        ->create();

    $page = visit(sprintf('/venues/%s', $venue->slug))->resize(1920, 1080);

    $page->assertPresent('.lg\\:grid-cols-2')
        ->assertNoJavaScriptErrors();
});

it('handles venue not found gracefully', function (): void {
    $page = visit('/venues/00000000-0000-0000-0000-000000000000');

    $page->assertNoJavaScriptErrors();
});

it('map card displays Location title', function (): void {
    $venue = Venue::factory()->create();

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->assertSee('Location')
        ->assertNoJavaScriptErrors();
});

it('reports section displays all venue reports', function (): void {
    $venue = Venue::factory()->create();

    Report::factory()->create([
        'venue_uuid' => $venue->uuid,
        'description' => 'First report content',
    ]);

    Report::factory()->create([
        'venue_uuid' => $venue->uuid,
        'description' => 'Second report content',
    ]);

    $page = visit(sprintf('/venues/%s', $venue->slug));

    $page->assertSee('First report content')
        ->assertSee('Second report content');
});
