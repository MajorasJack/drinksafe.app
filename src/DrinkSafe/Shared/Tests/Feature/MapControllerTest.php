<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('MapController', function (): void {
    it('renders map page successfully', function (): void {
        $response = $this->get(route('map'));

        $response->assertOk();
    });

    it('shares the configured CARTO basemap key with the page', function (): void {
        config(['services.carto.key' => 'carto-key-123']);

        $response = $this->get(route('map'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Map')
            ->where('carto.apiKey', 'carto-key-123')
        );
    });

    it('shows map page with Inertia component', function (): void {
        $response = $this->get(route('map'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Map')
        );
    });

    it('renders map page with empty venues for viewport loading', function (): void {
        Venue::factory()->count(5)->create()
            ->each(fn (Venue $venue) => Report::factory()->for($venue, 'venue')->create());

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
        ])->each(fn (Venue $venue) => Report::factory()->for($venue, 'venue')->create());

        Venue::factory()->count(2)->create([
            'latitude' => 55.0,
            'longitude' => 2.0,
        ])->each(fn (Venue $venue) => Report::factory()->for($venue, 'venue')->create());

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

describe('MapController review queue', function (): void {
    it('counts only published reports when searching the map', function (): void {
        $venue = Venue::factory()->create(['name' => 'The Neon Arms']);
        Report::factory()->for($venue, 'venue')->create();
        Report::factory()->for($venue, 'venue')->pending()->create();
        Report::factory()->for($venue, 'venue')->rejected()->create();

        $response = $this->get(route('map', ['search' => 'Neon']));

        $response->assertInertia(fn ($page) => $page->where('venues.0.reports_count', 1));
    });

    it('counts only published reports when filtering the map by city', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->for($venue, 'venue')->create();
        Report::factory()->for($venue, 'venue')->pending()->create();
        Report::factory()->for($venue, 'venue')->rejected()->create();

        $response = $this->get(route('map', ['city' => $venue->city]));

        $response->assertInertia(fn ($page) => $page->where('venues.0.reports_count', 1));
    });

    it('leaves out venues whose only reports are unpublished when loading by bounds', function (): void {
        $venue = Venue::factory()->create(['latitude' => 51.5, 'longitude' => -0.1]);
        Report::factory()->for($venue, 'venue')->pending()->create();
        Report::factory()->for($venue, 'venue')->rejected()->create();

        $response = $this->get(route('map', ['bounds' => '51.0,-0.5,52.0,0.5']));

        $response->assertInertia(fn ($page) => $page->has('venues', 0));
    });
});

describe('MapController unpublished venues', function (): void {
    it('leaves unpublished venues out of map search results', function (): void {
        $published = Venue::factory()->create(['name' => 'The Neon Arms']);
        Venue::factory()->unpublished()->create(['name' => 'The Neon Tavern']);

        $response = $this->get(route('map', ['search' => 'Neon']));

        $response->assertInertia(fn ($page) => $page
            ->has('venues', 1)
            ->where('venues.0.uuid', $published->uuid)
        );
    });

    it('leaves unpublished venues out of the map for a city', function (): void {
        $published = Venue::factory()->create();
        Venue::factory()->unpublished()->create(['city' => $published->city]);

        $response = $this->get(route('map', ['city' => $published->city]));

        $response->assertInertia(fn ($page) => $page
            ->has('venues', 1)
            ->where('venues.0.uuid', $published->uuid)
        );
    });
});
