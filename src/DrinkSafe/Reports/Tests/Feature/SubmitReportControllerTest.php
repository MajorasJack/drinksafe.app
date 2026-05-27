<?php

declare(strict_types=1);

use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        it('renders submit report page with venues', function (): void {
            Venue::factory()->count(3)->create();

            $response = $this->get(route('reports.create'));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('SubmitReport')
                ->has('venues', 3)
                ->has('venues.0.uuid')
                ->has('venues.0.name')
            );
        });

        it('shows empty venues array when no venues exist', function (): void {
            $response = $this->get(route('reports.create'));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('SubmitReport')
                ->has('venues', 0)
            );
        });
    });

    describe('store', function (): void {
        it('submits report and redirects to venue detail', function (): void {
            $venue = Venue::factory()->create();
            $data = [
                'venue_uuid' => $venue->uuid,
                'incident_date' => now()->subDays(2)->toDateString(),
                'time_of_day' => 'Evening',
                'description' => fake()->paragraph(),
            ];

            $response = $this->post(route('reports.store'), $data);

            $response->assertRedirect(route('venues.show', $venue->uuid));
            $response->assertSessionHas('success', 'Report submitted successfully');

            $this->assertDatabaseHas('reports', [
                'venue_uuid' => $venue->uuid,
                'time_of_day' => 'Evening',
            ]);
        });

        it('validates report submission data', function (): void {
            $response = $this->post(route('reports.store'), []);

            $response->assertSessionHasErrors(['incident_date', 'time_of_day', 'description']);
        });

        it('redirects back with error when venue not found', function (): void {
            $data = [
                'venue_uuid' => 'non-existent-uuid',
                'incident_date' => now()->toDateString(),
                'time_of_day' => 'Morning',
                'description' => fake()->paragraph(),
            ];

            $response = $this->post(route('reports.store'), $data);

            $response->assertSessionHasErrors(['venue_uuid']);
        });

        it('stores report with valid incident date', function (): void {
            $venue = Venue::factory()->create();
            $incidentDate = now()->subDays(5);
            $data = [
                'venue_uuid' => $venue->uuid,
                'incident_date' => $incidentDate->toDateString(),
                'time_of_day' => 'Night',
                'description' => fake()->paragraph(),
            ];

            $response = $this->post(route('reports.store'), $data);

            $response->assertRedirect(route('venues.show', $venue->uuid));

            $this->assertDatabaseHas('reports', [
                'venue_uuid' => $venue->uuid,
            ]);

            $report = $venue->fresh()->reports->first();
            expect($report->incident_date->toDateString())->toBe($incidentDate->toDateString());
        });

        it('validates time of day is valid enum value', function (): void {
            $venue = Venue::factory()->create();
            $data = [
                'venue_uuid' => $venue->uuid,
                'incident_date' => now()->toDateString(),
                'time_of_day' => 'InvalidTime',
                'description' => fake()->paragraph(),
            ];

            $response = $this->post(route('reports.store'), $data);

            $response->assertSessionHasErrors(['time_of_day']);
        });

        it('requires description field', function (): void {
            $venue = Venue::factory()->create();
            $data = [
                'venue_uuid' => $venue->uuid,
                'incident_date' => now()->toDateString(),
                'time_of_day' => 'Afternoon',
            ];

            $response = $this->post(route('reports.store'), $data);

            $response->assertSessionHasErrors(['description']);
        });
    });
});
