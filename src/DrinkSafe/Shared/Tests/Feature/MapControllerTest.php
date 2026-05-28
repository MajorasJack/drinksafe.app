<?php

declare(strict_types=1);

use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('MapController', function (): void {
    it('renders map page successfully', function (): void {
        $response = $this->get(route('map'));

        $response->assertOk();
    });

    it('shows map page with Inertia component', function (): void {
        $response = $this->get(route('map'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Map')
        );
    });

    it('renders map page with empty venues for viewport loading', function (): void {
        Venue::factory()->count(5)->create();

        $response = $this->get(route('map'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Map')
            ->has('venues', 0)
        );
    });

    it('renders map page with venues when bounds provided', function (): void {
        Venue::factory()->count(3)->create([
            'latitude' => 51.5,
            'longitude' => -0.1,
        ]);
        Venue::factory()->count(2)->create([
            'latitude' => 55.0,
            'longitude' => 2.0,
        ]);

        $response = $this->get(route('map', ['bounds' => '51.0,-0.5,52.0,0.5']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Map')
            ->has('venues', 3)
            ->has('venues.0.uuid')
            ->has('venues.0.name')
            ->has('venues.0.latitude')
            ->has('venues.0.longitude')
        );
    });

    it('filters venues by city on map page', function (): void {
        Venue::factory()->create(['city' => 'London']);
        Venue::factory()->create(['city' => 'London']);
        Venue::factory()->create(['city' => 'Manchester']);

        $response = $this->get(route('map', ['city' => 'London']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Map')
            ->has('venues', 2)
            ->where('venues.0.city', 'London')
            ->where('venues.1.city', 'London')
        );
    });

    it('shows empty venues array when no venues exist', function (): void {
        $response = $this->get(route('map'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Map')
            ->has('venues', 0)
        );
    });

    it('returns empty venues for viewport loading when no filters provided', function (): void {
        Venue::factory()->count(3)->create(['city' => 'London']);
        Venue::factory()->count(2)->create(['city' => 'Manchester']);

        $response = $this->get(route('map'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Map')
            ->has('venues', 0)
        );
    });
});
