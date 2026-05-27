<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReportController index', function (): void {
    it('returns recent reports with venues loaded', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        $reports = Report::factory()
            ->count(3)
            ->for($venue, 'venue')
            ->create();

        // Act
        $response = $this->getJson(route('api.reports.index'));

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'uuid',
                        'venue_uuid',
                        'incident_date',
                        'time_of_day',
                        'description',
                        'formatted_date',
                        'venue',
                        'created_at',
                    ],
                ],
            ]);
    });

    it('filters reports by venue uuid', function (): void {
        // Arrange
        $venue1 = Venue::factory()->create();
        $venue2 = Venue::factory()->create();

        Report::factory()->count(2)->for($venue1, 'venue')->create();
        Report::factory()->count(3)->for($venue2, 'venue')->create();

        // Act
        $response = $this->getJson(route('api.reports.index', ['venue_uuid' => $venue1->uuid]));

        // Assert
        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters reports by date range', function (): void {
        // Arrange
        $venue = Venue::factory()->create();

        Report::factory()
            ->for($venue, 'venue')
            ->create(['incident_date' => '2024-01-15']);

        Report::factory()
            ->for($venue, 'venue')
            ->create(['incident_date' => '2024-06-15']);

        Report::factory()
            ->for($venue, 'venue')
            ->create(['incident_date' => '2024-12-15']);

        // Act
        $response = $this->getJson(route('api.reports.index', [
            'start_date' => '2024-06-01',
            'end_date' => '2024-12-31',
        ]));

        // Assert
        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters reports by time of day', function (): void {
        // Arrange
        $venue = Venue::factory()->create();

        Report::factory()
            ->count(2)
            ->for($venue, 'venue')
            ->night()
            ->create();

        Report::factory()
            ->count(3)
            ->for($venue, 'venue')
            ->morning()
            ->create();

        // Act
        $response = $this->getJson(route('api.reports.index', ['time_of_day' => 'Night']));

        // Assert
        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('respects limit parameter', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        Report::factory()
            ->count(10)
            ->for($venue, 'venue')
            ->create();

        // Act
        $response = $this->getJson(route('api.reports.index', ['limit' => 5]));

        // Assert
        $response->assertOk()
            ->assertJsonCount(5, 'data');
    });

    it('enforces maximum limit of 100', function (): void {
        // Arrange
        $limit = 150;

        // Act
        $response = $this->getJson(route('api.reports.index', ['limit' => $limit]));

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);
    });

    it('validates start and end dates are required together', function (): void {
        // Act
        $response = $this->getJson(route('api.reports.index', ['start_date' => '2024-01-01']));

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    });

    it('validates end date is after start date', function (): void {
        // Act
        $response = $this->getJson(route('api.reports.index', [
            'start_date' => '2024-12-31',
            'end_date' => '2024-01-01',
        ]));

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    });
});

describe('ReportController show', function (): void {
    it('returns single report with venue', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        $report = Report::factory()
            ->for($venue, 'venue')
            ->create();

        // Act
        $response = $this->getJson(route('api.reports.show', ['uuid' => $report->uuid]));

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'uuid',
                'venue_uuid',
                'incident_date',
                'time_of_day',
                'description',
                'formatted_date',
                'venue',
                'created_at',
            ])
            ->assertJson([
                'uuid' => $report->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
    });

    it('returns 404 for invalid uuid', function (): void {
        // Act
        $response = $this->getJson(route('api.reports.show', ['uuid' => 'invalid-uuid']));

        // Assert
        $response->assertNotFound()
            ->assertJsonStructure(['message']);
    });
});

