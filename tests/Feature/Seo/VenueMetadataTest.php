<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;

describe('venue detail SEO metadata', function (): void {
    it('returns a successful response', function (): void {
        $venue = Venue::factory()->create();

        $this->get(route('venues.show', $venue->slug))->assertOk();
    });

    it('renders the venue name inside the title', function (): void {
        $venue = Venue::factory()->create();

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->toContain(sprintf('<title>%s,', e($venue->name)));
    });

    it('renders the venue name inside the open graph title', function (): void {
        $venue = Venue::factory()->create();

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->toContain(sprintf('<meta property="og:title" content="%s,', e($venue->name)));
    });

    it('renders a canonical url built from the configured app url', function (): void {
        $venue = Venue::factory()->create();

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->toContain(sprintf(
                '<link rel="canonical" href="%s/venues/%s">',
                rtrim((string) config('app.url'), '/'),
                $venue->slug,
            ));
    });

    it('renders the website open graph type', function (): void {
        $venue = Venue::factory()->create();

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->toContain('<meta property="og:type" content="website">');
    });

    it('renders a breadcrumb list json-ld block', function (): void {
        $venue = Venue::factory()->create();

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->toContain('"@type":"BreadcrumbList"');
    });

    it('renders a neutral place json-ld block', function (): void {
        $venue = Venue::factory()->create();

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->toContain('"@type":"Place"');
    });
});

describe('venue schema defamation guard', function (): void {
    it('never emits review schema even when reports exist', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->count(3)->create(['venue_uuid' => $venue->uuid]);

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->not->toContain('"Review"');
    });

    it('never emits aggregate rating schema even when reports exist', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->count(3)->create(['venue_uuid' => $venue->uuid]);

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->not->toContain('AggregateRating');
    });

    it('never emits a rating value even when reports exist', function (): void {
        $venue = Venue::factory()->create();
        Report::factory()->count(3)->create(['venue_uuid' => $venue->uuid]);

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->not->toContain('ratingValue');
    });
});
