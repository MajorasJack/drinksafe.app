<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('HomeController', function (): void {
    it('renders home page successfully', function (): void {
        $response = $this->get(route('home'));

        $response->assertOk();
    });

    it('shows home page with Inertia component', function (): void {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Home')
        );
    });

    it('shows recent reports on home page', function (): void {
        Venue::factory()->count(3)->create()->each(function ($venue): void {
            Report::factory()->count(2)->create([
                'venue_uuid' => $venue->uuid,
            ]);
        });

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('recent_reports', 5)
            ->has('recent_reports.0.uuid')
            ->has('recent_reports.0.description')
            ->has('recent_reports.0.venue')
        );
    });

    it('shows empty reports array when no reports exist', function (): void {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('recent_reports', 0)
        );
    });

    it('limits recent reports to 5 items', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->count(10)->create([
            'venue_uuid' => $venue->uuid,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('recent_reports', 5)
        );
    });

    it('includes stats with venue and report counts', function (): void {
        Venue::factory()->count(3)->create();
        $venue = Venue::first();
        Report::factory()->count(5)->create(['venue_uuid' => $venue->uuid]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('stats')
            ->where('stats.total_venues', 3)
            ->where('stats.total_reports', 5)
        );
    });
});
