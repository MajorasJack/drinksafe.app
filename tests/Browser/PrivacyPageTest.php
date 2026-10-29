<?php

declare(strict_types=1);

it('says that partner QR scans are only counted per partner per day', function (): void {
    $page = visit('/privacy');

    $page->assertSee("If you scan a QR code from one of our partners, we add one to that partner's total for the day. That's all we count: nothing about you, your device or where you came from is stored.")
        ->assertNoJavaScriptErrors();
});

it('shows when the policy was last updated', function (): void {
    $page = visit('/privacy');

    $page->assertSee('Last updated: October 2026');
});