describe('ReportController store', function (): void {
    it('creates report with existing venue', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        $data = [
            'venue_uuid' => $venue->uuid,
            'incident_date' => now()->subDays(1)->toDateString(),
            'time_of_day' => 'Night',
            'description' => 'Felt dizzy and disoriented after finishing my drink. Had to leave early.',
        ];

        // Act
        $response = $this->postJson(route('api.reports.store'), $data);

        // Assert
        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'uuid',
                    'venue_uuid',
                    'incident_date',
                    'time_of_day',
                    'description',
                    'venue',
                ],
            ])
            ->assertJson([
                'message' => 'Report created successfully',
                'data' => [
                    'venue_uuid' => $venue->uuid,
                    'time_of_day' => 'Night',
                ],
            ]);

        $this->assertDatabaseHas('reports', [
            'venue_uuid' => $venue->uuid,
            'description' => $data['description'],
        ]);
    });

    it('creates report and new venue when venue name and city provided', function (): void {
        // Arrange
        $data = [
            'venue_name' => 'The Testing Venue',
            'venue_city' => 'London',
            'venue_address' => '123 Test Street',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'incident_date' => now()->subDays(2)->toDateString(),
            'time_of_day' => 'Evening',
            'description' => 'My friend became suddenly very unwell after one drink. We had to call for help.',
        ];

        // Act
        $response = $this->postJson(route('api.reports.store'), $data);

        // Assert
        $response->assertCreated();

        $this->assertDatabaseHas('venues', [
            'name' => 'The Testing Venue',
            'city' => 'London',
        ]);

        $this->assertDatabaseHas('reports', [
            'description' => $data['description'],
        ]);
    });

    it('uses existing venue when name and city match', function (): void {
        // Arrange
        $existingVenue = Venue::factory()->create([
            'name' => 'The Duplicate Venue',
            'city' => 'Manchester',
        ]);

        $data = [
            'venue_name' => 'The Duplicate Venue',
            'venue_city' => 'Manchester',
            'incident_date' => now()->subDays(1)->toDateString(),
            'time_of_day' => 'Night',
            'description' => 'Noticed my drink tasted strange and felt effects that were unusual for the amount consumed.',
        ];

        // Act
        $response = $this->postJson(route('api.reports.store'), $data);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'venue_uuid' => $existingVenue->uuid,
                ],
            ]);

        $venueCount = Venue::where('name', 'The Duplicate Venue')
            ->where('city', 'Manchester')
            ->count();

        expect($venueCount)->toBe(1);
    });

    it('validates required fields', function (): void {
        // Act
        $response = $this->postJson(route('api.reports.store'), []);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'venue_name',
                'incident_date',
                'time_of_day',
                'description',
            ]);
    });

    it('validates incident date is not in future', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        $data = [
            'venue_uuid' => $venue->uuid,
            'incident_date' => now()->addDays(1)->toDateString(),
            'time_of_day' => 'Night',
            'description' => 'This should fail because the date is in the future.',
        ];

        // Act
        $response = $this->postJson(route('api.reports.store'), $data);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['incident_date']);
    });

    it('validates description minimum length', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        $data = [
            'venue_uuid' => $venue->uuid,
            'incident_date' => now()->subDays(1)->toDateString(),
            'time_of_day' => 'Night',
            'description' => 'Too short',
        ];

        // Act
        $response = $this->postJson(route('api.reports.store'), $data);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    });

    it('rejects description with email address', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        $data = [
            'venue_uuid' => $venue->uuid,
            'incident_date' => now()->subDays(1)->toDateString(),
            'time_of_day' => 'Night',
            'description' => 'Contact me at test@example.com for more information about this incident.',
        ];

        // Act
        $response = $this->postJson(route('api.reports.store'), $data);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description'])
            ->assertJson([
                'errors' => [
                    'description' => [
                        'Description contains personal information. Please remove names, emails, and phone numbers.',
                    ],
                ],
            ]);
    });

    it('rejects description with phone number', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        $data = [
            'venue_uuid' => $venue->uuid,
            'incident_date' => now()->subDays(1)->toDateString(),
            'time_of_day' => 'Night',
            'description' => 'Call me on 07700 900123 if you need more details about what happened.',
        ];

        // Act
        $response = $this->postJson(route('api.reports.store'), $data);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    });

    it('validates time of day is valid enum value', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        $data = [
            'venue_uuid' => $venue->uuid,
            'incident_date' => now()->subDays(1)->toDateString(),
            'time_of_day' => 'InvalidTimeOfDay',
            'description' => 'This should fail due to invalid time of day value.',
        ];

        // Act
        $response = $this->postJson(route('api.reports.store'), $data);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['time_of_day']);
    });

    it('validates latitude is within bounds', function (): void {
        // Arrange
        $data = [
            'venue_name' => 'Test Venue',
            'venue_city' => 'London',
            'latitude' => 100.0,
            'longitude' => 0.0,
            'incident_date' => now()->subDays(1)->toDateString(),
            'time_of_day' => 'Night',
            'description' => 'This should fail due to invalid latitude value.',
        ];

        // Act
        $response = $this->postJson(route('api.reports.store'), $data);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);
    });

    it('validates longitude is within bounds', function (): void {
        // Arrange
        $data = [
            'venue_name' => 'Test Venue',
            'venue_city' => 'London',
            'latitude' => 51.5074,
            'longitude' => 200.0,
            'incident_date' => now()->subDays(1)->toDateString(),
            'time_of_day' => 'Night',
            'description' => 'This should fail due to invalid longitude value.',
        ];

        // Act
        $response = $this->postJson(route('api.reports.store'), $data);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['longitude']);
    });
});
