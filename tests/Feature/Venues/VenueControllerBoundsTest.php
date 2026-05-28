<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;

describe('VenueController bounds filtering', function (): void {
    describe('index with bounds', function (): void {
        it('returns venues within specified bounds', function (): void {
            $venueInBounds = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->for($venueInBounds, 'venue')->create();

            $venueOutOfBounds = Venue::factory()->create([
                'latitude' => 53.5,
                'longitude' => -2.2,
            ]);
            Report::factory()->for($venueOutOfBounds, 'venue')->create();

            $response = $this->getJson('/api/venues?bounds=51.0,-0.5,52.0,0.5');

            $response->assertOk();
            $response->assertJsonCount(1, 'data');
        });

        it('returns empty when no venues exist within bounds', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 53.5,
                'longitude' => -2.2,
            ]);
            Report::factory()->for($venue, 'venue')->create();

            $response = $this->getJson('/api/venues?bounds=51.0,-0.5,52.0,0.5');

            $response->assertOk();
            $response->assertJsonCount(0, 'data');
        });

        it('returns multiple venues within bounds', function (): void {
            Venue::factory()->count(5)->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ])->each(fn (Venue $venue) => Report::factory()->for($venue, 'venue')->create());

            Venue::factory()->count(3)->create([
                'latitude' => 53.5,
                'longitude' => -2.2,
            ])->each(fn (Venue $venue) => Report::factory()->for($venue, 'venue')->create());

            $response = $this->getJson('/api/venues?bounds=51.0,-0.5,52.0,0.5');

            $response->assertOk();
            $response->assertJsonCount(5, 'data');
        });

        it('respects custom limit parameter', function (): void {
            Venue::factory()->count(10)->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ])->each(fn (Venue $venue) => Report::factory()->for($venue, 'venue')->create());

            $response = $this->getJson('/api/venues?bounds=51.0,-0.5,52.0,0.5&limit=3');

            $response->assertOk();
            $response->assertJsonCount(3, 'data');
        });

        it('only returns venues with at least one report', function (): void {
            $venueWithReport = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->for($venueWithReport, 'venue')->create();

            Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);

            $response = $this->getJson('/api/venues?bounds=51.0,-0.5,52.0,0.5');

            $response->assertOk();
            $response->assertJsonCount(1, 'data');
            $response->assertJsonPath('data.0.reports_count', 1);
        });
    });

    describe('index validation', function (): void {
        it('rejects invalid bounds format', function (): void {
            $response = $this->getJson('/api/venues?bounds=invalid');

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['bounds']);
        });

        it('rejects bounds with insufficient coordinates', function (): void {
            $response = $this->getJson('/api/venues?bounds=51.0,-0.5,52.0');

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['bounds']);
        });

        it('rejects bounds with invalid latitude range', function (): void {
            $response = $this->getJson('/api/venues?bounds=91.0,-0.5,52.0,0.5');

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['bounds']);
        });

        it('rejects bounds with invalid longitude range', function (): void {
            $response = $this->getJson('/api/venues?bounds=51.0,-181.0,52.0,0.5');

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['bounds']);
        });

        it('rejects bounds where sw latitude exceeds ne latitude', function (): void {
            $response = $this->getJson('/api/venues?bounds=53.0,-0.5,51.0,0.5');

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['bounds']);
        });

        it('rejects limit exceeding maximum', function (): void {
            $response = $this->getJson('/api/venues?limit=501');

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['limit']);
        });

        it('rejects limit below minimum', function (): void {
            $response = $this->getJson('/api/venues?limit=0');

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['limit']);
        });

        it('rejects non-integer limit', function (): void {
            $response = $this->getJson('/api/venues?limit=abc');

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['limit']);
        });
    });

    describe('index with city filter', function (): void {
        it('filters venues by city at database level', function (): void {
            Venue::factory()->create(['city' => 'London']);
            Venue::factory()->create(['city' => 'Manchester']);

            $response = $this->getJson('/api/venues?city=London');

            $response->assertOk();
            $response->assertJsonCount(1, 'data');
            $response->assertJsonPath('data.0.city', 'London');
        });
    });

    describe('index without filters', function (): void {
        it('returns venues with default limit', function (): void {
            Venue::factory()->count(5)->create();

            $response = $this->getJson('/api/venues');

            $response->assertOk();
            $response->assertJsonCount(5, 'data');
        });
    });
});
