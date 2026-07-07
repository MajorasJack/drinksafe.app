<?php

declare(strict_types=1);

dataset('public_pages', [
    'home' => ['home'],
    'map' => ['map'],
    'about' => ['about'],
    'support' => ['support'],
    'analytics' => ['analytics'],
    'privacy' => ['privacy'],
    'terms' => ['terms'],
    'contact' => ['contact'],
]);

dataset('page_titles', [
    'home' => ['home', 'Drink Safe'],
    'map' => ['map', 'Safety Map'],
    'about' => ['about', 'About'],
    'support' => ['support', 'Support'],
    'analytics' => ['analytics', 'Analytics'],
    'privacy' => ['privacy', 'Privacy'],
    'terms' => ['terms', 'Terms'],
    'contact' => ['contact', 'Contact'],
]);

describe('public page SEO metadata', function (): void {
    it('returns a successful response', function (string $routeName): void {
        $this->get(route($routeName))->assertOk();
    })->with('public_pages');

    it('renders a non-empty title', function (string $routeName): void {
        expect($this->get(route($routeName))->getContent())
            ->toMatch('#<title>.+</title>#');
    })->with('public_pages');

    it('renders a route-appropriate title', function (string $routeName, string $expectedFragment): void {
        expect($this->get(route($routeName))->getContent())
            ->toContain($expectedFragment);
    })->with('page_titles');

    it('renders a non-empty meta description', function (string $routeName): void {
        expect($this->get(route($routeName))->getContent())
            ->toMatch('#<meta name="description" content="[^"]+">#');
    })->with('public_pages');

    it('renders a non-empty canonical link', function (string $routeName): void {
        expect($this->get(route($routeName))->getContent())
            ->toMatch('#<link rel="canonical" href="[^"]+">#');
    })->with('public_pages');

    it('renders a non-empty open graph title', function (string $routeName): void {
        expect($this->get(route($routeName))->getContent())
            ->toMatch('#<meta property="og:title" content="[^"]+">#');
    })->with('public_pages');

    it('renders a non-empty open graph description', function (string $routeName): void {
        expect($this->get(route($routeName))->getContent())
            ->toMatch('#<meta property="og:description" content="[^"]+">#');
    })->with('public_pages');

    it('renders a non-empty open graph image', function (string $routeName): void {
        expect($this->get(route($routeName))->getContent())
            ->toMatch('#<meta property="og:image" content="[^"]+">#');
    })->with('public_pages');

    it('renders the twitter summary_large_image card', function (string $routeName): void {
        expect($this->get(route($routeName))->getContent())
            ->toContain('<meta name="twitter:card" content="summary_large_image">');
    })->with('public_pages');

    it('renders a unique title for every public page', function (): void {
        $titles = collect(['home', 'map', 'about', 'support', 'analytics', 'privacy', 'terms', 'contact'])
            ->map(fn (string $routeName): string => (string) preg_replace(
                '#.*<title>(.+?)</title>.*#s',
                '$1',
                (string) $this->get(route($routeName))->getContent()
            ));

        expect($titles->unique()->count())->toBe($titles->count());
    });
});

describe('robots directives', function (): void {
    it('marks the private report submission page as noindex', function (): void {
        expect($this->get(route('reports.create'))->getContent())
            ->toContain('<meta name="robots" content="noindex,follow">');
    });

    it('marks public pages as indexable', function (): void {
        expect($this->get(route('home'))->getContent())
            ->toContain('<meta name="robots" content="index,follow">');
    });
});
