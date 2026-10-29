<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
                ->assertExactJsonStructure([
                    'message',
                    'data' => ['uuid'],
                ]);

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

        it('logs nothing that identifies the person adding a venue', function (): void {
            $ipAddress = fake()->ipv4();
            $userAgent = sprintf('Mozilla/5.0 DistinctiveVenueAdder/%s', fake()->uuid());
            $loggedEntries = collect();
            Log::listen(fn (MessageLogged $entry): Collection => $loggedEntries->push(json_encode([$entry->message, $entry->context], JSON_UNESCAPED_SLASHES)));

            $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
                ->withHeader('User-Agent', $userAgent)
                ->postJson(route('api.venues.store'), [
                    'name' => fake()->company(),
                    'city' => fake()->city(),
                    'latitude' => fake()->latitude(),
                    'longitude' => fake()->longitude(),
                ])
                ->assertCreated();

            $everyLogLine = $loggedEntries->implode("\n");
            expect($loggedEntries)->not->toBeEmpty()
                ->and($everyLogLine)->not->toContain($ipAddress)
                ->and($everyLogLine)->not->toContain(hash('sha256', $ipAddress))
                ->and($everyLogLine)->not->toContain($userAgent)
                ->and($everyLogLine)->not->toContain(hash('sha256', $userAgent));
        });
    });
});

describe('VenueController review queue', function (): void {
    it('counts only published reports when listing venues', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->for($venue, 'venue')->create();
        Report::factory()->for($venue, 'venue')->pending()->create();
        Report::factory()->for($venue, 'venue')->rejected()->create();

        $response = $this->getJson(route('api.venues.index'));

        $response->assertOk()->assertJsonPath('data.0.reports_count', 1);
    });

    it('counts only published reports when listing venues in a city', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->for($venue, 'venue')->create();
        Report::factory()->for($venue, 'venue')->pending()->create();
        Report::factory()->for($venue, 'venue')->rejected()->create();

        $response = $this->getJson(route('api.venues.index', ['city' => $venue->city]));

        $response->assertOk()->assertJsonPath('data.0.reports_count', 1);
    });

    it('leaves out venues whose only reports are unpublished when listing by bounds', function (): void {
        $venue = Venue::factory()->create(['latitude' => 51.5, 'longitude' => -0.1]);
        Report::factory()->for($venue, 'venue')->pending()->create();
        Report::factory()->for($venue, 'venue')->rejected()->create();

        $response = $this->getJson(route('api.venues.index', ['bounds' => '51.0,-0.5,52.0,0.5']));

        $response->assertOk()->assertJsonCount(0, 'data');
    });

    it('shows only published reports for a single venue', function (): void {
        $venue = Venue::factory()->create();
        $published = Report::factory()->for($venue, 'venue')->create();
        Report::factory()->for($venue, 'venue')->pending()->create();
        Report::factory()->for($venue, 'venue')->rejected()->create();

        $response = $this->getJson(route('api.venues.show', $venue->uuid));

        $response->assertOk()
            ->assertJsonPath('reports_count', 1)
            ->assertJsonPath('reports.0.uuid', $published->uuid)
            ->assertJsonCount(1, 'reports');
    });
});

describe('VenueController unpublished venues', function (): void {
    it('leaves unpublished venues out of the venue list', function (): void {
        $published = Venue::factory()->create();
        Venue::factory()->unpublished()->create();

        $response = $this->getJson(route('api.venues.index'));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $published->uuid);
    });

    it('leaves unpublished venues out of the venue list for a city', function (): void {
        $published = Venue::factory()->create();
        Venue::factory()->unpublished()->create(['city' => $published->city]);

        $response = $this->getJson(route('api.venues.index', ['city' => $published->city]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $published->uuid);
    });

    it('returns not found for a venue that has not been published', function (): void {
        $venue = Venue::factory()->unpublished()->create();

        $response = $this->getJson(route('api.venues.show', $venue->uuid));

        $response->assertNotFound();
    });

    it('creates venues unpublished', function (): void {
        $name = fake()->company();

        $response = $this->postJson(route('api.venues.store'), [
            'name' => $name,
            'city' => fake()->city(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('venues', ['name' => $name, 'published_at' => null]);
    });

    it('returns the existing unpublished venue instead of creating a copy', function (): void {
        $venue = Venue::factory()->unpublished()->create();

        $response = $this->postJson(route('api.venues.store'), [
            'name' => $venue->name,
            'city' => $venue->city,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]);

        $response->assertCreated()->assertJsonPath('data.uuid', $venue->uuid);
        expect(Venue::withoutGlobalScope(PublishedVenueScope::class)->count())->toBe(1);
    });

    it('leaves the slug out so it cannot reveal a hidden venue with the same name', function (): void {
        $venue = Venue::factory()->unpublished()->create();

        $response = $this->postJson(route('api.venues.store'), [
            'name' => $venue->name,
            'city' => sprintf('%s North', $venue->city),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]);

        $response->assertCreated()->assertJsonMissingPath('data.slug');
    });

    it('leaves published_at out of the venue for anyone who is not an admin', function (): void {
        $venue = Venue::factory()->create();

        $response = $this->getJson(route('api.venues.show', $venue->uuid));

        $response->assertOk()->assertJsonMissingPath('published_at');
    });
});
