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

it('shows a confirmed admin the admin navigation on the settings pages', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

    $page = visit('/settings/profile');

    $page->click('[data-sidebar="content"] [data-sidebar="menu-button"][href="/admin/reports"]')
        ->wait(1)
        ->assertPathIs('/admin/reports')
        ->assertNoJavaScriptErrors();
});

it('lists every admin page in the sidebar', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

    $page = visit('/admin');

    $page->assertScript(
        "Array.from(document.querySelectorAll('[data-sidebar=\"content\"] a[data-sidebar=\"menu-button\"]')).map((link) => link.textContent.trim())",
        ['Dashboard', 'Review queue', 'Reports', 'Venues', 'Partners', 'Coverage'],
    )->assertNoJavaScriptErrors();
});

it('opens the venues page from the sidebar and marks its link as active', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

    $page = visit('/admin');

    $page->click('[data-sidebar="content"] [data-sidebar="menu-button"][href="/admin/venues"]')
        ->wait(1)
        ->assertPathIs('/admin/venues')
        ->assertAttribute('[data-sidebar="content"] [data-sidebar="menu-button"][href="/admin/venues"]', 'data-active', 'true')
        ->assertAttribute('[data-sidebar="content"] [data-sidebar="menu-button"][href="/admin"]', 'data-active', 'false')
        ->assertNoJavaScriptErrors();
});

it('opens an admin page from the sidebar on a phone', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

    $page = visit('/admin')->on()->mobile()->wait(1);

    $page->click('[data-test="sidebar-trigger"]')
        ->wait(1)
        ->click('[data-mobile="true"] [data-sidebar="menu-button"][href="/admin/partners"]')
        ->wait(1)
        ->assertPathIs('/admin/partners')
        ->assertNoJavaScriptErrors();
});

it('keeps the admin sidebar off the public pages', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

    $page = visit('/');

    $page->assertScript('document.querySelectorAll(\'[data-slot="sidebar"]\').length', 0)
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

it('shows a user who is not an admin the settings without admin navigation', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/settings/profile');

    $page->assertSee('Profile information')
        ->assertScript('document.querySelectorAll(\'[data-sidebar="content"] [data-sidebar="menu-button"]\').length', 0)
        ->assertAttribute('[data-sidebar="header"] a', 'href', '/')
        ->assertNoJavaScriptErrors();
});

it('opens the settings from the user menu in the sidebar', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

    $page = visit('/admin');

    $page->click('[data-test="sidebar-menu-button"]')
        ->click('Settings')
        ->wait(1)
        ->assertPathIs('/settings/profile')
        ->assertNoJavaScriptErrors();
});

it('logs out from the user menu in the sidebar', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

    $page = visit('/admin');

    $page->click('[data-test="sidebar-menu-button"]')
        ->click('[data-test="logout-button"]')
        ->wait(1)
        ->assertPathIs('/')
        ->assertNoJavaScriptErrors();
    $this->assertGuest();
});

it('takes a confirmed admin from the security settings to the admin area', function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create())
        ->withSession(['auth.password_confirmed_at' => time()]);

    $page = visit('/settings/security');

    $page->assertSee('Go to the admin area')
        ->click('[data-test="admin-area-link"]')
        ->wait(1)
        ->assertPathIs('/admin')
        ->assertNoJavaScriptErrors();
});

it('shows a user who is not an admin no admin area link in the security settings', function (): void {
    $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()]);

    $page = visit('/settings/security');

    $page->assertSee('Two-factor authentication')
        ->assertMissing('[data-test="admin-area-link"]')
        ->assertNoJavaScriptErrors();
});
