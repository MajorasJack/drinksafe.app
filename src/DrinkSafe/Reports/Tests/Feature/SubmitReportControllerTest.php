<?php

declare(strict_types=1);

use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Scopes\PublishedScope;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('SubmitReportController', function (): void {
    describe('create', function (): void {
        it('renders submit report page successfully', function (): void {
            $response = $this->get(route('reports.create'));

            $response->assertOk();
        });

        it('shows submit report page with Inertia component', function (): void {
            $response = $this->get(route('reports.create'));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('SubmitReport')
            );
        });

        it('preselects the venue named in the query string', function (): void {
            $venue = Venue::factory()->create();

            $response = $this->get(route('reports.create', ['venue' => $venue->uuid]));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('SubmitReport')
                ->where('venue.uuid', $venue->uuid)
            );
        });

        it('renders without a venue when the query string names an unknown one', function (): void {
            $response = $this->get(route('reports.create', ['venue' => 'not-a-venue']));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('SubmitReport')
                ->where('venue', null)
            );
        });

        it('shares the configured Turnstile site key with the page', function (): void {
            config(['services.turnstile.key' => 'site-key-123']);

            $response = $this->get(route('reports.create'));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('SubmitReport')
                ->where('turnstile.siteKey', 'site-key-123')
            );
        });

        it('does not ship the full venue list as a page prop', function (): void {
            Venue::factory()->count(3)->create();

            $response = $this->get(route('reports.create'));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('SubmitReport')
                ->missing('venues')
            );
        });
    });

    describe('store', function (): void {
        it('submits report and redirects to the home page', function (): void {
            Turnstile::fake();
            $venue = Venue::factory()->create();
            $data = [
                'venue_uuid' => $venue->uuid,
                'incident_date' => now()->subDays(2)->toDateString(),
                'time_of_day' => 'Evening',
                'description' => fake()->paragraph(),
                'cf-turnstile-response' => 'test-token',
            ];

            $response = $this->post(route('reports.store'), $data);

            $response->assertRedirect(route('home'));
            $response->assertSessionHas('success', 'Thank you. Your report will appear once it has been reviewed.');

            $this->assertDatabaseHas('reports', [
                'venue_uuid' => $venue->uuid,
                'time_of_day' => 'Evening',
            ]);
        });

        it('saves a submitted report as pending', function (): void {
            Turnstile::fake();
            $venue = Venue::factory()->create();

            $this->post(route('reports.store'), [
                'venue_uuid' => $venue->uuid,
                'incident_date' => now()->subDays(2)->toDateString(),
                'time_of_day' => 'Evening',
                'description' => fake()->paragraph(),
                'cf-turnstile-response' => 'test-token',
            ]);

            $this->assertDatabaseHas('reports', [
                'venue_uuid' => $venue->uuid,
                'status' => ReportStatus::Pending->value,
                'reviewed_at' => null,
            ]);
        });

        it('ignores a status sent with the submission', function (): void {
            Turnstile::fake();
            $venue = Venue::factory()->create();

            $this->post(route('reports.store'), [
                'venue_uuid' => $venue->uuid,
                'incident_date' => now()->subDays(2)->toDateString(),
                'time_of_day' => 'Evening',
                'description' => fake()->paragraph(),
                'status' => ReportStatus::Published->value,
                'cf-turnstile-response' => 'test-token',
            ]);

            $this->assertDatabaseHas('reports', [
                'venue_uuid' => $venue->uuid,
                'status' => ReportStatus::Pending->value,
            ]);
        });

        it('validates report submission data', function (): void {
            $response = $this->post(route('reports.store'), []);

            $response->assertSessionHasErrors(['incident_date', 'time_of_day', 'description']);
        });

        it('redirects back with error when venue not found', function (): void {
            Turnstile::fake();
            $data = [
                'venue_uuid' => 'non-existent-uuid',
                'incident_date' => now()->toDateString(),
                'time_of_day' => 'Morning',
                'description' => fake()->paragraph(),
                'cf-turnstile-response' => 'test-token',
            ];

            $response = $this->post(route('reports.store'), $data);

            $response->assertSessionHasErrors(['venue_uuid']);
        });

        it('stores report with valid incident date', function (): void {
            Turnstile::fake();
            $venue = Venue::factory()->create();
            $incidentDate = now()->subDays(5);
            $data = [
                'venue_uuid' => $venue->uuid,
                'incident_date' => $incidentDate->toDateString(),
                'time_of_day' => 'Night',
                'description' => fake()->paragraph(),
                'cf-turnstile-response' => 'test-token',
            ];

            $response = $this->post(route('reports.store'), $data);

            $response->assertRedirect(route('home'));

            $this->assertDatabaseHas('reports', [
                'venue_uuid' => $venue->uuid,
            ]);

            $report = Report::withoutGlobalScope(PublishedScope::class)->where('venue_uuid', $venue->uuid)->first();
            expect($report->incident_date->toDateString())->toBe($incidentDate->toDateString());
        });

        it('validates time of day is valid enum value', function (): void {
            Turnstile::fake();
            $venue = Venue::factory()->create();
            $data = [
                'venue_uuid' => $venue->uuid,
                'incident_date' => now()->toDateString(),
                'time_of_day' => 'InvalidTime',
                'description' => fake()->paragraph(),
                'cf-turnstile-response' => 'test-token',
            ];

            $response = $this->post(route('reports.store'), $data);

            $response->assertSessionHasErrors(['time_of_day']);
        });

        it('requires description field', function (): void {
            Turnstile::fake();
            $venue = Venue::factory()->create();
            $data = [
                'venue_uuid' => $venue->uuid,
                'incident_date' => now()->toDateString(),
                'time_of_day' => 'Afternoon',
                'cf-turnstile-response' => 'test-token',
            ];

            $response = $this->post(route('reports.store'), $data);

            $response->assertSessionHasErrors(['description']);
        });
    });
});
