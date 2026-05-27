<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('VenueDetailController', function (): void {
    it('renders venue detail page successfully', function (): void {
        $venue = Venue::factory()->create();

        $response = $this->get(route('venues.show', $venue->slug));

        $response->assertOk();
    });

    it('shows venue detail page with Inertia component', function (): void {
        $venue = Venue::factory()->create();

        $response = $this->get(route('venues.show', $venue->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('VenueDetail')
        );
    });

    it('renders venue detail page with venue data', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->count(3)->create(['venue_uuid' => $venue->uuid]);

        $response = $this->get(route('venues.show', $venue->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('VenueDetail')
            ->has('venue')
            ->where('venue.uuid', $venue->uuid)
            ->where('venue.slug', $venue->slug)
            ->where('venue.name', $venue->name)
            ->has('venue.reports', 3)
        );
    });

    it('throws 404 for non-existent venue slug', function (): void {
        $response = $this->get(route('venues.show', 'non-existent-slug'));

        $response->assertNotFound();
    });

    it('loads venue with all reports', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->count(5)->create(['venue_uuid' => $venue->uuid]);

        $response = $this->get(route('venues.show', $venue->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('VenueDetail')
            ->has('venue.reports', 5)
            ->has('venue.reports.0.uuid')
            ->has('venue.reports.0.description')
            ->has('venue.reports.0.incident_date')
        );
    });

    it('shows venue with empty reports when no reports exist', function (): void {
        $venue = Venue::factory()->create();

        $response = $this->get(route('venues.show', $venue->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('VenueDetail')
            ->has('venue.reports', 0)
        );
    });

    it('generates unique slugs for venues with duplicate names', function (): void {
        $venue1 = Venue::factory()->create(['name' => 'The Red Lion']);
        $venue2 = Venue::factory()->create(['name' => 'The Red Lion']);

        expect($venue1->slug)->toBe('the-red-lion');
        expect($venue2->slug)->toBe('the-red-lion-1');
    });

    it('uses slug for route model binding instead of uuid', function (): void {
        $venue = Venue::factory()->create(['name' => 'Test Venue']);

        $response = $this->get(sprintf('/venues/%s', $venue->slug));

        $response->assertOk();
    });

    it('does not find venue by uuid when using slug route', function (): void {
        $venue = Venue::factory()->create();

        $response = $this->get(sprintf('/venues/%s', $venue->uuid));

        $response->assertNotFound();
    });
});
