<?php

declare(strict_types=1);

it('says the site is free with no ads, paywalls or tracking', function (): void {
    $page = visit('/support');

    $page->assertSee('Drink Safe will always be free to use, with no ads on the site, no paywalls, and no tracking.')
        ->assertNoJavaScriptErrors();
});
