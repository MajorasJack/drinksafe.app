<?php

declare(strict_types=1);

it('says that partner QR scans are only counted per partner per day', function (): void {
    $page = visit('/privacy');

    $page->assertSee("If you scan a QR code from one of our partners, we add one to that partner's total for the day. That's all we count: nothing about you, your device or where you came from is stored.")
        ->assertNoJavaScriptErrors();
});

it('says that Cloudflare handles every request and checks the report form for bots', function (): void {
    $page = visit('/privacy');

    $page->assertSee('Drink Safe runs behind Cloudflare, which handles every request to the site and sees your IP address as part of doing that.')
        ->assertSee('The report form uses Cloudflare Turnstile to check that it is being sent by a person rather than a bot.');
});

it('says that the scrambled connection address kept for rate limiting expires after about a minute', function (): void {
    $page = visit('/privacy');

    $page->assertSee('To stop abuse, our server keeps a scrambled form of your connection address for about a minute while it limits how many requests can be made, and then it expires.');
});

it('says that nothing else about visitors is stored or logged', function (): void {
    $page = visit('/privacy');

    $page->assertSee('Nothing else about you is stored or logged.');
});

it('does not claim to collect usage statistics', function (): void {
    $page = visit('/privacy');

    $page->assertDontSee('usage statistics')
        ->assertDontSee('analytics data');
});

it('shows when the policy was last updated', function (): void {
    $page = visit('/privacy');

    $page->assertSee('Last updated: November 2026');
});
