<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Venue Model', function (): void {
    it('has UUID primary key instead of integer', function (): void {
        $venue = Venue::factory()->create();

        expect($venue->getKeyType())->toBe('string')
            ->and($venue->getIncrementing())->toBeFalse()
            ->and($venue->uuid)->toBeString()
            ->and($venue->uuid)->toHaveLength(36);
    });

    it('automatically generates UUID on creation', function (): void {
        $venue = new Venue([
            'name' => fake()->company(),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]);

        $venue->save();

        expect($venue->uuid)->not->toBeNull()
            ->and($venue->uuid)->toBeString()
            ->and($venue->uuid)->toHaveLength(36);
    });

    it('reports relationship returns HasMany', function (): void {
        $venue = Venue::factory()->create();

        $relationship = $venue->reports();

        expect($relationship)->toBeInstanceOf(HasMany::class);
    });

    it('can retrieve associated reports through relationship', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->count(3)->create(['venue_uuid' => $venue->uuid]);

        $reports = $venue->reports;

        expect($reports)->toHaveCount(3)
            ->and($reports->first())->toBeInstanceOf(Report::class);
    });

    it('scopeInCity filters venues by city correctly', function (): void {
        Venue::factory()->create(['city' => 'London']);
        Venue::factory()->create(['city' => 'Manchester']);
        Venue::factory()->create(['city' => 'London']);

        $londonVenues = Venue::inCity('London')->get();

        expect($londonVenues)->toHaveCount(2)
            ->and($londonVenues->every(fn (Venue $v): bool => $v->city === 'London'))->toBeTrue();
    });

    it('scopeSearch finds venues by partial name match case-insensitive', function (): void {
        Venue::factory()->create(['name' => 'The Crown Bar', 'city' => 'London']);
        Venue::factory()->create(['name' => 'The Royal Oak', 'city' => 'Manchester']);
        Venue::factory()->create(['name' => 'Blue Moon Lounge', 'city' => 'Birmingham']);

        $results = Venue::search('crown')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('The Crown Bar');
    });

    it('scopeSearch finds venues by partial city match case-insensitive', function (): void {
        Venue::factory()->create(['name' => 'Bar One', 'city' => 'Manchester']);
        Venue::factory()->create(['name' => 'Bar Two', 'city' => 'Birmingham']);
        Venue::factory()->create(['name' => 'Bar Three', 'city' => 'Manchester']);

        $results = Venue::search('manch')->get();

        expect($results)->toHaveCount(2)
            ->and($results->every(fn (Venue $v): bool => str_contains(strtolower($v->city), 'manch')))->toBeTrue();
    });

    it('scopeNearby returns venues within specified radius', function (): void {
        // Create venue at specific coordinates (London centre: 51.5074, -0.1278)
        $centralVenue = Venue::factory()->create([
            'name' => 'Central Venue',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ]);

        // Create nearby venue (approximately 5km away)
        $nearbyVenue = Venue::factory()->create([
            'name' => 'Nearby Venue',
            'latitude' => 51.5500,
            'longitude' => -0.1278,
        ]);

        // Create far venue (approximately 100km away)
        $farVenue = Venue::factory()->create([
            'name' => 'Far Venue',
            'latitude' => 52.5074,
            'longitude' => -0.1278,
        ]);

        // Search within 10km radius
        $results = Venue::nearby(51.5074, -0.1278, 10)->get();

        expect($results)->toHaveCount(2)
            ->and($results->pluck('name')->contains('Central Venue'))->toBeTrue()
            ->and($results->pluck('name')->contains('Nearby Venue'))->toBeTrue()
            ->and($results->pluck('name')->contains('Far Venue'))->toBeFalse();
    });

    it('factory creates valid venue with all required fields', function (): void {
        $venue = Venue::factory()->create();

        expect($venue)->toBeInstanceOf(Venue::class)
            ->and($venue->uuid)->not->toBeNull()
            ->and($venue->name)->not->toBeNull()
            ->and($venue->city)->not->toBeNull()
            ->and($venue->latitude)->toBeNumeric()
            ->and($venue->longitude)->toBeNumeric()
            ->and($venue->latitude)->toBeGreaterThanOrEqual(50.0)
            ->and($venue->latitude)->toBeLessThanOrEqual(56.0)
            ->and($venue->longitude)->toBeGreaterThanOrEqual(-6.0)
            ->and($venue->longitude)->toBeLessThanOrEqual(2.0);
    });

    it('factory london state sets London coordinates', function (): void {
        $venue = Venue::factory()->london()->create();

        expect($venue->city)->toBe('London')
            ->and($venue->latitude)->toBeGreaterThanOrEqual(51.3)
            ->and($venue->latitude)->toBeLessThanOrEqual(51.7)
            ->and($venue->longitude)->toBeGreaterThanOrEqual(-0.5)
            ->and($venue->longitude)->toBeLessThanOrEqual(0.3);
    });

    it('factory manchester state sets Manchester coordinates', function (): void {
        $venue = Venue::factory()->manchester()->create();

        expect($venue->city)->toBe('Manchester')
            ->and($venue->latitude)->toBeGreaterThanOrEqual(53.3)
            ->and($venue->latitude)->toBeLessThanOrEqual(53.6)
            ->and($venue->longitude)->toBeGreaterThanOrEqual(-2.4)
            ->and($venue->longitude)->toBeLessThanOrEqual(-2.0);
    });

    it('casts latitude to decimal', function (): void {
        $venue = Venue::factory()->create(['latitude' => 51.50740123]);

        $freshVenue = $venue->fresh();

        expect($freshVenue->latitude)->toBeString()
            ->and($freshVenue->latitude)->toBe('51.50740123');
    });

    it('casts longitude to decimal', function (): void {
        $venue = Venue::factory()->create(['longitude' => -0.12780456]);

        $freshVenue = $venue->fresh();

        expect($freshVenue->longitude)->toBeString()
            ->and($freshVenue->longitude)->toBe('-0.12780456');
    });
});
