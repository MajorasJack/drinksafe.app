<?php

declare(strict_types=1);

use DrinkSafe\Venues\Models\Venue;
use Inertia\Testing\AssertableInertia as Assert;

describe('MapController bounds filtering', function (): void {
    describe('initial page load', function (): void {
        it('returns empty venues when no filters provided', function (): void {
            Venue::factory()->count(10)->create();

            $response = $this->get('/map');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Map')
                ->where('venues', [])
            );
        });

        it('passes empty filters when none provided', function (): void {
            $response = $this->get('/map');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Map')
                ->where('filters.search', null)
                ->where('filters.city', null)
                ->where('filters.bounds', null)
            );
        });
    });

    describe('bounds filtering', function (): void {
        it('returns venues within specified bounds', function (): void {
            $venueInBounds = Venue::factory()->create([
                'name' => 'In Bounds Venue',
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);

            Venue::factory()->create([
                'name' => 'Out of Bounds Venue',
                'latitude' => 53.5,
                'longitude' => -2.2,
            ]);

            $response = $this->get('/map?bounds=51.0,-0.5,52.0,0.5');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Map')
                ->has('venues', 1)
                ->where('venues.0.name', $venueInBounds->name)
            );
        });

        it('passes bounds filter to frontend', function (): void {
            $response = $this->get('/map?bounds=51.0,-0.5,52.0,0.5');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Map')
                ->where('filters.bounds', '51.0,-0.5,52.0,0.5')
            );
        });

        it('returns empty for invalid bounds format', function (): void {
            Venue::factory()->create();

            $response = $this->get('/map?bounds=invalid');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Map')
                ->where('venues', [])
            );
        });

        it('returns empty for bounds with invalid coordinate count', function (): void {
            Venue::factory()->create();

            $response = $this->get('/map?bounds=51.0,-0.5,52.0');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Map')
                ->where('venues', [])
            );
        });

        it('returns empty for bounds with out of range latitude', function (): void {
            Venue::factory()->create();

            $response = $this->get('/map?bounds=91.0,-0.5,92.0,0.5');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Map')
                ->where('venues', [])
            );
        });

        it('returns empty for bounds where sw latitude exceeds ne latitude', function (): void {
            Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);

            $response = $this->get('/map?bounds=53.0,-0.5,51.0,0.5');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Map')
                ->where('venues', [])
            );
        });
    });

    describe('search filter priority', function (): void {
        it('prioritises search over bounds', function (): void {
            $searchVenue = Venue::factory()->create([
                'name' => 'Searchable Venue',
                'latitude' => 53.5,
                'longitude' => -2.2,
            ]);

            Venue::factory()->create([
                'name' => 'Bounds Only Venue',
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);

            $response = $this->get('/map?search=Searchable&bounds=51.0,-0.5,52.0,0.5');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Map')
                ->has('venues', 1)
                ->where('venues.0.name', $searchVenue->name)
            );
        });

        it('prioritises city over bounds', function (): void {
            $cityVenue = Venue::factory()->create([
                'name' => 'Manchester Venue',
                'city' => 'Manchester',
                'latitude' => 53.5,
                'longitude' => -2.2,
            ]);

            Venue::factory()->create([
                'name' => 'London Venue',
                'city' => 'London',
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);

            $response = $this->get('/map?city=Manchester&bounds=51.0,-0.5,52.0,0.5');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Map')
                ->has('venues', 1)
                ->where('venues.0.name', $cityVenue->name)
            );
        });
    });

    describe('legacy behaviour', function (): void {
        it('continues to support search filtering', function (): void {
            $venue = Venue::factory()->create(['name' => 'The Phoenix Bar']);
            Venue::factory()->create(['name' => 'Other Venue']);

            $response = $this->get('/map?search=Phoenix');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Map')
                ->has('venues', 1)
                ->where('venues.0.name', $venue->name)
            );
        });

        it('continues to support city filtering', function (): void {
            $venue = Venue::factory()->create(['city' => 'London']);
            Venue::factory()->create(['city' => 'Manchester']);

            $response = $this->get('/map?city=London');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Map')
                ->has('venues', 1)
                ->where('venues.0.city', 'London')
            );
        });
    });
});
