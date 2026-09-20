<?php

declare(strict_types=1);

use DrinkSafe\Venues\Models\Venue;

it('does not send a preload Link header on the submit report page', function (): void {
    $response = $this->get(route('reports.create'));

    $response->assertOk();
    $response->assertHeaderMissing('Link');
});

it('does not send a preload Link header on the venue detail page', function (): void {
    $venue = Venue::factory()->create();

    $response = $this->get(route('venues.show', $venue));

    $response->assertOk();
    $response->assertHeaderMissing('Link');
});
