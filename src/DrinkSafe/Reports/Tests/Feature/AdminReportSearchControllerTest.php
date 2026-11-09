<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('AdminReportSearchController', function (): void {
    test('guests are sent to the login page', function (): void {
        $response = $this->get(route('admin.reports.search'));

        $response->assertRedirect(route('login'));
    });

    test('users who are not admins are forbidden', function (): void {
        $this->actingAs(User::factory()->create());

        $response = $this->getJson(route('admin.reports.search'));

        $response->assertForbidden();
    });

    it('lists reports of every status, newest first', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $rejected = Report::factory()->rejected()->create(['created_at' => now()->subDays(3)]);
        $published = Report::factory()->create(['created_at' => now()->subDays(2)]);
        $pending = Report::factory()->pending()->create(['created_at' => now()->subDay()]);

        $response = $this->get(route('admin.reports.search'));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('admin/ReportSearch')
            ->where('total', 3)
            ->where('reports.0.uuid', $pending->uuid)
            ->where('reports.1.uuid', $published->uuid)
            ->where('reports.2.uuid', $rejected->uuid)
        );
    });

    it('shows 25 reports a page', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Report::factory()->count(26)->create();

        $response = $this->get(route('admin.reports.search', ['page' => 2]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->has('reports', 1)
            ->where('pagination', ['current_page' => 2, 'last_page' => 2])
        );
    });

    it('filters by status', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $rejected = Report::factory()->rejected()->create();
        Report::factory()->create();
        Report::factory()->pending()->create();

        $response = $this->get(route('admin.reports.search', ['status' => ReportStatus::Rejected->value]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->has('reports', 1)
            ->where('reports.0.uuid', $rejected->uuid)
        );
    });

    it('filters by city', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $city = fake()->unique()->city();
        $inCity = Report::factory()->for(Venue::factory()->state(['city' => $city]), 'venue')->create();
        Report::factory()->for(Venue::factory()->state(['city' => fake()->unique()->city()]), 'venue')->create();

        $response = $this->get(route('admin.reports.search', ['city' => $city]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->has('reports', 1)
            ->where('reports.0.uuid', $inCity->uuid)
        );
    });

    it('filters by venue name', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $venueName = sprintf('%s Tavern', fake()->unique()->lastName());
        $atVenue = Report::factory()->for(Venue::factory()->unpublished()->state(['name' => $venueName]), 'venue')->pending()->create();
        Report::factory()->for(Venue::factory()->state(['name' => sprintf('%s Lounge', fake()->unique()->lastName())]), 'venue')->create();

        $response = $this->get(route('admin.reports.search', ['venue' => mb_strtolower($venueName)]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->has('reports', 1)
            ->where('reports.0.uuid', $atVenue->uuid)
        );
    });

    it('filters by incident date range, including both ends', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $from = now()->subDays(10);
        $to = now()->subDays(5);
        $onFirstDay = Report::factory()->create(['incident_date' => $from->toDateString(), 'created_at' => now()->subHour()]);
        $onLastDay = Report::factory()->create(['incident_date' => $to->toDateString(), 'created_at' => now()->subHours(2)]);
        Report::factory()->create(['incident_date' => $from->copy()->subDay()->toDateString()]);
        Report::factory()->create(['incident_date' => $to->copy()->addDay()->toDateString()]);

        $response = $this->get(route('admin.reports.search', ['from' => $from->toDateString(), 'to' => $to->toDateString()]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->has('reports', 2)
            ->where('reports.0.uuid', $onFirstDay->uuid)
            ->where('reports.1.uuid', $onLastDay->uuid)
        );
    });

    it('filters by description text', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $searchTerm = fake()->unique()->lexify('zq??????');
        $matching = Report::factory()->create(['description' => sprintf('%s %s %s', fake()->sentence(), $searchTerm, fake()->sentence())]);
        Report::factory()->create(['description' => fake()->paragraph()]);

        $response = $this->get(route('admin.reports.search', ['q' => $searchTerm]));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->has('reports', 1)
            ->where('reports.0.uuid', $matching->uuid)
        );
    });

    it('treats percent and underscore in the description search literally', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $literal = Report::factory()->create(['description' => sprintf('%s Drinks were 50%%_off all night.', fake()->sentence())]);
        Report::factory()->create(['description' => sprintf('%s Drinks were 50 percent-off all night.', fake()->sentence())]);

        $response = $this->get(route('admin.reports.search', ['q' => '50%_off']));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->has('reports', 1)
            ->where('reports.0.uuid', $literal->uuid)
        );
    });

    it('rejects a date range that ends before it starts', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

        $response = $this->getJson(route('admin.reports.search', [
            'from' => now()->subDays(2)->toDateString(),
            'to' => now()->subDays(3)->toDateString(),
        ]));

        $response->assertUnprocessable()->assertJsonValidationErrors(['to' => 'The end date must be on or after the start date.']);
    });

    it('rejects a status that does not exist', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

        $response = $this->getJson(route('admin.reports.search', ['status' => fake()->word()]));

        $response->assertUnprocessable()->assertJsonValidationErrors('status');
    });

    it('offers to hide a venue when its last published report is taken down', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Report::factory()->create();

        $response = $this->get(route('admin.reports.search'));

        $response->assertOk()->assertInertia(fn ($page) => $page->where('reports.0.would_empty_venue', true));
    });

    it('lists reports in a fixed number of queries', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Report::factory()->count(2)->create();
        DB::enableQueryLog();
        $this->get(route('admin.reports.search'))->assertOk();
        $queriesForFewReports = count(DB::getQueryLog());
        Report::factory()->count(10)->create();
        Report::factory()->rejected()->count(10)->create();
        DB::flushQueryLog();

        $this->get(route('admin.reports.search'))->assertOk();

        expect(DB::getQueryLog())->toHaveCount($queriesForFewReports);
    });

    it('sends a page past the end to the last page, keeping the filters', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Report::factory()->rejected()->create();

        $response = $this->get(route('admin.reports.search', ['status' => ReportStatus::Rejected->value, 'page' => 3]));

        $response->assertRedirect(route('admin.reports.search', ['status' => ReportStatus::Rejected->value, 'page' => 1]));
    });

    it('returns to the filtered list after a takedown', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        $report = Report::factory()->create();
        $searchUrl = route('admin.reports.search', ['status' => ReportStatus::Published->value, 'q' => fake()->word()]);

        $response = $this->from($searchUrl)->patch(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Rejected->value]);

        $response->assertRedirect($searchUrl);
    });
});
