<?php

declare(strict_types=1);

describe('SEO foundation', function (): void {
    it('renders a server-side title in the initial HTML', function (): void {
        $response = $this->get(route('home'));

        $response->assertOk();
        expect($response->getContent())->toContain('<title>');
    });

    it('renders the meta description', function (): void {
        $response = $this->get(route('home'));

        expect($response->getContent())->toContain('name="description"');
    });

    it('renders a canonical link', function (): void {
        $response = $this->get(route('home'));

        expect($response->getContent())->toContain('<link rel="canonical"');
    });

    it('renders open graph title', function (): void {
        $response = $this->get(route('home'));

        expect($response->getContent())->toContain('property="og:title"');
    });

    it('renders a json-ld structured data script', function (): void {
        $response = $this->get(route('home'));

        expect($response->getContent())->toContain('application/ld+json');
    });

    it('renders exactly one title element', function (): void {
        $response = $this->get(route('home'));

        expect(substr_count($response->getContent(), '<title>'))->toBe(1);
    });
});

it('renders the Google site verification tag when a token is configured', function (): void {
    $token = fake()->regexify('[A-Za-z0-9_-]{43}');
    config(['services.google.site_verification' => $token]);

    $this->get(route('home'))->assertOk()->assertSee(sprintf('<meta name="google-site-verification" content="%s">', $token), escape: false);
});

it('omits the Google site verification tag when no token is configured', function (): void {
    config(['services.google.site_verification' => null]);

    $this->get(route('home'))->assertOk()->assertDontSee('google-site-verification');
});
