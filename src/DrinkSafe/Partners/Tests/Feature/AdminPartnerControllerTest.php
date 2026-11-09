<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\CarbonImmutable;
use DrinkSafe\Partners\Models\PartnerLink;
use DrinkSafe\Partners\Models\PartnerLinkDailyCount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    PartnerLink::query()->delete();
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
});

describe('AdminPartnerController index', function (): void {
    it('totals scans per partner over the last 30 days by default', function (): void {
        $this->travelTo(CarbonImmutable::parse('2026-11-09 12:00', 'Europe/London'));
        $busy = PartnerLink::factory()->create();
        $unused = PartnerLink::factory()->inactive()->create();
        $recentCount = fake()->numberBetween(1, 50);
        PartnerLinkDailyCount::factory()->for($busy)->on('2026-10-11')->create(['count' => $recentCount]);
        PartnerLinkDailyCount::factory()->for($busy)->on('2026-11-09')->create(['count' => 1]);
        PartnerLinkDailyCount::factory()->for($busy)->on('2026-10-10')->create(['count' => 100]);

        $response = $this->get(route('admin.partners.index'));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/Partners')
            ->where('range', ['from' => '2026-10-11', 'to' => '2026-11-09'])
            ->where('partners', [
                ['code' => $busy->code, 'label' => $busy->label, 'destination' => $busy->destination, 'active' => true, 'total' => $recentCount + 1],
                ['code' => $unused->code, 'label' => $unused->label, 'destination' => $unused->destination, 'active' => false, 'total' => 0],
            ]));
    });

    it('totals scans over a chosen range inclusively', function (): void {
        $partnerLink = PartnerLink::factory()->create();
        PartnerLinkDailyCount::factory()->for($partnerLink)->on('2026-09-30')->create(['count' => 100]);
        PartnerLinkDailyCount::factory()->for($partnerLink)->on('2026-10-01')->create(['count' => 4]);
        PartnerLinkDailyCount::factory()->for($partnerLink)->on('2026-10-31')->create(['count' => 6]);
        PartnerLinkDailyCount::factory()->for($partnerLink)->on('2026-11-01')->create(['count' => 100]);

        $response = $this->get(route('admin.partners.index', ['from' => '2026-10-01', 'to' => '2026-10-31']));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('partners.0.total', 10));
    });

    it('rejects a range that ends before it starts', function (): void {
        $response = $this->getJson(route('admin.partners.index', ['from' => '2026-10-02', 'to' => '2026-10-01']));

        $response->assertUnprocessable()->assertJsonValidationErrors(['to' => 'The end date must be on or after the start date.']);
    });

    it('rejects a start date after today when no end date is given', function (): void {
        $response = $this->getJson(route('admin.partners.index', ['from' => today('Europe/London')->addDay()->toDateString()]));

        $response->assertUnprocessable()->assertJsonValidationErrors('from');
    });

    it('shows nothing finer than daily totals', function (): void {
        PartnerLinkDailyCount::factory()->for(PartnerLink::factory())->create();

        $response = $this->get(route('admin.partners.index'));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('partners.0', fn (AssertableInertia $partner): AssertableInertia => $partner
                ->hasAll(['code', 'label', 'destination', 'active', 'total'])));
    });
});

describe('AdminPartnerController update', function (): void {
    it('switches a partner link off', function (): void {
        $partnerLink = PartnerLink::factory()->create();

        $response = $this->patchJson(route('admin.partners.update', $partnerLink->code), ['active' => false]);

        $response->assertRedirect();
        expect($partnerLink->fresh()->active)->toBeFalse();
    });

    it('saves a new label and destination', function (): void {
        $partnerLink = PartnerLink::factory()->create();
        $label = fake()->company();

        $this->patchJson(route('admin.partners.update', $partnerLink->code), ['label' => $label, 'destination' => '/venues']);

        expect($partnerLink->fresh())->label->toBe($label)->destination->toBe('/venues');
    });

    it('refuses an external destination', function (string $destination): void {
        $partnerLink = PartnerLink::factory()->create();

        $response = $this->patchJson(route('admin.partners.update', $partnerLink->code), ['destination' => $destination]);

        $response->assertUnprocessable()->assertJsonValidationErrors('destination');
        expect($partnerLink->fresh()->destination)->toBe('/submit-report');
    })->with(['https://example.com', '//example.com', '/\\example.com']);

    it('leaves the code as it is', function (): void {
        $partnerLink = PartnerLink::factory()->create();

        $this->patchJson(route('admin.partners.update', $partnerLink->code), ['code' => 'changed', 'active' => false]);

        expect($partnerLink->fresh()->code)->toBe($partnerLink->code);
    });

    it('returns not found for a code that does not exist', function (): void {
        $response = $this->patchJson(route('admin.partners.update', fake()->lexify('missing-????')), ['active' => false]);

        $response->assertNotFound();
    });
});
