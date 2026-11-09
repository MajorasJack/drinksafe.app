<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Scopes\PublishedScope;
use DrinkSafe\Shared\Controllers\SitemapController;
use DrinkSafe\Shared\Services\PublicCacheVersion;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
});

describe('AdminVenueController index', function (): void {
    it('lists published and unpublished venues', function (): void {
        $published = Venue::factory()->create(['name' => sprintf('A %s', fake()->unique()->company())]);
        $unpublished = Venue::factory()->unpublished()->create(['name' => sprintf('B %s', fake()->unique()->company())]);

        $response = $this->get(route('admin.venues.index'));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/Venues')
            ->where('total', 2)
            ->where('venues.0.uuid', $published->uuid)
            ->where('venues.1.uuid', $unpublished->uuid));
    });

    it('filters to unpublished venues with no pending report', function (): void {
        $orphaned = Venue::factory()->unpublished()->create();
        Report::factory()->for($orphaned, 'venue')->rejected()->create();
        Report::factory()->for(Venue::factory()->unpublished(), 'venue')->pending()->create();
        Venue::factory()->create();

        $response = $this->get(route('admin.venues.index', ['state' => 'orphaned']));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('venues', 1)
            ->where('venues.0.uuid', $orphaned->uuid));
    });

    it('filters to unpublished venues', function (): void {
        $unpublished = Venue::factory()->unpublishedByAdmin()->create();
        Venue::factory()->create();

        $response = $this->get(route('admin.venues.index', ['state' => 'unpublished']));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('venues', 1)
            ->where('venues.0.uuid', $unpublished->uuid));
    });

    it('filters by part of the name and by city', function (): void {
        $city = fake()->unique()->city();
        $nameWord = fake()->unique()->lexify('zq??????');
        $matching = Venue::factory()->create(['name' => sprintf('The %s Arms', $nameWord), 'city' => $city]);
        Venue::factory()->create(['name' => sprintf('The %s Arms', $nameWord), 'city' => fake()->unique()->city()]);
        Venue::factory()->create(['city' => $city]);

        $response = $this->get(route('admin.venues.index', ['q' => $nameWord, 'city' => $city]));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('venues', 1)
            ->where('venues.0.uuid', $matching->uuid));
    });

    it('rejects an unknown state filter', function (): void {
        $response = $this->getJson(route('admin.venues.index', ['state' => fake()->word()]));

        $response->assertUnprocessable()->assertJsonValidationErrors('state');
    });
});

describe('AdminVenueController show', function (): void {
    it('shows an unpublished venue with its reports in every status', function (): void {
        $venue = Venue::factory()->unpublishedByAdmin()->create();
        Report::factory()->for($venue, 'venue')->rejected()->create();
        Report::factory()->for($venue, 'venue')->pending()->create();

        $response = $this->get(route('admin.venues.show', $venue->uuid));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/VenueShow')
            ->where('venue.uuid', $venue->uuid)
            ->has('reports', 2)
            ->has('reports.0.venue')
            ->has('reports.0.would_empty_venue'));
    });

    it('returns not found for an unknown venue', function (): void {
        $response = $this->getJson(route('admin.venues.show', fake()->uuid()));

        $response->assertNotFound();
    });
});

