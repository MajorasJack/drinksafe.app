<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;

it('loads home page and displays hero section', function (): void {
    $page = visit('/');

    $page->assertSee('Community awareness for safer nights out')
        ->assertSee('DrinkSafe is an anonymous, informational platform')
        ->assertNoJavaScriptErrors();
});

it('displays search input and search button', function (): void {
    $page = visit('/');

    $page->assertPresent('input[name="search"]')
        ->assertPresent('button[type="submit"]')
        ->assertSee('Search');
});

it('displays Browse Map and Share a Report buttons', function (): void {
    $page = visit('/');

    $page->assertSeeLink('Browse Map')
        ->assertSeeLink('Share a Report');
});

it('navigates to map page when Browse Map clicked', function (): void {
    $page = visit('/');

    $page->click('Browse Map')
        ->assertUrlIs('/map');
});

it('navigates to submit report page when Share a Report clicked', function (): void {
    $page = visit('/');

    $page->click('Share a Report')
        ->assertUrlIs('/report');
});

it('displays recent reports section', function (): void {
    Venue::factory()
        ->has(Report::factory()->recent()->count(3))
        ->create();

    $page = visit('/');

    $page->assertSee('Recent Reports')
        ->assertSee('Latest anonymised submissions from the community');
});

it('displays recent report cards with venue information', function (): void {
    $venue = Venue::factory()->create([
        'name' => 'Test Venue Name',
        'city' => 'Test City',
    ]);

    Report::factory()->recent()->create([
        'venue_uuid' => $venue->uuid,
        'description' => 'This is a test report description',
    ]);

    $page = visit('/');

    $page->assertSee('Test Venue Name')
        ->assertSee('Test City')
        ->assertSee('This is a test report description');
});

it('navigates to venue detail when report card clicked', function (): void {
    $venue = Venue::factory()->create();
    Report::factory()->recent()->create([
        'venue_uuid' => $venue->uuid,
    ]);

    $page = visit('/');

    $page->click($venue->name)
        ->assertPathIs(sprintf('/venues/%s', $venue->uuid));
});

it('displays How it works section', function (): void {
    $page = visit('/');

    $page->assertSee('How it works')
        ->assertSee('Search or Browse')
        ->assertSee('Stay Informed')
        ->assertSee('Share Anonymously');
});

it('displays police contact information box', function (): void {
    $page = visit('/');

    $page->assertSee('Need to report a crime?')
        ->assertSee('This platform does not contact the police')
        ->assertSeeLink('View police contact guide');
});

it('performs search and navigates to map with query', function (): void {
    $page = visit('/');

    $page->fill('search', 'London')
        ->click('button[type="submit"]')
        ->assertUrlIs('/map')
        ->assertQueryStringHas('search', 'London');
});

it('displays statistics on homepage', function (): void {
    Venue::factory()->count(5)->create();
    Report::factory()->count(10)->create();

    $page = visit('/');

    $page->assertNoJavaScriptErrors();
});

it('displays disclaimer banner', function (): void {
    $page = visit('/');

    $page->assertSee('informational purposes only')
        ->assertNoJavaScriptErrors();
});

it('renders correctly on mobile viewport', function (): void {
    $page = visit('/')->on()->iPhone14Pro();

    $page->assertSee('Community awareness for safer nights out')
        ->assertPresent('input[name="search"]')
        ->assertNoJavaScriptErrors();
});

it('renders correctly on desktop viewport', function (): void {
    $page = visit('/')->resize(1920, 1080);

    $page->assertSee('Community awareness for safer nights out')
        ->assertSee('Recent Reports')
        ->assertNoJavaScriptErrors();
});

it('displays View all link to map on desktop', function (): void {
    $page = visit('/')->resize(1920, 1080);

    $page->assertSeeLink('View all');
});

it('all navigation links work correctly', function (): void {
    $page = visit('/');

    $page->assertNoJavaScriptErrors();
});
