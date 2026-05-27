<?php

declare(strict_types=1);

use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('VenueController', function (): void {
    describe('index', function (): void {
        it('returns all venues with report counts', function (): void {
            Venue::factory()->count(3)->create();

            $response = $this->getJson(route('api.venues.index'));

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'uuid',
                            'name',
                            'city',
                            'address',
                            'latitude',
                            'longitude',
                            'reports_count',
                            'created_at',
                        ],
                    ],
                    'meta' => ['total'],
                ])
                ->assertJsonCount(3, 'data');
        });

        it('filters venues by city when city parameter provided', function (): void {
            Venue::factory()->create(['city' => 'London']);
            Venue::factory()->create(['city' => 'London']);
            Venue::factory()->create(['city' => 'Manchester']);

            $response = $this->getJson(route('api.venues.index', ['city' => 'London']));

            $response->assertOk()
                ->assertJsonCount(2, 'data');

            $data = $response->json('data');
            expect($data)->each->toHaveKey('city', 'London');
        });

        it('returns empty array when no venues exist', function (): void {
            $response = $this->getJson(route('api.venues.index'));

            $response->assertOk()
                ->assertJsonCount(0, 'data');
        });
    });

    describe('show', function (): void {
        it('returns single venue with reports', function (): void {
            $venue = Venue::factory()->create();

            $response = $this->getJson(route('api.venues.show', $venue->uuid));

            $response->assertOk()
                ->assertJsonStructure([
                    'uuid',
                    'name',
                    'city',
                    'address',
                    'latitude',
                    'longitude',
                    'reports',
                    'created_at',
                ])
                ->assertJsonPath('uuid', $venue->uuid)
                ->assertJsonPath('name', $venue->name);
        });

        it('returns 404 for non-existent venue', function (): void {
            $response = $this->getJson(route('api.venues.show', 'non-existent-uuid'));

            $response->assertNotFound()
                ->assertJsonStructure(['message']);
        });

        it('returns proper JSON structure with correct data types', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5074,
                'longitude' => -0.1278,
            ]);

            $response = $this->getJson(route('api.venues.show', $venue->uuid));

            $response->assertOk();

            $data = $response->json();
            expect($data['latitude'])->toBeFloat();
            expect($data['longitude'])->toBeFloat();
            expect($data['uuid'])->toBeString();
            expect($data['name'])->toBeString();
        });
    });

    describe('store', function (): void {
        it('creates venue with valid data', function (): void {
            $data = [
                'name' => fake()->company(),
                'city' => fake()->city(),
                'address' => fake()->address(),
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
            ];

            $response = $this->postJson(route('api.venues.store'), $data);

            $response->assertCreated()
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'uuid',
                        'name',
                        'city',
                        'address',
                        'latitude',
                        'longitude',
                        'created_at',
                    ],
                ])
                ->assertJsonPath('data.name', $data['name'])
                ->assertJsonPath('data.city', $data['city']);

            $this->assertDatabaseHas('venues', [
                'name' => $data['name'],
                'city' => $data['city'],
            ]);
        });

        it('returns 422 for duplicate venue with same name and city', function (): void {
            $existingVenue = Venue::factory()->create([
                'name' => 'The Neon Club',
                'city' => 'London',
            ]);

            $data = [
                'name' => 'The Neon Club',
                'city' => 'London',
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
            ];

            $response = $this->postJson(route('api.venues.store'), $data);

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonStructure(['message']);
        });

        it('returns 422 for missing required fields', function (): void {
            $response = $this->postJson(route('api.venues.store'), []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['name', 'city', 'latitude', 'longitude']);
        });

        it('returns 422 for invalid latitude', function (): void {
            $data = [
                'name' => fake()->company(),
                'city' => fake()->city(),
                'latitude' => 91.0,
                'longitude' => fake()->longitude(),
            ];

            $response = $this->postJson(route('api.venues.store'), $data);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['latitude']);
        });

        it('returns 422 for invalid longitude', function (): void {
            $data = [
                'name' => fake()->company(),
                'city' => fake()->city(),
                'latitude' => fake()->latitude(),
                'longitude' => 181.0,
            ];

            $response = $this->postJson(route('api.venues.store'), $data);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['longitude']);
        });
    });
});
