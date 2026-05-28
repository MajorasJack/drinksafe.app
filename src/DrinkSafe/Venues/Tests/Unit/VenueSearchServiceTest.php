<?php

declare(strict_types=1);

use Database\Factories\DrinkSafe\ReportFactory;
use Database\Factories\DrinkSafe\VenueFactory;
use DrinkSafe\Shared\Services\GeolocationService;
use DrinkSafe\Venues\Services\VenueSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('VenueSearchService', function (): void {
    beforeEach(function (): void {
        $this->geolocationService = new GeolocationService;
        $this->service = new VenueSearchService($this->geolocationService);
    });

    describe('search', function (): void {
        it('finds venues by partial name match', function (): void {
            // Arrange
            VenueFactory::new()->create(['name' => 'The Blue Lounge', 'city' => 'London']);
            VenueFactory::new()->create(['name' => 'Red Bar', 'city' => 'Manchester']);

            // Act
            $results = $this->service->search('Blue');

            // Assert
            expect($results)->toHaveCount(1)
                ->and($results->first()->name)->toBe('The Blue Lounge');
        });

        it('finds venues by partial city match', function (): void {
            // Arrange
            VenueFactory::new()->create(['name' => 'Bar One', 'city' => 'London']);
            VenueFactory::new()->create(['name' => 'Bar Two', 'city' => 'Manchester']);

            // Act
            $results = $this->service->search('Manchester');

            // Assert
            expect($results)->toHaveCount(1)
                ->and($results->first()->city)->toBe('Manchester');
        });

        it('returns empty collection for no matches', function (): void {
            // Arrange
            VenueFactory::new()->create(['name' => 'Test Venue', 'city' => 'London']);

            // Act
            $results = $this->service->search('NonexistentVenue');

            // Assert
            expect($results)->toBeEmpty();
        });

        it('respects the limit parameter', function (): void {
            // Arrange
            VenueFactory::new()->count(10)->create(['city' => 'London']);

            // Act
            $results = $this->service->search('London', 5);

            // Assert
            expect($results)->toHaveCount(5);
        });

        it('uses default limit of 50 when not specified', function (): void {
            // Arrange
            VenueFactory::new()->count(60)->create(['city' => 'London']);

            // Act
            $results = $this->service->search('London');

            // Assert
            expect($results)->toHaveCount(50);
        });
    });

    describe('filterByCity', function (): void {
        it('returns only venues in specified city', function (): void {
            // Arrange
            VenueFactory::new()->count(2)->create(['city' => 'London']);
            VenueFactory::new()->create(['city' => 'Manchester']);

            // Act
            $results = $this->service->filterByCity('London');

            // Assert
            expect($results)->toHaveCount(2)
                ->and($results->every(fn ($venue): bool => $venue->city === 'London'))->toBeTrue();
        });

        it('respects the limit parameter', function (): void {
            // Arrange
            VenueFactory::new()->count(10)->create(['city' => 'London']);

            // Act
            $results = $this->service->filterByCity('London', 3);

            // Assert
            expect($results)->toHaveCount(3);
        });

        it('uses default limit of 50 when not specified', function (): void {
            // Arrange
            VenueFactory::new()->count(60)->create(['city' => 'London']);

            // Act
            $results = $this->service->filterByCity('London');

            // Assert
            expect($results)->toHaveCount(50);
        });
    });

    describe('nearby', function (): void {
        it('returns venues within radius', function (): void {
            // Arrange
            $londonLat = 51.5074;
            $londonLng = -0.1278;

            VenueFactory::new()->create([
                'name' => 'Central Venue',
                'latitude' => $londonLat,
                'longitude' => $londonLng,
            ]);

            VenueFactory::new()->create([
                'name' => 'Nearby Venue',
                'latitude' => 51.5155,
                'longitude' => -0.1415,
            ]);

            // Act
            $results = $this->service->nearby($londonLat, $londonLng, 5);

            // Assert
            expect($results)->toHaveCount(2);
        });

        it('excludes venues outside radius', function (): void {
            // Arrange
            $londonLat = 51.5074;
            $londonLng = -0.1278;

            VenueFactory::new()->create([
                'name' => 'London Venue',
                'latitude' => $londonLat,
                'longitude' => $londonLng,
            ]);

            VenueFactory::new()->create([
                'name' => 'Manchester Venue',
                'latitude' => 53.4808,
                'longitude' => -2.2426,
            ]);

            // Act
            $results = $this->service->nearby($londonLat, $londonLng, 10);

            // Assert
            expect($results)->toHaveCount(1)
                ->and($results->first()->name)->toBe('London Venue');
        });
    });

    describe('withReportCounts', function (): void {
        it('is chainable and loads counts', function (): void {
            // Arrange
            $venue = VenueFactory::new()->create();
            ReportFactory::new()->count(5)->create(['venue_uuid' => $venue->uuid]);

            // Act
            $result = $this->service->withReportCounts();
            $venues = $result->filterByCity($venue->city);

            // Assert
            expect($result)->toBeInstanceOf(VenueSearchService::class)
                ->and($venues->first()->reports_count)->toBe(5);
        });
    });
});