describe('AdminVenueController update', function (): void {
    it('updates a venue and keeps its slug', function (): void {
        $venue = Venue::factory()->unpublished()->create();
        $details = [
            'name' => fake()->unique()->company(),
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ];

        $response = $this->patchJson(route('admin.venues.update', $venue->uuid), $details);

        $response->assertRedirect(route('admin.venues.show', $venue->uuid));
        expect(Venue::withoutGlobalScope(PublishedVenueScope::class)->find($venue->uuid))
            ->name->toBe($details['name'])
            ->city->toBe($details['city'])
            ->address->toBe($details['address'])
            ->slug->toBe($venue->slug);
    });

    it('refuses a name and city that another venue already has', function (): void {
        $otherVenue = Venue::factory()->unpublished()->create();
        $venue = Venue::factory()->create();

        $response = $this->patchJson(route('admin.venues.update', $venue->uuid), [
            'name' => $otherVenue->name,
            'city' => $otherVenue->city,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'name' => sprintf('%s in %s already exists', $otherVenue->name, $otherVenue->city),
        ]);
    });

    it('lets a venue keep its own name and city', function (): void {
        $venue = Venue::factory()->create();

        $response = $this->patchJson(route('admin.venues.update', $venue->uuid), [
            'name' => $venue->name,
            'city' => $venue->city,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]);

        $response->assertRedirect(route('admin.venues.show', $venue->uuid));
    });

    it('rejects coordinates outside valid latitude and longitude', function (): void {
        $venue = Venue::factory()->create();

        $response = $this->patchJson(route('admin.venues.update', $venue->uuid), [
            'name' => $venue->name,
            'city' => $venue->city,
            'latitude' => fake()->randomFloat(4, 90.0001, 180),
            'longitude' => fake()->randomFloat(4, -360, -180.0001),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['latitude', 'longitude']);
    });

    it('clears the safety score and heatmap version when a venue moves', function (): void {
        $venue = Venue::factory()->create();
        Cache::put(sprintf('venue:safety_score:%s', $venue->uuid), fake()->numberBetween(0, 100));

        $this->patchJson(route('admin.venues.update', $venue->uuid), [
            'name' => $venue->name,
            'city' => $venue->city,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]);

        expect(Cache::has(sprintf('venue:safety_score:%s', $venue->uuid)))->toBeFalse()
            ->and(app(PublicCacheVersion::class)->current())->toBe(2);
    });

    it('leaves the caches alone when the venue does not move', function (): void {
        $venue = Venue::factory()->create();
        Cache::put(sprintf('venue:safety_score:%s', $venue->uuid), fake()->numberBetween(0, 100));

        $this->patchJson(route('admin.venues.update', $venue->uuid), [
            'name' => fake()->unique()->company(),
            'city' => $venue->city,
            'latitude' => $venue->latitude,
            'longitude' => $venue->longitude,
        ]);

        expect(Cache::has(sprintf('venue:safety_score:%s', $venue->uuid)))->toBeTrue()
            ->and(app(PublicCacheVersion::class)->current())->toBe(1);
    });

    it('returns not found when updating an unknown venue', function (): void {
        $response = $this->patchJson(route('admin.venues.update', fake()->uuid()), [
            'name' => fake()->company(),
            'city' => fake()->city(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ]);

        $response->assertNotFound();
    });
});

describe('AdminVenueController publish and unpublish', function (): void {
    it('unpublishes a venue and hides it from the public site', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->for($venue, 'venue')->create();

        $this->postJson(route('admin.venues.unpublish', $venue->uuid));

        $this->getJson(route('venues.show', $venue->slug))->assertNotFound();
    });

    it('marks an unpublished venue as unpublished by the admin', function (): void {
        $venue = Venue::factory()->create();

        $this->postJson(route('admin.venues.unpublish', $venue->uuid));

        expect(Venue::withoutGlobalScope(PublishedVenueScope::class)->find($venue->uuid)->wasUnpublishedByAdmin())->toBeTrue();
    });

    it('clears the safety score and heatmap version when a venue is unpublished', function (): void {
        $venue = Venue::factory()->create();
        Cache::put(sprintf('venue:safety_score:%s', $venue->uuid), fake()->numberBetween(0, 100));

        $this->postJson(route('admin.venues.unpublish', $venue->uuid));

        expect(Cache::has(sprintf('venue:safety_score:%s', $venue->uuid)))->toBeFalse()
            ->and(app(PublicCacheVersion::class)->current())->toBe(2);
    });

    it('publishes a venue without publishing its reports', function (): void {
        $venue = Venue::factory()->unpublishedByAdmin()->create();
        $rejected = Report::factory()->for($venue, 'venue')->rejected()->create();
        $pending = Report::factory()->for($venue, 'venue')->pending()->create();

        $this->postJson(route('admin.venues.publish', $venue->uuid));

        expect(Venue::find($venue->uuid))->not->toBeNull()
            ->and(Report::withoutGlobalScope(PublishedScope::class)->find($rejected->uuid)->status)->toBe(ReportStatus::Rejected)
            ->and(Report::withoutGlobalScope(PublishedScope::class)->find($pending->uuid)->status)->toBe(ReportStatus::Pending);
    });

    it('lets a report be published again once its venue is republished', function (): void {
        $venue = Venue::factory()->unpublishedByAdmin()->create();
        $report = Report::factory()->for($venue, 'venue')->rejected()->create();

        $this->postJson(route('admin.venues.publish', $venue->uuid));
        $response = $this->patchJson(route('admin.reports.update', $report->uuid), ['status' => ReportStatus::Published->value]);

        $response->assertRedirect();
        expect($report->fresh()->status)->toBe(ReportStatus::Published);
    });

    it('returns not found when publishing an unknown venue', function (): void {
        $response = $this->postJson(route('admin.venues.publish', fake()->uuid()));

        $response->assertNotFound();
    });
});

describe('AdminVenueController unpublish and the venue\'s reports', function (): void {
    it('drops an unpublished venue\'s reports from the heatmap', function (): void {
        $venue = Venue::factory()->create(['latitude' => 51.5, 'longitude' => -0.1]);
        Report::factory()->count(3)->for($venue, 'venue')->daysAgo(5)->create();

        $this->postJson(route('admin.venues.unpublish', $venue->uuid));
        $response = $this->getJson(route('api.heatmap', ['north' => 51.6, 'south' => 51.4, 'east' => 0.0, 'west' => -0.2]));

        $response->assertOk()
            ->assertJsonPath('data.points', [])
            ->assertJsonPath('data.total_reports', 0);
    });

    it('puts a republished venue\'s reports back on the heatmap', function (): void {
        $venue = Venue::factory()->create(['latitude' => 51.5, 'longitude' => -0.1]);
        Report::factory()->count(3)->for($venue, 'venue')->daysAgo(5)->create();
        $this->postJson(route('admin.venues.unpublish', $venue->uuid));

        $this->postJson(route('admin.venues.publish', $venue->uuid));
        $response = $this->getJson(route('api.heatmap', ['north' => 51.6, 'south' => 51.4, 'east' => 0.0, 'west' => -0.2]));

        $response->assertOk()->assertJsonPath('data.total_reports', 3);
    });

    it('drops an unpublished venue\'s reports from the temporal analytics', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->count(15)->for($venue, 'venue')->daysAgo(5)->create();

        $this->postJson(route('admin.venues.unpublish', $venue->uuid));
        $response = $this->getJson(route('api.analytics.temporal'));

        $response->assertUnprocessable()->assertJsonPath('actual_count', 0);
    });

    it('drops an unpublished venue\'s reports from the reports API', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->count(2)->for($venue, 'venue')->create();
        $otherReport = Report::factory()->create();

        $this->postJson(route('admin.venues.unpublish', $venue->uuid));
        $response = $this->getJson(route('api.reports.index'));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $otherReport->uuid);
    });

    it('returns not found for a report about an unpublished venue', function (): void {
        $report = Report::factory()->create();

        $this->postJson(route('admin.venues.unpublish', $report->venue_uuid));
        $response = $this->getJson(route('api.reports.show', ['uuid' => $report->uuid]));

        $response->assertNotFound();
    });

    it('leaves an unpublished venue\'s reports out of the home page total', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->count(3)->for($venue, 'venue')->create();
        Report::factory()->count(2)->create();

        $this->postJson(route('admin.venues.unpublish', $venue->uuid));
        $response = $this->get(route('home'));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('stats.total_reports', 2));
    });

    it('serves a republished venue\'s reports from the reports API again', function (): void {
        $venue = Venue::factory()->create();
        $report = Report::factory()->for($venue, 'venue')->create();
        $this->postJson(route('admin.venues.unpublish', $venue->uuid));

        $this->postJson(route('admin.venues.publish', $venue->uuid));
        $response = $this->getJson(route('api.reports.show', ['uuid' => $report->uuid]));

        $response->assertOk()->assertJsonPath('uuid', $report->uuid);
    });

    it('still lists an unpublished venue\'s reports in the admin report search', function (): void {
        $report = Report::factory()->create();

        $this->postJson(route('admin.venues.unpublish', $report->venue_uuid));
        $response = $this->get(route('admin.reports.search'));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('total', 1)
            ->where('reports.0.uuid', $report->uuid));
    });

    it('still counts an unpublished venue\'s published reports on the admin venue list', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->count(2)->for($venue, 'venue')->create();

        $this->postJson(route('admin.venues.unpublish', $venue->uuid));
        $response = $this->get(route('admin.venues.index'));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('venues.0.published_reports_count', 2));
    });

    it('clears the cached sitemap when a venue is unpublished', function (): void {
        $venue = Venue::factory()->create();
        Cache::put(SitemapController::CACHE_KEY, fake()->text());

        $this->postJson(route('admin.venues.unpublish', $venue->uuid));

        expect(Cache::has(SitemapController::CACHE_KEY))->toBeFalse();
    });

    it('clears the cached sitemap when a venue is published', function (): void {
        $venue = Venue::factory()->unpublishedByAdmin()->create();
        Cache::put(SitemapController::CACHE_KEY, fake()->text());

        $this->postJson(route('admin.venues.publish', $venue->uuid));

        expect(Cache::has(SitemapController::CACHE_KEY))->toBeFalse();
    });
});
