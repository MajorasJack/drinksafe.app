<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;

it('opens a venue from the venue list', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    $venue = Venue::factory()->unpublished()->create();

    $page = visit('/admin/venues');

    $page->click(sprintf('a[href="/admin/venues/%s"]', $venue->uuid))
        ->wait(1)
        ->assertSee('Edit venue')
        ->assertSee('Never published')
        ->assertNoJavaScriptErrors();
});

it('saves an edited venue name', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    $venue = Venue::factory()->create();
    $newName = fake()->unique()->company();

    $page = visit(sprintf('/admin/venues/%s', $venue->uuid));

    $page->fill('name', $newName)
        ->click('Save venue')
        ->wait(1)
        ->assertNoJavaScriptErrors();
    expect($venue->fresh()->name)->toBe($newName);
});

it('confirms a saved venue edit with a toast', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    $venue = Venue::factory()->create();

    $page = visit(sprintf('/admin/venues/%s', $venue->uuid));

    $page->fill('name', fake()->unique()->company())
        ->click('Save venue')
        ->wait(1)
        ->assertSee('Venue saved.')
        ->assertScript("document.querySelectorAll('[data-sonner-toast]').length", 1)
        ->assertNoJavaScriptErrors();
});

it('asks before unpublishing a venue', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    $venue = Venue::factory()->create();

    $page = visit(sprintf('/admin/venues/%s', $venue->uuid));

    $page->click('Unpublish')
        ->assertSee('Unpublish this venue?')
        ->click('Unpublish the venue')
        ->wait(1)
        ->assertSee('Publish')
        ->assertNoJavaScriptErrors();
    expect(Venue::withoutGlobalScope(PublishedVenueScope::class)->find($venue->uuid)->wasUnpublishedByAdmin())->toBeTrue();
});
