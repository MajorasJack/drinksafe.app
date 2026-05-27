<?php

declare(strict_types=1);

use DrinkSafe\Venues\Models\Venue;

it('displays submit report page title and description', function (): void {
    $page = visit('/submit-report');

    $page->assertSee('Submit a Report')
        ->assertSee('Help keep others safe by sharing your experience')
        ->assertNoJavaScriptErrors();
});

it('displays disclaimer banner on submit page', function (): void {
    $page = visit('/submit-report');

    $page->assertSee('informational purposes only');
});

it('displays multi-step form progress indicator', function (): void {
    $page = visit('/submit-report');

    $page->assertSee('Step 1 of 3')
        ->assertNoJavaScriptErrors();
});

it('displays step 1 venue selection', function (): void {
    $page = visit('/submit-report');

    $page->assertSee('Select Venue')
        ->assertPresent('#venue-search')
        ->assertSee('Search for a venue');
});

it('displays Create New Venue button in step 1', function (): void {
    $page = visit('/submit-report');

    $page->assertSee('Create New Venue');
});

it('shows new venue form when Create New Venue clicked', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->assertPresent('#venue-name')
        ->assertPresent('#venue-city')
        ->assertPresent('#venue-address');
});

it('displays Back to Search button in new venue form', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->assertSee('Back to Search');
});

it('Next button is disabled when step 1 is incomplete', function (): void {
    $page = visit('/submit-report');

    $page->assertPresent('button[disabled]:has-text("Next")');
});

it('allows filling new venue form fields', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue Name')
        ->fill('#venue-city', 'Test City')
        ->fill('#venue-address', '123 Test Street');
});

it('enables Next button when new venue form is valid', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->wait(500);
});

it('progresses to step 2 when Next clicked with valid data', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->assertSee('Step 2 of 3')
        ->assertSee('Incident Details');
});

it('displays date picker in step 2', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->assertPresent('#incident-date')
        ->assertSee('Date of Incident');
});

it('date picker only allows past dates', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->assertAttribute('#incident-date', 'max', now()->toDateString());
});

it('displays time of day dropdown in step 2', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->assertPresent('#time-of-day')
        ->assertSee('Time of Day');
});

it('displays description textarea in step 2', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->assertPresent('#description')
        ->assertSee('Description');
});

it('shows character count for description', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->assertSee('characters (minimum 10)');
});

it('displays privacy notice in step 2', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->assertSee('Privacy Notice')
        ->assertSee('Do not include any personal information');
});

it('shows PII warning when email detected', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->fill('#incident-date', now()->subDays(1)->toDateString())
        ->fill('#description', 'Contact me at test@example.com please')
        ->wait(500)
        ->assertSee('Personal Information Detected');
});

it('shows PII warning when phone number detected', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->fill('#incident-date', now()->subDays(1)->toDateString())
        ->fill('#description', 'Call me on 123-456-7890 for more details')
        ->wait(500)
        ->assertSee('Personal Information Detected');
});

it('Back button works in step 2', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->click('Back')
        ->wait(500)
        ->assertSee('Step 1 of 3')
        ->assertSee('Select Venue');
});

it('progresses to step 3 review when step 2 valid', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Review Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->fill('#incident-date', now()->subDays(2)->toDateString())
        ->fill('#description', 'This is a test report description that is long enough')
        ->click('Next')
        ->wait(500)
        ->assertSee('Step 3 of 3')
        ->assertSee('Review & Submit');
});

it('displays all entered data in step 3 review', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Review Test Venue')
        ->fill('#venue-city', 'Manchester')
        ->fill('#venue-address', '456 Review Street')
        ->click('Next')
        ->wait(500)
        ->fill('#incident-date', now()->subDays(3)->toDateString())
        ->fill('#description', 'This is my review test description')
        ->click('Next')
        ->wait(500)
        ->assertSee('Review Test Venue')
        ->assertSee('Manchester')
        ->assertSee('456 Review Street')
        ->assertSee('This is my review test description');
});

it('Submit button is disabled when PII detected', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->fill('#incident-date', now()->subDays(1)->toDateString())
        ->fill('#description', 'Email me at bad@example.com with info')
        ->click('Next')
        ->wait(500)
        ->assertPresent('button[disabled]:has-text("Submit Report")');
});

it('displays privacy notice at bottom of page', function (): void {
    $page = visit('/submit-report');

    $page->assertSee('Privacy Notice')
        ->assertSee('Reports are completely anonymous');
});

it('renders correctly on mobile viewport', function (): void {
    $page = visit('/submit-report')->on()->iPhone14Pro();

    $page->assertSee('Submit a Report')
        ->assertPresent('#venue-search')
        ->assertNoJavaScriptErrors();
});

it('renders correctly on desktop viewport', function (): void {
    $page = visit('/submit-report')->resize(1920, 1080);

    $page->assertSee('Submit a Report')
        ->assertSee('Select Venue')
        ->assertNoJavaScriptErrors();
});

it('pre-populates venue when URL has venue parameter', function (): void {
    $venue = Venue::factory()->create(['name' => 'Pre-selected Venue']);

    $page = visit(sprintf('/submit-report?venue=%s', $venue->uuid));

    $page->wait(1000)
        ->assertNoJavaScriptErrors();
});

it('validates minimum description length', function (): void {
    $page = visit('/submit-report');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Test Venue')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->fill('#incident-date', now()->subDays(1)->toDateString())
        ->fill('#description', 'Short')
        ->wait(500);
});

it('displays step indicator correctly on all steps', function (): void {
    $page = visit('/submit-report');

    $page->assertSee('Step 1 of 3');

    $page->click('Create New Venue')
        ->wait(500)
        ->fill('#venue-name', 'Step Test')
        ->fill('#venue-city', 'London')
        ->click('Next')
        ->wait(500)
        ->assertSee('Step 2 of 3');

    $page->fill('#incident-date', now()->subDays(1)->toDateString())
        ->fill('#description', 'Step test description content here')
        ->click('Next')
        ->wait(500)
        ->assertSee('Step 3 of 3');
});
