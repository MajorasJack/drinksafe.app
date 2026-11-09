<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Services\ReportService;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReportModerationController', function (): void {
    describe('index', function (): void {
        test('guests are sent to the login page', function (): void {
            $response = $this->get(route('admin.reports.index'));

            $response->assertRedirect(route('login'));
        });

        test('users who are not admins are forbidden', function (): void {
            $this->actingAs(User::factory()->create());

            $response = $this->getJson(route('admin.reports.index'));

            $response->assertForbidden();
        });

        it('lists pending reports oldest first', function (): void {
            $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
            $newerPending = Report::factory()->pending()->create(['created_at' => now()->subHour()]);
            $olderPending = Report::factory()->pending()->create(['created_at' => now()->subDay()]);

            $response = $this->get(route('admin.reports.index'));

            $response->assertOk()->assertInertia(fn ($page) => $page
                ->component('admin/Reports')
                ->where('pending_count', 2)
                ->where('pending_reports.0.uuid', $olderPending->uuid)
                ->where('pending_reports.1.uuid', $newerPending->uuid)
            );
        });

        it('lists recent decisions separately from the pending reports', function (): void {
            $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
            $published = Report::factory()->create(['reviewed_at' => now()->subDay()]);
            $rejected = Report::factory()->rejected()->create(['reviewed_at' => now()->subHour()]);
            Report::factory()->pending()->create();

            $response = $this->get(route('admin.reports.index'));

            $response->assertOk()->assertInertia(fn ($page) => $page
                ->has('reviewed_reports', 2)
                ->where('reviewed_reports.0.uuid', $rejected->uuid)
                ->where('reviewed_reports.0.status', ReportStatus::Rejected->value)
                ->where('reviewed_reports.1.uuid', $published->uuid)
            );
        });

        it('shows the report text and venue so the admin can review them', function (): void {
            $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
            $report = Report::factory()->pending()->create();

            $response = $this->get(route('admin.reports.index'));

            $response->assertOk()->assertInertia(fn ($page) => $page
                ->where('pending_reports.0.description', $report->description)
                ->where('pending_reports.0.venue.name', $report->venue->name)
            );
        });
    });

    describe('update', function (): void {
        test('guests are unauthorised', function (): void {
            $report = Report::factory()->pending()->create();

            $response = $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Published->value]);

            $response->assertUnauthorized();
        });

        test('users who are not admins are forbidden', function (): void {
            $this->actingAs(User::factory()->create());
            $report = Report::factory()->pending()->create();

            $response = $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Published->value]);

            $response->assertForbidden();
        });

        test('users who are not admins cannot change the status', function (): void {
            $this->actingAs(User::factory()->create());
            $report = Report::factory()->pending()->create();

            $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Published->value]);

            expect($report->fresh()->status)->toBe(ReportStatus::Pending);
        });

        it('publishes a report', function (): void {
            $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
            $report = Report::factory()->pending()->create();

            $response = $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Published->value]);

            $response->assertRedirect(route('admin.reports.index'))->assertSessionHas('success', 'Report published.');
            expect($report->fresh()->status)->toBe(ReportStatus::Published);
        });

        it('rejects a report', function (): void {
            $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
            $report = Report::factory()->pending()->create();

            $response = $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Rejected->value]);

            $response->assertRedirect(route('admin.reports.index'))->assertSessionHas('success', 'Report rejected.');
            expect($report->fresh()->status)->toBe(ReportStatus::Rejected);
        });

        it('can take down a report that was already published', function (): void {
            $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
            $report = Report::factory()->create();

            $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Rejected->value]);

            expect(Report::find($report->uuid))->toBeNull();
        });

        it('refuses to move a report back to pending', function (): void {
            $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
            $report = Report::factory()->create();

            $response = $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Pending->value]);

            $response->assertUnprocessable()->assertJsonValidationErrors(['status']);
        });

        it('requires a status', function (): void {
            $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
            $report = Report::factory()->pending()->create();

            $response = $this->patchJson(route('admin.reports.update', $report->uuid), []);

            $response->assertUnprocessable()->assertJsonValidationErrors(['status']);
        });

        it('returns 404 for an unknown report', function (): void {
            $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

            $response = $this->patchJson(route('admin.reports.update', fake()->uuid()), ['status' => ReportStatus::Published->value]);

            $response->assertNotFound();
        });
    });
});

