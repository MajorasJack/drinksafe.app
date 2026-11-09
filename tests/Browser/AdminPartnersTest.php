<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Partners\Models\PartnerLink;
use DrinkSafe\Venues\Models\Venue;

it('switches a partner link off from the partners page', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    PartnerLink::query()->delete();
    $partnerLink = PartnerLink::factory()->create();

    $page = visit('/admin/partners');

    $page->click('Switch off')
        ->wait(1)
        ->assertSee('Switch on')
        ->assertNoJavaScriptErrors();
    expect($partnerLink->fresh()->active)->toBeFalse();
});

it('saves an edited partner destination', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    PartnerLink::query()->delete();
    $partnerLink = PartnerLink::factory()->create();

    $page = visit('/admin/partners');

    $page->fill(sprintf('destination-%s', $partnerLink->code), '/venues')
        ->click('Save')
        ->wait(1)
        ->assertNoJavaScriptErrors();
    expect($partnerLink->fresh())->destination->toBe('/venues')->active->toBeTrue();
});

it('counts the cities the admin types', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    $city = fake()->unique()->lexify('Zq?????');
    Venue::factory()->create(['city' => $city]);

    $page = visit('/admin/coverage');

    $page->fill('cities', $city)
        ->click('Count')
        ->wait(1)
        ->assertSee($city)
        ->assertDontSee('Edinburgh')
        ->assertNoJavaScriptErrors();
});
