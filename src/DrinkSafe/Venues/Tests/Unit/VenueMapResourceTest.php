<?php

declare(strict_types=1);

use Database\Factories\DrinkSafe\ReportFactory;
use Database\Factories\DrinkSafe\VenueFactory;
use DrinkSafe\Venues\Resources\VenueMapResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('VenueMapResource', function (): void {
    it('includes only minimal fields for map markers', function (): void {
        // Arrange
        $venue = VenueFactory::new()->create([
            'name' => 'Test Venue',
            'city' => 'London',
            'address' => '123 Test Street',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ]);

        // Act
        $resource = (new VenueMapResource($venue))->toArray(new Request);

        // Assert
        expect($resource)->toHaveKeys(['uuid', 'name', 'slug', 'city', 'latitude', 'longitude'])
            ->and($resource)->not->toHaveKeys(['address', 'reports', 'created_at']);
    });

    it('excludes address field', function (): void {
        // Arrange
        $venue = VenueFactory::new()->create([
            'address' => '123 Test Street',
        ]);

        // Act
        $resource = (new VenueMapResource($venue))->toArray(new Request);

        // Assert
        expect($resource)->not->toHaveKey('address');
    });

    it('excludes reports relationship', function (): void {
        // Arrange
        $venue = VenueFactory::new()->create();
        ReportFactory::new()->count(3)->create(['venue_uuid' => $venue->uuid]);
        $venueWithReports = $venue->load('reports');

        // Act
        $resource = (new VenueMapResource($venueWithReports))->toArray(new Request);

        // Assert
        expect($resource)->not->toHaveKey('reports');
    });

    it('excludes created_at field', function (): void {
        // Arrange
        $venue = VenueFactory::new()->create();

        // Act
        $resource = (new VenueMapResource($venue))->toArray(new Request);

        // Assert
        expect($resource)->not->toHaveKey('created_at');
    });

    it('includes reports_count when loaded', function (): void {
        // Arrange
        $venue = VenueFactory::new()->create();
        ReportFactory::new()->count(5)->create(['venue_uuid' => $venue->uuid]);
        $venueWithCount = $venue->loadCount('reports');

        // Act
        $resource = (new VenueMapResource($venueWithCount))->toArray(new Request);

        // Assert
        expect($resource['reports_count'])->toBe(5);
    });

    it('casts latitude and longitude to float', function (): void {
        // Arrange
        $venue = VenueFactory::new()->create([
            'latitude' => '51.5074',
            'longitude' => '-0.1278',
        ]);

        // Act
        $resource = (new VenueMapResource($venue))->toArray(new Request);

        // Assert
        expect($resource['latitude'])->toBeFloat()
            ->and($resource['longitude'])->toBeFloat();
    });
});
