<?php

declare(strict_types=1);

use DrinkSafe\Venues\Models\Venue;

describe('sitemap.xml', function (): void {
    it('returns a successful response', function (): void {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
    });

    it('returns an application/xml content type', function (): void {
        $response = $this->get('/sitemap.xml');

        expect($response->headers->get('Content-Type'))->toContain('application/xml');
    });

    it('renders a valid urlset element', function (): void {
        $response = $this->get('/sitemap.xml');

        expect($response->getContent())->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
    });

    it('includes the home url', function (): void {
        $response = $this->get('/sitemap.xml');

        expect($response->getContent())->toContain(sprintf('<loc>%s</loc>', route('home')));
    });

    it('includes a seeded venue url', function (): void {
        $venue = Venue::factory()->create();

        $response = $this->get('/sitemap.xml');

        expect($response->getContent())->toContain(route('venues.show', $venue->slug));
    });
});

describe('robots.txt', function (): void {
    it('disallows the api path', function (): void {
        expect(file_get_contents(public_path('robots.txt')))->toContain('Disallow: /api');
    });

    it('references the sitemap', function (): void {
        expect(file_get_contents(public_path('robots.txt')))->toContain('Sitemap:');
    });
});
