<?php

declare(strict_types=1);

it('says a new venue is added once its report has been reviewed and published', function (): void {
    $page = visit('/contact');

    $page->click("How can I add a venue that isn't listed?")
        ->assertSee("If a venue isn't in our database, it's added once your report has been reviewed and published.")
        ->assertDontSee('added automatically')
        ->assertNoJavaScriptErrors();
});

it('says we have no way to identify who submitted a report', function (): void {
    $page = visit('/contact');

    $page->click('Is my report truly anonymous?')
        ->assertSee('We do not collect any personal information, require accounts, or store IP addresses linked to reports. We have no way to identify who submitted a report.');
});
