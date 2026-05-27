<?php

declare(strict_types=1);

use Database\Factories\DrinkSafe\ReportFactory;
use Database\Factories\DrinkSafe\VenueFactory;
use DrinkSafe\Venues\Exceptions\VenueDuplicateException;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Services\VenueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('VenueService', function (): void {
    beforeEach(function (): void {
        $this->service = new VenueService;
    });

    describe('getAllVenues', function (): void {
        it('returns collection with report counts', function (): void {
            // Arrange
            $venue = VenueFactory::new()->create();
            ReportFactory::new()->count(3)->create(['venue_uuid' => $venue->uuid]);

            // Act
            $venues = $this->service->getAllVenues();

            // Assert
            expect($venues)->toHaveCount(1)
                ->and($venues->first()->reports_count)->toBe(3);
        });
    });

    describe('getVenueById', function (): void {
        it('returns venue with reports loaded', function (): void {
            // Arrange
            $venue = VenueFactory::new()->create();
            ReportFactory::new()->count(2)->create(['venue_uuid' => $venue->uuid]);

            // Act
            $result = $this->service->getVenueById($venue->uuid);

            // Assert
            expect($result->uuid)->toBe($venue->uuid)
                ->and($result->relationLoaded('reports'))->toBeTrue()
                ->and($result->reports)->toHaveCount(2);
        });

        it('throws VenueNotFoundException for invalid UUID', function (): void {
            // Arrange
            $invalidUuid = fake()->uuid();

            // Act & Assert
            expect(fn (): Venue => $this->service->getVenueById($invalidUuid))
                ->toThrow(VenueNotFoundException::class);
        });
    });

    describe('createVenue', function (): void {
        it('creates venue with valid data', function (): void {
            // Arrange
            $data = [
                'name' => fake()->company(),
                'city' => fake()->city(),
                'address' => fake()->address(),
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
            ];

            // Act
            $venue = $this->service->createVenue($data);

            // Assert
            expect($venue)->toBeInstanceOf(Venue::class)
                ->and($venue->name)->toBe($data['name'])
                ->and($venue->city)->toBe($data['city']);
        });

        it('throws VenueDuplicateException for duplicate name and city', function (): void {
            // Arrange
            $existingVenue = VenueFactory::new()->create();
            $duplicateData = [
                'name' => $existingVenue->name,
                'city' => $existingVenue->city,
                'address' => fake()->address(),
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
            ];

            // Act & Assert
            expect(fn (): Venue => $this->service->createVenue($duplicateData))
                ->toThrow(VenueDuplicateException::class);
        });
    });

    describe('updateVenue', function (): void {
        it('updates venue fields correctly', function (): void {
            // Arrange
            $venue = VenueFactory::new()->create();
            $newName = fake()->company();
            $updateData = ['name' => $newName];

            // Act
            $updated = $this->service->updateVenue($venue->uuid, $updateData);

            // Assert
            expect($updated->name)->toBe($newName)
                ->and($updated->uuid)->toBe($venue->uuid);
        });

        it('throws VenueNotFoundException for invalid UUID', function (): void {
            // Arrange
            $invalidUuid = fake()->uuid();
            $updateData = ['name' => fake()->company()];

            // Act & Assert
            expect(fn (): Venue => $this->service->updateVenue($invalidUuid, $updateData))
                ->toThrow(VenueNotFoundException::class);
        });
    });

    describe('deleteVenue', function (): void {
        it('soft deletes venue', function (): void {
            // Arrange
            $venue = VenueFactory::new()->create();

            // Act
            $result = $this->service->deleteVenue($venue->uuid);

            // Assert
            expect($result)->toBeTrue()
                ->and(Venue::find($venue->uuid))->toBeNull();
        });

        it('throws VenueNotFoundException for invalid UUID', function (): void {
            // Arrange
            $invalidUuid = fake()->uuid();

            // Act & Assert
            expect(fn (): bool => $this->service->deleteVenue($invalidUuid))
                ->toThrow(VenueNotFoundException::class);
        });
    });

    describe('getVenueReports', function (): void {
        it('returns paginated reports ordered by date', function (): void {
            // Arrange
            $venue = VenueFactory::new()->create();
            $reports = ReportFactory::new()->count(10)->create([
                'venue_uuid' => $venue->uuid,
                'incident_date' => fake()->dateTimeBetween('-1 year', 'now'),
            ]);

            // Act
            $result = $this->service->getVenueReports($venue->uuid, 5);

            // Assert
            expect($result)->toHaveCount(5);
        });

        it('throws VenueNotFoundException for invalid UUID', function (): void {
            // Arrange
            $invalidUuid = fake()->uuid();

            // Act & Assert
            expect(fn () => $this->service->getVenueReports($invalidUuid))
                ->toThrow(VenueNotFoundException::class);
        });
    });
});
