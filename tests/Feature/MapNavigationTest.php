<?php

declare(strict_types=1);

use DrinkSafe\Venues\Models\Venue;

it('renders map page with venues', function (): void {
    Venue::factory()->count(3)->create();

    $response = $this->getJson('/map');

    $response->assertOk();
});

it('renders venue detail page', function (): void {
    $venue = Venue::factory()->create([
        'name' => 'Test Venue',
        'city' => 'London',
    ]);

    $response = $this->getJson(sprintf('/venues/%s', $venue->slug));

    $response->assertOk();
});

it('venue detail page contains venue name', function (): void {
    $venue = Venue::factory()->create([
        'name' => 'Phoenix Wine Bar',
        'city' => 'London',
    ]);

    $response = $this->getJson(sprintf('/venues/%s', $venue->slug));

    $response->assertOk();
});

it('returns 404 for non-existent venue slug', function (): void {
    $response = $this->getJson('/venues/non-existent-venue-slug');

    $response->assertNotFound();
});
