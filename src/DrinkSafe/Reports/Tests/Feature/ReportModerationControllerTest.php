<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            $this->actingAs(User::factory()->admin()->create());
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
            $this->actingAs(User::factory()->admin()->create());
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
            $this->actingAs(User::factory()->admin()->create());
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
            $this->actingAs(User::factory()->admin()->create());
            $report = Report::factory()->pending()->create();

            $response = $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Published->value]);

            $response->assertRedirect(route('admin.reports.index'))->assertSessionHas('success', 'Report published.');
            expect($report->fresh()->status)->toBe(ReportStatus::Published);
        });

        it('rejects a report', function (): void {
            $this->actingAs(User::factory()->admin()->create());
            $report = Report::factory()->pending()->create();

            $response = $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Rejected->value]);

            $response->assertRedirect(route('admin.reports.index'))->assertSessionHas('success', 'Report rejected.');
            expect($report->fresh()->status)->toBe(ReportStatus::Rejected);
        });

        it('can take down a report that was already published', function (): void {
            $this->actingAs(User::factory()->admin()->create());
            $report = Report::factory()->create();

            $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Rejected->value]);

            expect(Report::find($report->uuid))->toBeNull();
        });

        it('refuses to move a report back to pending', function (): void {
            $this->actingAs(User::factory()->admin()->create());
            $report = Report::factory()->create();

            $response = $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Pending->value]);

            $response->assertUnprocessable()->assertJsonValidationErrors(['status']);
        });

        it('requires a status', function (): void {
            $this->actingAs(User::factory()->admin()->create());
            $report = Report::factory()->pending()->create();

            $response = $this->patchJson(route('admin.reports.update', $report->uuid), []);

            $response->assertUnprocessable()->assertJsonValidationErrors(['status']);
        });

        it('returns 404 for an unknown report', function (): void {
            $this->actingAs(User::factory()->admin()->create());

            $response = $this->patchJson(route('admin.reports.update', fake()->uuid()), ['status' => ReportStatus::Published->value]);

            $response->assertNotFound();
        });
    });
});

describe('ReportModerationController unpublished venues', function (): void {
    it('shows the venue name for a pending report about an unpublished venue', function (): void {
        $this->actingAs(User::factory()->admin()->create());
        $venue = Venue::factory()->unpublished()->create();
        Report::factory()->for($venue, 'venue')->pending()->create();

        $response = $this->get(route('admin.reports.index'));

        $response->assertOk()->assertInertia(fn ($page) => $page->where('pending_reports.0.venue.name', $venue->name));
    });

    it('makes the venue page available once its first report is published', function (): void {
        $this->actingAs(User::factory()->admin()->create());
        $venue = Venue::factory()->unpublished()->create();
        $report = Report::factory()->for($venue, 'venue')->pending()->create();

        $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Published->value]);

        $this->get(route('venues.show', $venue->slug))->assertOk();
    });
});

describe('ReportModerationController safety score cache', function (): void {
    it('updates a cached safety score when a report is published', function (): void {
        $this->actingAs(User::factory()->admin()->create());
        $venue = Venue::factory()->create();
        $report = Report::factory()->for($venue, 'venue')->daysAgo(15)->pending()->create();
        $this->getJson(route('api.venues.safety-score', $venue->uuid))->assertJsonPath('data.report_count_30d', 0);

        $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Published->value]);

        $this->getJson(route('api.venues.safety-score', $venue->uuid))->assertOk()->assertJsonPath('data.report_count_30d', 1);
    });
});
