<?php

declare(strict_types=1);

use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('AnalyticsController', function (): void {
    it('lists only the cities of published venues', function (): void {
        $published = Venue::factory()->create(['city' => 'Leeds']);
        Venue::factory()->unpublished()->create(['city' => 'Bristol']);

        $response = $this->get(route('analytics'));

        $response->assertOk()->assertInertia(fn ($page) => $page->where('cities', [$published->city]));
    });
});
