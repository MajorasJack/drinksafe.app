<?php

declare(strict_types=1);

use DrinkSafe\Venues\Models\Venue;
use Illuminate\Support\Facades\Cache;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;

beforeEach(function (): void {
    Turnstile::fake();
    Cache::flush();
});

function validReportPayload(Venue $venue, array $overrides = []): array
{
    return array_merge([
        'venue_uuid' => $venue->uuid,
        'incident_date' => now()->subDay()->toDateString(),
        'incident_time' => '21:30',
        'time_of_day' => 'Night',
        'description' => 'A sufficiently detailed description of what happened that night.',
        'cf-turnstile-response' => 'fake-token',
    ], $overrides);
}

it('stores a report with an optional exact incident time', function (): void {
    $venue = Venue::factory()->create();

    $response = $this->postJson('/api/reports', validReportPayload($venue));

    $response->assertCreated()->assertJsonPath('data.incident_time', '21:30');
});

it('stores a report when the optional incident time is omitted', function (): void {
    $venue = Venue::factory()->create();

    $response = $this->postJson(
        '/api/reports',
        validReportPayload($venue, ['incident_time' => null]),
    );

    $response->assertCreated()->assertJsonPath('data.incident_time', null);
});

it('rejects a report with an incident date in the future', function (): void {
    $venue = Venue::factory()->create();

    $response = $this->postJson(
        '/api/reports',
        validReportPayload($venue, [
            'incident_date' => now()->addDay()->toDateString(),
        ]),
    );

    $response->assertUnprocessable()->assertJsonValidationErrors([
        'incident_date' => 'Incident date cannot be in the future.',
    ]);
});

it('rejects a report with a description shorter than the minimum', function (): void {
    $venue = Venue::factory()->create();

    $response = $this->postJson(
        '/api/reports',
        validReportPayload($venue, ['description' => 'Too short here']),
    );

    $response->assertUnprocessable()->assertJsonValidationErrors(['description']);
});

it('rejects a report with a malformed incident time', function (): void {
    $venue = Venue::factory()->create();

    $response = $this->postJson(
        '/api/reports',
        validReportPayload($venue, ['incident_time' => '9pm']),
    );

    $response->assertUnprocessable()->assertJsonValidationErrors(['incident_time']);
});

it('rejects a report whose description contains personal information', function (): void {
    $venue = Venue::factory()->create();

    $response = $this->postJson(
        '/api/reports',
        validReportPayload($venue, [
            'description' => 'Please email me back at someone@example.com about this.',
        ]),
    );

    $response->assertUnprocessable()->assertJsonValidationErrors(['description']);
});