describe('ReportModerationController unpublished venues', function (): void {
    it('shows the venue name for a pending report about an unpublished venue', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $venue = Venue::factory()->unpublished()->create();
        Report::factory()->for($venue, 'venue')->pending()->create();

        $response = $this->get(route('admin.reports.index'));

        $response->assertOk()->assertInertia(fn ($page) => $page->where('pending_reports.0.venue.name', $venue->name));
    });

    it('shows the address of an unpublished venue and marks it as new', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $venue = Venue::factory()->unpublished()->create(['address' => fake()->address()]);
        Report::factory()->for($venue, 'venue')->pending()->create();

        $response = $this->get(route('admin.reports.index'));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('pending_reports.0.venue.address', $venue->address)
            ->where('pending_reports.0.venue.published_at', null)
        );
    });

    it('makes the venue page available once its first report is published', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $venue = Venue::factory()->unpublished()->create();
        $report = Report::factory()->for($venue, 'venue')->pending()->create();

        $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Published->value]);

        $this->get(route('venues.show', $venue->slug))->assertOk();
    });
});

describe('ReportModerationController safety score cache', function (): void {
    it('updates a cached safety score when a report is published', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $venue = Venue::factory()->create();
        $report = Report::factory()->for($venue, 'venue')->daysAgo(15)->pending()->create();
        $this->getJson(route('api.venues.safety-score', $venue->uuid))->assertJsonPath('data.report_count_30d', 0);

        $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Published->value]);

        $this->getJson(route('api.venues.safety-score', $venue->uuid))->assertOk()->assertJsonPath('data.report_count_30d', 1);
    });
});

describe('ReportModerationController review queue', function (): void {
    it('shows the coordinates and a default-location warning for a pending report', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $venue = Venue::factory()->unpublished()->atDefaultCoordinates()->create();
        Report::factory()->for($venue, 'venue')->pending()->create();

        $response = $this->get(route('admin.reports.index'));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('pending_reports.0.venue.latitude', ReportService::DEFAULT_LATITUDE)
            ->where('pending_reports.0.venue.longitude', ReportService::DEFAULT_LONGITUDE)
            ->where('pending_reports.0.venue.is_at_default_coordinates', true)
        );
    });

    it('does not warn about a venue away from the default location', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Report::factory()->pending()->create();

        $response = $this->get(route('admin.reports.index'));

        $response->assertOk()->assertInertia(fn ($page) => $page->where('pending_reports.0.venue.is_at_default_coordinates', false));
    });

    it('paginates pending reports', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Report::factory()->pending()->count(60)->create();

        $response = $this->get(route('admin.reports.index', ['page' => 2]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->has('pending_reports', 10)
            ->where('pending_count', 60)
            ->where('pending_pagination', ['current_page' => 2, 'last_page' => 2])
        );
    });

    it('sends a page past the end to the last page that still has reports', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Report::factory()->pending()->count(51)->create();

        $response = $this->get(route('admin.reports.index', ['page' => 3]));

        $response->assertRedirect(route('admin.reports.index', ['page' => 2]));
    });

    it('sends a page past the end to page 1 when nothing is pending', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

        $response = $this->get(route('admin.reports.index', ['page' => 2]));

        $response->assertRedirect(route('admin.reports.index', ['page' => 1]));
    });

    it('lands on the last page with reports after a review empties the current one', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Report::factory()->pending()->count(50)->create(['created_at' => now()->subDay()]);
        $lastReport = Report::factory()->pending()->create();

        $response = $this->from(route('admin.reports.index', ['page' => 2]))
            ->followingRedirects()
            ->patch(route('admin.reports.update', $lastReport->uuid), ['status' => ReportStatus::Rejected->value]);

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('pending_pagination', ['current_page' => 1, 'last_page' => 1])
            ->has('pending_reports', 50)
        );
    });

    it('shows the flags on a pending report', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $venue = Venue::factory()->atDefaultCoordinates()->create();
        Report::factory()->for($venue, 'venue')->pending()->create(['description' => 'Call me on [PHONE REMOVED].']);

        $response = $this->get(route('admin.reports.index'));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('pending_reports.0.flags', ['personal_details_removed', 'default_coordinates'])
        );
    });

    it('offers to hide a venue when its last published report is taken down', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Report::factory()->create(['reviewed_at' => now()]);

        $response = $this->get(route('admin.reports.index'));

        $response->assertOk()->assertInertia(fn ($page) => $page->where('reviewed_reports.0.would_empty_venue', true));
    });

    it('does not offer to hide a venue that still has published reports', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $venue = Venue::factory()->create();
        Report::factory()->for($venue, 'venue')->count(2)->create(['reviewed_at' => now()]);

        $response = $this->get(route('admin.reports.index'));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('reviewed_reports.0.would_empty_venue', false)
            ->where('reviewed_reports.1.would_empty_venue', false)
        );
    });

    it('lists pending reports and decisions in a fixed number of queries', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Report::factory()->pending()->count(2)->create();
        Report::factory()->count(2)->create(['reviewed_at' => now()]);
        DB::enableQueryLog();
        $this->get(route('admin.reports.index'))->assertOk();
        $queriesForFewReports = count(DB::getQueryLog());
        Report::factory()->pending()->count(10)->create();
        Report::factory()->count(10)->create(['reviewed_at' => now()]);
        DB::flushQueryLog();

        $this->get(route('admin.reports.index'))->assertOk();

        expect(DB::getQueryLog())->toHaveCount($queriesForFewReports);
    });
});

