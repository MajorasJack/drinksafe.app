<?php

declare(strict_types=1);

it('displays the partners page title', function (): void {
    $page = visit('/partners');

    $page->assertSee('For venues and partners')
        ->assertSee('how reports are handled')
        ->assertNoJavaScriptErrors();
});

it('explains what Drink Safe is', function (): void {
    $page = visit('/partners');

    $page->assertSee('What Drink Safe is')
        ->assertSee('no ads on the site, no paywalls, and no data collection')
        ->assertSee('not a substitute for reporting crimes to the police');
});

it('displays emergency contact numbers', function (): void {
    $page = visit('/partners');

    $page->assertSee('UK Emergency: 999')
        ->assertSee('UK Non-Emergency: 101')
        ->assertSee('Crimestoppers (Anonymous): 0800 555 111');
});

it('explains that a report is not an accusation', function (): void {
    $page = visit('/partners');

    $page->assertSee('A report is not an accusation')
        ->assertSee('Every report is reviewed by a person before it is published.');
});

it('says that information provided may not be verified', function (): void {
    $page = visit('/partners');

    $page->assertSee('Information provided may not be verified or accurate.');
});

it('describes each step a report goes through', function (): void {
    $page = visit('/partners');

    $page->assertSee('without an account and without giving their name')
        ->assertSee('If the description includes an email address or phone number, the report is turned away')
        ->assertSee('It does not appear anywhere on the site yet.')
        ->assertSee('either publishes it or rejects it')
        ->assertSee('the venue stays hidden as well until its first report is published')
        ->assertSee('A published report can still be taken down later.');
});

it('tells a venue what to do if it disagrees with a report', function (): void {
    $page = visit('/partners');

    $page->assertSee('If your venue disagrees with a published report')
        ->assertSee("with a link to your venue's page on Drink Safe and the date shown on the report")
        ->assertSee('remove anything that breaks our rules or names a person')
        ->assertPresent('a[href="/terms"]');
});

it('explains where to put material up with permission', function (): void {
    $page = visit('/partners');

    $page->assertSee('How to help')
        ->assertSee('Toilets')
        ->assertSee('Welfare, first aid and security desks')
        ->assertSee('Halls of residence')
        ->assertSee("Please only put anything up with the venue's permission.");
});

it('links each printable download', function (string $fileName): void {
    $page = visit('/partners');

    $page->assertSee('Downloads')
        ->assertPresent(sprintf('a[href="/downloads/%s"]', $fileName));
})->with([
    'drink-safe-poster-a4.pdf',
    'drink-safe-poster-a3.pdf',
    'drink-safe-flyer.pdf',
    'drink-safe-sticker-a.pdf',
    'drink-safe-sticker-b.pdf',
]);

it('warns that the stickers are not for an office printer', function (): void {
    $page = visit('/partners');

    $page->assertSee('Set up for a sticker or print shop, with 3mm bleed. Not for an office printer.');
});

it('gives the format and size in each download link', function (): void {
    $page = visit('/partners');

    $page->assertSeeIn('a[href="/downloads/drink-safe-poster-a4.pdf"]', 'Poster, A4 (PDF, 21 KB)');
});

it('shows the contact email as a mailto link', function (): void {
    $page = visit('/partners');

    $page->assertSee('drinksafe@protonmail.com')
        ->assertPresent('a[href="mailto:drinksafe@protonmail.com"]');
});

it('links to the partners page from the footer', function (): void {
    $page = visit('/');

    $page->assertPresent('footer a[href="/partners"]');
});

it('renders correctly on mobile viewport', function (): void {
    $page = visit('/partners')->on()->iPhone14Pro();

    $page->assertSee('For venues and partners')
        ->assertSee('A report is not an accusation')
        ->assertNoJavaScriptErrors();
});
