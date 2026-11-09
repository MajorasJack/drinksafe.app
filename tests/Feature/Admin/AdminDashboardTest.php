<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\CarbonImmutable;
use DrinkSafe\Partners\Models\PartnerLink;
use DrinkSafe\Partners\Models\PartnerLinkDailyCount;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Shared\Services\PublicCacheVersion;
use DrinkSafe\Venues\Models\Venue;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-09 12:00', 'Europe/London'));
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
});

it('summarises the review queue', function (): void {
    $pendingCount = fake()->numberBetween(1, 5);
    Report::factory()->pending()->create(['created_at' => CarbonImmutable::parse('2026-11-01 09:00', 'UTC')]);
    Report::factory()->pending()->count($pendingCount - 1)->create();
    Report::factory()->count(2)->create();
    Report::factory()->rejected()->create();
    Report::factory()->create(['reviewed_at' => CarbonImmutable::parse('2026-10-10 12:00', 'UTC')]);

    $response = $this->get(route('admin.dashboard'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('admin/Dashboard')
        ->where('summary.pending_count', $pendingCount)
        ->where('summary.oldest_pending_at', '2026-11-01T09:00:00+00:00')
        ->where('summary.published_30d', 2)
        ->where('summary.rejected_30d', 1));
});

it('counts unpublished venues with no pending report', function (): void {
    Venue::factory()->unpublished()->count(2)->create();
    Report::factory()->pending()->for(Venue::factory()->unpublished())->create();
    Venue::factory()->create();

    $response = $this->get(route('admin.dashboard'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('summary.orphaned_venues', 2));
});

it('counts reports received per day for the last 30 days including empty days', function (): void {
    $todayCount = fake()->numberBetween(1, 5);
    Report::factory()->pending()->create(['created_at' => CarbonImmutable::parse('2026-10-11 00:00', 'Europe/London')->utc()]);
    Report::factory()->rejected()->create(['created_at' => CarbonImmutable::parse('2026-10-20 00:30', 'Europe/London')->utc()]);
    Report::factory()->count($todayCount)->create(['created_at' => CarbonImmutable::parse('2026-11-09 08:00', 'Europe/London')->utc()]);
    Report::factory()->create(['created_at' => CarbonImmutable::parse('2026-10-10 23:59', 'Europe/London')->utc()]);

    $response = $this->get(route('admin.dashboard'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('summary.received_per_day', [
            ...collect(range(0, 29))
                ->mapWithKeys(fn (int $offset): array => [CarbonImmutable::parse('2026-10-11')->addDays($offset)->toDateString() => 0])
                ->all(),
            '2026-10-11' => 1,
            '2026-10-20' => 1,
            '2026-11-09' => $todayCount,
        ]));
});

it('lists the top partners including switched-off ones', function (): void {
    PartnerLink::query()->delete();
    $partnerLinks = PartnerLink::factory()->count(6)->create();
    $switchedOff = PartnerLink::factory()->inactive()->create();
    $partnerLinks->each(fn (PartnerLink $partnerLink, int $index): PartnerLinkDailyCount => PartnerLinkDailyCount::factory()
        ->for($partnerLink)
        ->on('2026-11-01')
        ->create(['count' => $index + 1]));
    PartnerLinkDailyCount::factory()->for($switchedOff)->on('2026-10-11')->create(['count' => 100]);
    PartnerLinkDailyCount::factory()->for($partnerLinks->last())->on('2026-10-10')->create(['count' => 1000]);

    $response = $this->get(route('admin.dashboard'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('summary.top_partners', 5)
        ->where('summary.top_partners.0', [
            'code' => $switchedOff->code,
            'label' => $switchedOff->label,
            'destination' => $switchedOff->destination,
            'active' => false,
            'total' => 100,
        ])
        ->where('summary.top_partners.1.code', $partnerLinks->last()->code)
        ->where('summary.top_partners.1.total', 6)
        ->where('summary.top_partners.4.total', 3));
});

it('refreshes the public caches', function (): void {
    $versionBefore = app(PublicCacheVersion::class)->current();

    $response = $this->from(route('admin.dashboard'))->post(route('admin.caches.refresh'));

    $response->assertRedirect(route('admin.dashboard'))->assertSessionHas('success', 'Public caches refreshed.');
    expect(app(PublicCacheVersion::class)->current())->toBe($versionBefore + 1);
});

it('sends old dashboard bookmarks to the admin dashboard', function (): void {
    $response = $this->get('/dashboard');

    $response->assertMovedPermanently()->assertRedirect('/admin');
});