describe('ReportModerationController takedowns', function (): void {
    it('hides the venue when asked during a takedown', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $venue = Venue::factory()->create();
        $report = Report::factory()->for($venue, 'venue')->create();

        $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Rejected->value, 'hide_venue' => true]);

        expect(Venue::withoutGlobalScopes()->find($venue->uuid))
            ->published_at->toBeNull()
            ->unpublished_at->not->toBeNull();
    });

    it('keeps the venue when not asked during a takedown', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $venue = Venue::factory()->create();
        $report = Report::factory()->for($venue, 'venue')->create();

        $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Rejected->value, 'hide_venue' => false]);

        expect(Venue::find($venue->uuid))->not->toBeNull();
    });

    it('keeps a venue that still has published reports even when asked to hide it', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $venue = Venue::factory()->create();
        [$report] = Report::factory()->for($venue, 'venue')->count(2)->create()->all();

        $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Rejected->value, 'hide_venue' => true]);

        expect(Venue::find($venue->uuid))->not->toBeNull();
    });

    it('refuses to publish a report whose venue the admin unpublished', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $venue = Venue::factory()->unpublishedByAdmin()->create();
        $report = Report::factory()->for($venue, 'venue')->rejected()->create();

        $response = $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Published->value]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['status' => 'This venue is unpublished. Publish the venue first.']);
        expect(Report::withoutGlobalScopes()->find($report->uuid)->status)->toBe(ReportStatus::Rejected);
    });
});

