<?php

declare(strict_types=1);

use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('VenueSearchController', function (): void {
    describe('search', function (): void {
        it('finds venues by search term matching name', function (): void {
            Venue::factory()->create(['name' => 'The Neon Club']);
            Venue::factory()->create(['name' => 'Neon Nights Bar']);
            Venue::factory()->create(['name' => 'Downtown Bar']);

            $response = $this->getJson(route('api.venues.search', ['q' => 'neon']));

            $response->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('finds venues by search term matching city', function (): void {
            Venue::factory()->create(['city' => 'London']);
            Venue::factory()->create(['city' => 'London']);
            Venue::factory()->create(['city' => 'Manchester']);

            $response = $this->getJson(route('api.venues.search', ['q' => 'london']));

            $response->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('filters venues by city parameter', function (): void {
            Venue::factory()->create(['city' => 'London']);
            Venue::factory()->create(['city' => 'London']);
            Venue::factory()->create(['city' => 'Manchester']);

            $response = $this->getJson(route('api.venues.search', ['city' => 'London']));

            $response->assertOk()
                ->assertJsonCount(2, 'data');
        });

        it('returns nearby venues within specified radius', function (): void {
            Venue::factory()->create([
                'name' => 'Close Venue',
                'latitude' => 51.5074,
                'longitude' => -0.1278,
            ]);

            Venue::factory()->create([
                'name' => 'Far Venue',
                'latitude' => 52.4862,
                'longitude' => -1.8904,
            ]);

            $response = $this->getJson(route('api.venues.search', [
                'lat' => 51.5074,
                'lng' => -0.1278,
                'radius' => 10,
            ]));

            $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.name', 'Close Venue');
        });

        it('excludes venues outside specified radius', function (): void {
            Venue::factory()->create([
                'name' => 'London Venue',
                'latitude' => 51.5074,
                'longitude' => -0.1278,
            ]);

            Venue::factory()->create([
                'name' => 'Birmingham Venue',
                'latitude' => 52.4862,
                'longitude' => -1.8904,
            ]);

            $response = $this->getJson(route('api.venues.search', [
                'lat' => 51.5074,
                'lng' => -0.1278,
                'radius' => 10,
            ]));

            $response->assertOk();

            $names = collect($response->json('data'))->pluck('name');
            expect($names)->not->toContain('Birmingham Venue');
        });

        it('handles empty search query gracefully', function (): void {
            Venue::factory()->count(3)->create();

            $response = $this->getJson(route('api.venues.search'));

            $response->assertOk()
                ->assertJsonCount(0, 'data');
        });

        it('includes report counts in all search results', function (): void {
            Venue::factory()->create(['name' => 'Test Venue']);

            $response = $this->getJson(route('api.venues.search', ['q' => 'test']));

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'uuid',
                            'name',
                            'city',
                            'reports_count',
                        ],
                    ],
                ]);
        });

        it('validates latitude and longitude are both required together', function (): void {
            $response = $this->getJson(route('api.venues.search', ['lat' => 51.5074]));

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['lng']);
        });

        it('validates latitude range', function (): void {
            $response = $this->getJson(route('api.venues.search', [
                'lat' => 91.0,
                'lng' => 0.0,
            ]));

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['lat']);
        });

        it('validates longitude range', function (): void {
            $response = $this->getJson(route('api.venues.search', [
                'lat' => 0.0,
                'lng' => 181.0,
            ]));

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['lng']);
        });
    });
});
