<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;

it('publishes a rejected report from the report list', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    $report = Report::factory()->rejected()->create(['reviewed_at' => now()]);

    $page = visit('/admin/reports/all?status=rejected');

    $page->click('Publish')
        ->wait(1)
        ->assertSee('No reports match these filters.')
        ->assertNoJavaScriptErrors();
    expect($report->fresh()->status)->toBe(ReportStatus::Published);
});

it('asks whether to hide the venue before taking down its last report from the report list', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    $venue = Venue::factory()->create();
    Report::factory()->for($venue, 'venue')->create(['reviewed_at' => now()]);

    $page = visit('/admin/reports/all');

    $page->click('Take down')
        ->assertSee('Keep the venue listed')
        ->click('Keep the venue listed')
        ->wait(1)
        ->assertSee('Rejected')
        ->assertNoJavaScriptErrors();
    expect(Venue::find($venue->uuid))->not->toBeNull();
});

it('disables Publish on a rejected report whose venue the admin unpublished', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    Report::factory()->for(Venue::factory()->unpublishedByAdmin(), 'venue')->rejected()->create();

    $page = visit('/admin/reports/all');

    $page->assertSee('This venue is unpublished. Publish the venue first.')
        ->assertButtonDisabled('Publish')
        ->assertNoJavaScriptErrors();
});
