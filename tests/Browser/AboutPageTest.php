<?php

declare(strict_types=1);

it('displays about page title and description', function (): void {
    $page = visit('/about');

    $page->assertSee('About DrinkSafe')
        ->assertSee('Empowering communities to stay informed')
        ->assertNoJavaScriptErrors();
});

it('displays mission section', function (): void {
    $page = visit('/about');

    $page->assertSee('Our Mission')
        ->assertSee('DrinkSafe was created to address the serious issue of drink spiking')
        ->assertNoJavaScriptErrors();
});

it('displays How to Use DrinkSafe section', function (): void {
    $page = visit('/about');

    $page->assertSee('How to Use DrinkSafe')
        ->assertSee('Search Venues')
        ->assertSee('View on Map')
        ->assertSee('Report Incidents');
});

it('displays Important Safety Information section', function (): void {
    $page = visit('/about');

    $page->assertSee('Important Safety Information')
        ->assertSee('informational platform only')
        ->assertSee('not a substitute for reporting crimes to the police');
});

it('displays emergency contact numbers', function (): void {
    $page = visit('/about');

    $page->assertSee('Emergency Services:')
        ->assertSee('UK Emergency: 999')
        ->assertSee('UK Non-Emergency: 101')
        ->assertSee('Crimestoppers (Anonymous): 0800 555 111');
});

it('displays Staying Safe section with tips', function (): void {
    $page = visit('/about');

    $page->assertSee('Staying Safe')
        ->assertSee('Never leave your drink unattended')
        ->assertSee('Watch your drink being prepared')
        ->assertSee('If your drink tastes or smells unusual');
});

it('displays Resources & Support section', function (): void {
    $page = visit('/about');

    $page->assertSee('Resources & Support')
        ->assertSee('Drink Spiking Support')
        ->assertSee('Sexual Assault Support')
        ->assertSee('Mental Health Support');
});

it('displays support organisation names and numbers', function (): void {
    $page = visit('/about');

    $page->assertSee('Stamp Out Spiking')
        ->assertSee('Victim Support')
        ->assertSee('Rape Crisis')
        ->assertSee('The Survivors Trust')
        ->assertSee('Samaritans')
        ->assertSee('Mind');
});

it('displays support phone numbers correctly', function (): void {
    $page = visit('/about');

    $page->assertSee('08 08 16 89 111')
        ->assertSee('0808 500 2222')
        ->assertSee('08088 010 818')
        ->assertSee('116 123')
        ->assertSee('0300 123 3393');
});

it('displays external link to Stamp Out Spiking', function (): void {
    $page = visit('/about');

    $page->assertSeeLink('stampoutspiking.org');
});

it('displays numbered staying safe tips', function (): void {
    $page = visit('/about');

    $page->assertSee('1')
        ->assertSee('2')
        ->assertSee('3')
        ->assertSee('4')
        ->assertSee('5');
});

it('displays how to use cards with icons', function (): void {
    $page = visit('/about');

    $page->assertSee('Use our search tool to find venues')
        ->assertSee('Browse venues on an interactive map')
        ->assertSee('Submit anonymous reports about incidents');
});

it('displays questions and feedback section', function (): void {
    $page = visit('/about');

    $page->assertSee('Have questions or feedback about DrinkSafe?')
        ->assertSee('community-driven platform');
});

it('renders correctly on mobile viewport', function (): void {
    $page = visit('/about')->on()->iPhone14Pro();

    $page->assertSee('About DrinkSafe')
        ->assertSee('Our Mission')
        ->assertNoJavaScriptErrors();
});

it('renders correctly on desktop viewport', function (): void {
    $page = visit('/about')->resize(1920, 1080);

    $page->assertSee('About DrinkSafe')
        ->assertSee('How to Use DrinkSafe')
        ->assertSee('Resources & Support')
        ->assertNoJavaScriptErrors();
});

it('displays three column grid for How to Use on desktop', function (): void {
    $page = visit('/about')->resize(1920, 1080);

    $page->assertSee('Search Venues')
        ->assertSee('View on Map')
        ->assertSee('Report Incidents')
        ->assertNoJavaScriptErrors();
});

it('safety warning section has amber styling', function (): void {
    $page = visit('/about');

    $page->assertPresent('.border-amber-200')
        ->assertNoJavaScriptErrors();
});

it('all sections are properly structured with cards', function (): void {
    $page = visit('/about');

    $page->assertSee('Our Mission')
        ->assertSee('How to Use DrinkSafe')
        ->assertSee('Important Safety Information')
        ->assertSee('Staying Safe')
        ->assertSee('Resources & Support')
        ->assertNoJavaScriptErrors();
});

it('anchors the police contact guide at #police', function (): void {
    $page = visit('/about#police');

    $page->assertPresent('#police')
        ->assertSeeIn('#police', 'UK Non-Emergency: 101');
});