describe('ReportModerationController bulk', function (): void {
    test('guests are unauthorised', function (): void {
        $report = Report::factory()->pending()->create();

        $response = $this->postJson(route('admin.reports.bulk'), ['uuids' => [$report->uuid], 'status' => ReportStatus::Published->value]);

        $response->assertUnauthorized();
    });

    test('users who are not admins are forbidden', function (): void {
        $this->actingAs(User::factory()->create());
        $report = Report::factory()->pending()->create();

        $response = $this->postJson(route('admin.reports.bulk'), ['uuids' => [$report->uuid], 'status' => ReportStatus::Published->value]);

        $response->assertForbidden();
    });

    it('publishes and rejects several pending reports at once', function (string $status, string $message): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $reports = Report::factory()->pending()->count(3)->create();

        $response = $this->from(route('admin.reports.index'))
            ->postJson(route('admin.reports.bulk'), ['uuids' => $reports->pluck('uuid')->all(), 'status' => $status]);

        $response->assertRedirect(route('admin.reports.index'))->assertSessionHas('status', $message);
        expect(Report::withoutGlobalScopes()->whereKey($reports->modelKeys())->pluck('status')->unique()->all())
            ->toBe([ReportStatus::from($status)]);
    })->with([
        'publish' => [ReportStatus::Published->value, '3 reports published'],
        'reject' => [ReportStatus::Rejected->value, '3 reports rejected'],
    ]);

    it('skips reports that are no longer pending in a bulk review', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $pending = Report::factory()->pending()->count(2)->create();
        $published = Report::factory()->create();

        $response = $this->postJson(route('admin.reports.bulk'), [
            'uuids' => [...$pending->pluck('uuid')->all(), $published->uuid, fake()->uuid()],
            'status' => ReportStatus::Rejected->value,
        ]);

        $response->assertRedirect()->assertSessionHas('status', '2 reports rejected. 2 skipped because they are no longer pending.');
        expect($published->fresh()->status)->toBe(ReportStatus::Published)
            ->and(Report::withoutGlobalScopes()->whereKey($pending->modelKeys())->pluck('status')->unique()->all())->toBe([ReportStatus::Rejected]);
    });

    it('skips reports whose venue the admin unpublished in a bulk publish', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $blocked = Report::factory()->for(Venue::factory()->unpublishedByAdmin(), 'venue')->pending()->create();
        $allowed = Report::factory()->pending()->create();

        $response = $this->postJson(route('admin.reports.bulk'), [
            'uuids' => [$blocked->uuid, $allowed->uuid],
            'status' => ReportStatus::Published->value,
        ]);

        $response->assertRedirect()->assertSessionHas('status', '1 report published. 1 skipped because its venue is unpublished. Publish the venue first.');
        expect(Report::withoutGlobalScopes()->find($blocked->uuid)->status)->toBe(ReportStatus::Pending);
    });

    it('says how many reports a mixed bulk publish skipped and why', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $allowed = Report::factory()->pending()->count(3)->create();
        $blocked = Report::factory()->for(Venue::factory()->unpublishedByAdmin(), 'venue')->pending()->count(2)->create();
        $alreadyRejected = Report::factory()->rejected()->create();

        $response = $this->postJson(route('admin.reports.bulk'), [
            'uuids' => [...$allowed->pluck('uuid')->all(), ...$blocked->pluck('uuid')->all(), $alreadyRejected->uuid],
            'status' => ReportStatus::Published->value,
        ]);

        $response->assertRedirect()->assertSessionHas(
            'status',
            '3 reports published. 2 skipped because their venue is unpublished. Publish the venue first. 1 skipped because it is no longer pending.',
        );
    });

    it('hides the venues it was asked to when a bulk reject leaves them empty', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $hiddenVenue = Venue::factory()->create();
        $keptVenue = Venue::factory()->create();
        $reports = collect([
            Report::factory()->for($hiddenVenue, 'venue')->pending()->create(),
            Report::factory()->for($keptVenue, 'venue')->pending()->create(),
        ]);

        $this->postJson(route('admin.reports.bulk'), [
            'uuids' => $reports->pluck('uuid')->all(),
            'status' => ReportStatus::Rejected->value,
            'hide_venues' => [$hiddenVenue->uuid],
        ]);

        expect(Venue::find($hiddenVenue->uuid))->toBeNull()
            ->and(Venue::find($keptVenue->uuid))->not->toBeNull();
    });

    it('returns to the page the bulk review was made from', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $report = Report::factory()->pending()->create();

        $response = $this->from(route('admin.reports.index', ['page' => 2]))
            ->postJson(route('admin.reports.bulk'), ['uuids' => [$report->uuid], 'status' => ReportStatus::Rejected->value]);

        $response->assertRedirect(route('admin.reports.index', ['page' => 2]));
    });

    it('refuses to move reports back to pending in bulk', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $report = Report::factory()->rejected()->create();

        $response = $this->postJson(route('admin.reports.bulk'), ['uuids' => [$report->uuid], 'status' => ReportStatus::Pending->value]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['status']);
    });

    it('requires a list of report UUIDs', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

        $response = $this->postJson(route('admin.reports.bulk'), ['uuids' => [fake()->word()], 'status' => ReportStatus::Rejected->value]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['uuids.0']);
    });
});
