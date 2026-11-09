<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Shared\Services\PublicCacheVersion;

it('shows the review queue on the admin dashboard', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    Report::factory()->pending()->count(3)->create();

    $page = visit('/admin');

    $page->assertSeeIn('[data-test="pending-count"]', '3')
        ->assertSee('Reports received per day')
        ->assertNoJavaScriptErrors();
});

it('refreshes the public caches from the admin dashboard', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    $versionBefore = app(PublicCacheVersion::class)->current();

    $page = visit('/admin');

    $page->click('Refresh public caches')
        ->wait(1)
        ->assertSee('Public caches refreshed.')
        ->assertNoJavaScriptErrors();
    expect(app(PublicCacheVersion::class)->current())->toBe($versionBefore + 1);
});
