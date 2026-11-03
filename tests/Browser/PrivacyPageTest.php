<?php

declare(strict_types=1);

it('says that partner QR scans are only counted per partner per day', function (): void {
    $page = visit('/privacy');

    $page->assertSee("If you scan a QR code from one of our partners, we add one to that partner's total for the day. That's all we count: nothing about you, your device or where you came from is stored.")
        ->assertNoJavaScriptErrors();
});

it('says that Cloudflare sees visitors IP addresses and our server never receives them', function (): void {
    $page = visit('/privacy');

    $page->assertSee('Every request to the site goes through Cloudflare, which sees your IP address. Our server never receives your IP address.');
});

it('says that the map images come directly from CARTO', function (): void {
    $page = visit('/privacy');

    $page->assertSee('Your browser downloads them directly from CARTO, which sees your IP address and the area of the map you are looking at.');
});

it('says what Turnstile sends to Cloudflare and that we only see the result', function (): void {
    $page = visit('/privacy');

    $page->assertSee('Turnstile runs in your browser and sends Cloudflare some details about your browser and device to make that check. We only see whether the check passed.');
});

it('says that the three cookies set on every page expire after two hours', function (): void {
    $page = visit('/privacy');

    $page->assertSee('The site sets three cookies on every page, which its forms need to work. They expire after two hours.');
});

it('says that nothing else about visitors is stored or logged by us', function (): void {
    $page = visit('/privacy');

    $page->assertSee('Nothing else about you is stored or logged by us.');
});

it('does not describe a scrambled connection address', function (): void {
    $page = visit('/privacy');

    $page->assertDontSee('scrambled');
});

it('lists the incident time and submission time in the report data', function (): void {
    $page = visit('/privacy');

    $page->assertSee('the time of the incident if you give it, the incident description, and when the report was sent.');
});

it('does not claim reports carry no privacy risk', function (): void {
    $page = visit('/privacy');

    $page->assertDontSee('no privacy risk')
        ->assertSee('We remove anything that names or could identify a person during review, and a published report can still be taken down.');
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
