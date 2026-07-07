<?php

declare(strict_types=1);

use DrinkSafe\Venues\Models\Venue;

describe('venue metadata XSS escaping', function (): void {
    it('escapes the raw script payload in the html meta context', function (): void {
        $venue = Venue::factory()->create(['name' => 'Rex"><script>alert(1)</script>']);

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->toContain('Rex&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;');
    });

    it('never emits the raw attribute breakout in the html meta context', function (): void {
        $venue = Venue::factory()->create(['name' => 'Rex"><script>alert(1)</script>']);

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->not->toContain('Rex"><script>');
    });

    it('never emits the executable script payload anywhere in the response', function (): void {
        $venue = Venue::factory()->create(['name' => 'Rex"><script>alert(1)</script>']);

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->not->toContain('<script>alert(1)</script>');
    });

    it('hex-escapes the script payload inside the json-ld block', function (): void {
        $venue = Venue::factory()->create(['name' => 'Rex"><script>alert(1)</script>']);

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->toContain('\\u003Cscript\\u003Ealert(1)\\u003C/script\\u003E');
    });

    it('neutralises the closing script tag so the payload cannot break out of any script block', function (): void {
        $venue = Venue::factory()->create(['name' => 'Rex"><script>alert(1)</script>']);

        expect($this->get(route('venues.show', $venue->slug))->getContent())
            ->not->toContain('alert(1)</script>');
    });
});
