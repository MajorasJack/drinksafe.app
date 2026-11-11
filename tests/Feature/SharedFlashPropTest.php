<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('sends the flash messages with every partial reload', function (): void {
    $this->actingAs(User::factory()->create());

    $response = $this->getJson('/settings/profile');

    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->reloadOnly('seo', fn (AssertableInertia $reload): AssertableInertia => $reload->has('flash')));
});
