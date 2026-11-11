<?php

declare(strict_types=1);

use Database\Factories\DrinkSafe\ReportFactory;
use Database\Factories\DrinkSafe\VenueFactory;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Shared\Services\PublicCacheVersion;
use DrinkSafe\Venues\Exceptions\VenueDuplicateException;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;
use DrinkSafe\Venues\Services\VenueService;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('VenueService', function (): void {
    beforeEach(function (): void {
        $this->service = app(VenueService::class);
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

        it('respects optional limit parameter', function (): void {
            // Arrange
            VenueFactory::new()->count(10)->create();

            // Act
            $venues = $this->service->getAllVenues(5);

            // Assert
            expect($venues)->toHaveCount(5);
        });

        it('returns all venues when limit is null', function (): void {
            // Arrange
            VenueFactory::new()->count(10)->create();

            // Act
            $venues = $this->service->getAllVenues(null);

            // Assert
            expect($venues)->toHaveCount(10);
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

describe('VenueService createVenue publishing', function (): void {
    it('creates the venue unpublished', function (): void {
        $venue = app(VenueService::class)->createVenue([
            'name' => fake()->company(),
            'city' => fake()->city(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]);

        $this->assertDatabaseHas('venues', ['uuid' => $venue->uuid, 'published_at' => null]);
    });
});

describe('VenueService createVenue duplicates', function (): void {
    it('treats a name and city that differ only in case and spaces as a duplicate', function (): void {
        $city = fake()->city();
        Venue::factory()->create(['name' => 'The Crown', 'city' => $city]);

        expect(fn (): Venue => app(VenueService::class)->createVenue([
            'name' => ' the crown ',
            'city' => mb_strtoupper($city),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]))->toThrow(VenueDuplicateException::class);
    });

    it('returns the waiting unpublished venue for a name that differs only in case', function (): void {
        $city = fake()->city();
        $waitingVenue = Venue::factory()->unpublished()->create(['name' => 'The Crown', 'city' => $city]);

        $venue = app(VenueService::class)->createVenue([
            'name' => 'the crown',
            'city' => $city,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]);

        expect($venue->uuid)->toBe($waitingVenue->uuid);
    });

    it('refuses the name when an older unpublished copy and a published copy both match', function (): void {
        $name = fake()->company();
        $city = fake()->city();
        Venue::factory()->unpublished()->create(['name' => strtolower($name), 'city' => $city, 'created_at' => now()->subDay()]);
        Venue::factory()->create(['name' => $name, 'city' => $city]);

        expect(fn (): Venue => app(VenueService::class)->createVenue([
            'name' => $name,
            'city' => $city,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]))->toThrow(VenueDuplicateException::class);
    });

    it('returns the oldest unpublished copy when several match', function (): void {
        $name = fake()->company();
        $city = fake()->city();
        Venue::factory()->unpublished()->create(['name' => strtolower($name), 'city' => $city]);
        $oldestVenue = Venue::factory()->unpublished()->create(['name' => $name, 'city' => $city, 'created_at' => now()->subDay()]);

        $venue = app(VenueService::class)->createVenue([
            'name' => $name,
            'city' => $city,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]);

        expect($venue->uuid)->toBe($oldestVenue->uuid);
    });
});

describe('VenueService publishVenue', function (): void {
    it('bumps the public cache version', function (): void {
        $venue = Venue::factory()->unpublishedByAdmin()->create();

        app(VenueService::class)->publishVenue($venue->uuid);

        expect(app(PublicCacheVersion::class)->current())->toBe(2);
    });

    it('clears the venue\'s cached safety score', function (): void {
        $venue = Venue::factory()->unpublishedByAdmin()->create();
        Cache::put(sprintf('venue:safety_score:%s', $venue->uuid), fake()->numberBetween(0, 100));

        app(VenueService::class)->publishVenue($venue->uuid);

        expect(Cache::has(sprintf('venue:safety_score:%s', $venue->uuid)))->toBeFalse();
    });

    it('leaves an already published venue and the caches alone', function (): void {
        $publishedAt = now()->subWeek()->startOfSecond();
        $venue = Venue::factory()->create(['published_at' => $publishedAt]);

        app(VenueService::class)->publishVenue($venue->uuid);

        expect($venue->fresh()->published_at->equalTo($publishedAt))->toBeTrue()
            ->and(app(PublicCacheVersion::class)->current())->toBe(1);
    });
});

describe('VenueService unpublishVenue', function (): void {
    it('leaves a venue the admin already unpublished and the caches alone', function (): void {
        $unpublishedAt = now()->subWeek()->startOfSecond();
        $venue = Venue::factory()->unpublishedByAdmin()->create(['unpublished_at' => $unpublishedAt]);

        app(VenueService::class)->unpublishVenue($venue->uuid);

        expect(Venue::withoutGlobalScope(PublishedVenueScope::class)->find($venue->uuid)->unpublished_at->equalTo($unpublishedAt))->toBeTrue()
            ->and(app(PublicCacheVersion::class)->current())->toBe(1);
    });

    it('marks a never published venue as unpublished by the admin', function (): void {
        $venue = Venue::factory()->unpublished()->create();

        app(VenueService::class)->unpublishVenue($venue->uuid);

        expect(Venue::withoutGlobalScope(PublishedVenueScope::class)->find($venue->uuid)->wasUnpublishedByAdmin())->toBeTrue();
    });
});

describe('VenueService getVenueReportsForAdmin', function (): void {
    it('returns 25 reports in every status per page, newest first', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->count(26)->for($venue, 'venue')->pending()->sequence(fn (Sequence $sequence): array => ['created_at' => now()->subDays($sequence->index + 1)])->create();
        $newestReport = Report::factory()->for($venue, 'venue')->rejected()->create(['created_at' => now()]);

        $reports = app(VenueService::class)->getVenueReportsForAdmin($venue);

        expect($reports->total())->toBe(27)
            ->and($reports->items())->toHaveCount(25)
            ->and($reports->items()[0]->uuid)->toBe($newestReport->uuid);
    });
});
