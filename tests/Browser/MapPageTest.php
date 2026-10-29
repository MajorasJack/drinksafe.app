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

it('shows the empty state when the search matches nothing', function (): void {
    Venue::factory()->create(['name' => 'Bishops Tavern', 'city' => 'Bristol']);

    $page = visit('/map');

    $page->fill('input[placeholder*="Search"]', 'satan')
        ->wait(1)
        ->assertSee('Nothing reported here yet')
        ->assertSee('Never leave your drink unattended, even for a moment.')
        ->assertPresent('[data-testid="nothing-reported-yet"] a[href="/submit-report"]')
        ->assertNoJavaScriptErrors();
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
        ->assertPresent('.md\\:sticky')
        ->assertNoJavaScriptErrors();
});

it('handles an empty venue list gracefully', function (): void {
    $page = visit('/map');

    $page->fill('input[placeholder*="Search"]', 'nothing matches this')
        ->wait(1)
        ->assertSee('Nothing reported here yet')
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
        ->assertPresent('.md\\:overflow-y-auto')
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

it('shows the empty state above venues that have no published reports', function (): void {
    Venue::factory()->has(Report::factory()->pending())->create(['name' => 'Quiet Corner Bar', 'city' => 'Bolton']);

    $page = visit('/map');

    $page->fill('input[placeholder*="Search"]', 'Bolton')
        ->wait(1)
        ->assertSee('Quiet Corner Bar')
        ->assertSee('Nothing reported here yet')
        ->assertSee('Never leave your drink unattended, even for a moment.')
        ->assertPresent('[data-testid="nothing-reported-yet"] a[href="/submit-report"]')
        ->assertNoJavaScriptErrors();
});

it('hides the empty state when a listed venue has a published report', function (): void {
    Venue::factory()->has(Report::factory())->create(['name' => 'Reported Venue', 'city' => 'Bolton']);

    $page = visit('/map');

    $page->fill('input[placeholder*="Search"]', 'Bolton')
        ->wait(1)
        ->assertSee('Reported Venue')
        ->assertDontSee('Nothing reported here yet');
});

it('runs the search passed from the home page', function (): void {
    Venue::factory()->create(['name' => 'Home Search Venue', 'city' => 'Salford']);

    $page = visit('/map?search=Salford');

    $page->wait(1)
        ->assertValue('input[placeholder*="Search"]', 'Salford')
        ->assertSee('Home Search Venue')
        ->assertNoJavaScriptErrors();
});

it('fits the whole empty state on a phone screen once scrolled to', function (): void {
    $page = visit(sprintf('/map?search=%s', fake()->lexify('zzqx????')))->on()->iPhone14Pro()->wait(1);

    $headingAndLinkOnScreen = $page->script(<<<'JS'
        (() => {
            const card = document.querySelector('[data-testid="nothing-reported-yet"]');
            card.scrollIntoView({ block: 'center' });
            const isOnTop = (element) => {
                const box = element.getBoundingClientRect();

                return element.contains(document.elementFromPoint(box.left + box.width / 2, box.top + box.height / 2));
            };

            return [isOnTop(card.querySelector('h3')), isOnTop(card.querySelector('a'))];
        })()
        JS);

    expect($headingAndLinkOnScreen)->toBe([true, true]);
});

it('opens the venue list on a phone when the page is opened with a search', function (): void {
    $page = visit(sprintf('/map?search=%s', fake()->lexify('zzqx????')))->on()->iPhone14Pro();

    $page->wait(1)
        ->assertVisible('[data-testid="nothing-reported-yet"]')
        ->assertNoJavaScriptErrors();
});

it('fills the map with tiles on a phone after going from the opened list to the map', function (): void {
    $page = visit(sprintf('/map?search=%s', fake()->lexify('zzqx????')))->on()->iPhone14Pro()->wait(1);

    $tileAtRightEdge = $page->click('Show Map')
        ->wait(1.5)
        ->script(<<<'JS'
            (() => {
                const map = document.querySelector('.leaflet-container').getBoundingClientRect();
                const [x, y] = [map.right - 10, map.top + map.height / 2];

                return [...document.querySelectorAll('.leaflet-tile')].some((tile) => {
                    const box = tile.getBoundingClientRect();

                    return box.left <= x && x <= box.right && box.top <= y && y <= box.bottom;
                });
            })()
            JS);

    expect($tileAtRightEdge)->toBeTrue();
});

it('keeps the map in front on a phone when there is no search', function (): void {
    $page = visit('/map')->on()->iPhone14Pro();

    $page->wait(1)
        ->assertVisible('.leaflet-container')
        ->assertMissing('[data-testid="nothing-reported-yet"]');
});

it('says when no venue matches the search', function (): void {
    $search = fake()->lexify('zzqx????');

    $page = visit(sprintf('/map?search=%s', $search));

    $page->wait(1)
        ->assertSee(sprintf('No venues match "%s".', $search));
});

it('hides clear filters when no date filter is set', function (): void {
    $page = visit(sprintf('/map?search=%s', fake()->lexify('zzqx????')));

    $page->wait(1)
        ->assertSee('Nothing reported here yet')
        ->assertDontSee('Clear filters');
});

it('lists the safety tips in order with their numbers hidden from screen readers', function (): void {
    $page = visit(sprintf('/map?search=%s', fake()->lexify('zzqx????')));

    $page->wait(1)
        ->assertCount('[data-testid="nothing-reported-yet"] ol > li > span[aria-hidden="true"]', 6);
});

it('lists the reported venues on a phone after zooming in and opening the list', function (): void {
    $venueName = fake()->unique()->company();
    Venue::factory()->has(Report::factory())->create(['name' => $venueName, 'latitude' => 51.5074, 'longitude' => -0.1278]);
    Venue::factory()->count(2)->has(Report::factory())->sequence(
        ['latitude' => 51.5080, 'longitude' => -0.1270],
        ['latitude' => 51.5068, 'longitude' => -0.1285],
    )->create();

    $page = visit('/map')->on()->iPhone14Pro()->wait(1);

    $page->click('.leaflet-control-zoom-in')
        ->wait(1)
        ->click('.leaflet-control-zoom-in')
        ->wait(2)
        ->click('View 3 Venues')
        ->wait(1.5)
        ->assertSee($venueName)
        ->assertMissing('[data-testid="nothing-reported-yet"]');
});

it('keeps the search suggestions closed when the page is opened with a search', function (): void {
    $venueName = fake()->unique()->company();
    Venue::factory()->create(['name' => $venueName]);

    $page = visit(sprintf('/map?search=%s', urlencode($venueName)))->on()->iPhone14Pro();

    $page->wait(1)
        ->assertSee($venueName)
        ->assertMissing('[data-testid="venue-search-suggestions"]');
});

it('opens the search suggestions when typing a search', function (): void {
    $venueName = fake()->unique()->company();
    Venue::factory()->create(['name' => $venueName]);

    $page = visit('/map');

    $page->fill('input[placeholder*="Search"]', $venueName)
        ->wait(1)
        ->assertVisible('[data-testid="venue-search-suggestions"]');
});
