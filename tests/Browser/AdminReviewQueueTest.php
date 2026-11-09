<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;

it('asks whether to hide the venue before taking down its last report', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    $venue = Venue::factory()->create();
    Report::factory()->for($venue, 'venue')->create(['reviewed_at' => now()]);

    $page = visit('/admin/reports');

    $page->click('Take down')
        ->assertSee('Keep the venue listed')
        ->click('Hide the venue too')
        ->wait(1)
        ->assertNoJavaScriptErrors();
    expect(Venue::find($venue->uuid))->toBeNull();
});

it('takes down a report without asking about a venue that keeps other published reports', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    $venue = Venue::factory()->create();
    Report::factory()->for($venue, 'venue')->count(2)->create(['reviewed_at' => now()]);

    $page = visit('/admin/reports');

    $page->click('Take down')
        ->assertDontSee('Hide the venue too')
        ->assertSee('It comes off the site straight away.')
        ->assertNoJavaScriptErrors();
});

it('shows a map and a warning for a pending report at the default location', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    Report::factory()->for(Venue::factory()->unpublished()->atDefaultCoordinates(), 'venue')->pending()->create();

    $page = visit('/admin/reports');

    $page->wait(2)
        ->assertPresent('[data-test="venue-mini-map"] .leaflet-marker-icon')
        ->assertSee('Location not given')
        ->assertNoJavaScriptErrors();
});

it('publishes the selected pending reports together', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    $reports = Report::factory()->pending()->count(2)->create();

    $page = visit('/admin/reports');

    $page->click('Select all on this page')
        ->click('Publish selected')
        ->wait(1)
        ->assertSee('No reports are waiting for review.')
        ->assertNoJavaScriptErrors();
    expect(Report::whereKey($reports->modelKeys())->count())->toBe(2);
});

it('shows one toast after a bulk review', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    Report::factory()->pending()->count(2)->create();

    $page = visit('/admin/reports');

    $page->click('Select all on this page')
        ->click('Publish selected')
        ->wait(1)
        ->assertSee('2 reports published')
        ->assertScript("document.querySelectorAll('[data-sonner-toast]').length", 1)
        ->assertNoJavaScriptErrors();
});
