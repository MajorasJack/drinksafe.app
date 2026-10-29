<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

describe('Partners page', function (): void {
    it('renders the Partners component', function (): void {
        $response = $this->get(route('partners'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Partners'));
    });

    it('passes the partners page title and description', function (): void {
        $response = $this->get(route('partners'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'For Venues and Partners - Drink Safe')
            ->where('seo.description', 'How Drink Safe reviews reports, where to put up our posters and stickers, and free printable downloads for venues, students\' unions and community groups.')
        );
    });

    it('serves every download the page links to', function (string $fileName): void {
        expect(public_path(sprintf('downloads/%s', $fileName)))->toBeFile();
    })->with([
        'drink-safe-poster-a4.pdf',
        'drink-safe-poster-a3.pdf',
        'drink-safe-flyer.pdf',
        'drink-safe-sticker-a.pdf',
        'drink-safe-sticker-b.pdf',
    ]);
});
