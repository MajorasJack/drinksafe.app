<?php

declare(strict_types=1);

use App\Models\User;

it('sends an admin without two-factor from a full load of the admin area to the security notice', function (): void {
    $this->actingAs(User::factory()->admin()->create())
        ->withSession(['auth.password_confirmed_at' => time()]);

    $page = visit('/admin/reports');

    $page->assertPathIs('/settings/security')
        ->assertSee('Set up two-factor authentication to open the admin area. Click Enable 2FA below, then scan the code with an authenticator app.')
        ->assertNoJavaScriptErrors();
});

it('shows a confirmed admin the way back to the admin area from settings', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

    $page = visit('/settings/profile');

    $page->click('nav[aria-label="Admin"] >> text=Review queue')
        ->wait(1)
        ->assertPathIs('/admin/reports')
        ->assertNoJavaScriptErrors();
});

it('shows a session flash message once as a toast on a full page load', function (): void {
    $message = fake()->sentence();
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create())
        ->withSession(['success' => $message]);

    $page = visit('/admin');

    $page->wait(1)
        ->assertSee($message)
        ->assertScript("document.querySelectorAll('[data-sonner-toast]').length", 1)
        ->assertNoJavaScriptErrors();
});
