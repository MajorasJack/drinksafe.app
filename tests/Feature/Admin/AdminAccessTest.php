<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

dataset('admin pages', ['admin.dashboard', 'admin.reports.index', 'admin.reports.search']);

it('redirects guests to login', function (string $routeName): void {
    $response = $this->get(route($routeName));

    $response->assertRedirect(route('login'));
})->with('admin pages');

it('forbids users who are not admins', function (string $routeName): void {
    $this->actingAs(User::factory()->withTwoFactor()->create());

    $response = $this->getJson(route($routeName));

    $response->assertForbidden();
})->with('admin pages');

it('forbids users who are not admins even without two-factor', function (string $routeName): void {
    $this->actingAs(User::factory()->create());

    $response = $this->getJson(route($routeName));

    $response->assertForbidden();
})->with('admin pages');

it('sends an admin without confirmed two-factor to set it up', function (string $routeName): void {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route($routeName));

    $response->assertRedirect(route('security.edit'))
        ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'two-factor'));
})->with('admin pages');

it('refuses a JSON request from an admin without confirmed two-factor', function (string $routeName): void {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->getJson(route($routeName));

    $response->assertForbidden();
})->with('admin pages');

it('lets a confirmed admin in', function (string $routeName): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

    $response = $this->getJson(route($routeName));

    $response->assertOk();
})->with('admin pages');

it('shares whether the user is an admin', function (string $routeName): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

    $response = $this->get(route($routeName));

    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('auth.user.is_admin', true));
})->with('admin pages');

it('shares only the admin navigation items whose routes exist', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

    $response = $this->get(route('admin.dashboard'));

    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('admin.nav', 3)
        ->where('admin.nav.0.name', 'admin.dashboard')
        ->where('admin.nav.0.href', '/admin')
        ->where('admin.nav.1.name', 'admin.reports.index')
        ->where('admin.nav.2.name', 'admin.reports.search'));
});

it('shares no admin navigation with users who are not admins', function (): void {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('profile.edit'));

    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('admin.nav', []));
});

it('keeps the settings pages reachable for an admin without two-factor', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->getJson(route('profile.edit'));

    $response->assertOk();
});

it('lets an admin without two-factor reach the security settings to set it up', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->withSession(['auth.password_confirmed_at' => time()])->getJson(route('security.edit'));

    $response->assertOk();
});

it('sends admins to the admin dashboard after login', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password']);

    $response->assertRedirect('/admin');
});
