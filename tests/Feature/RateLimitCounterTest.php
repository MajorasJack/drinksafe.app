<?php

declare(strict_types=1);

it('gives each API route its own rate-limit counter', function (): void {
    foreach (range(1, 120) as $request) {
        $this->getJson(route('api.venues.index'));
    }

    $response = $this->getJson(route('api.venues.search', ['q' => fake()->word()]));

    $response->assertOk();
});

it('counts requests against the connecting address, not the client IP headers', function (): void {
    $cloudflareAddress = '172.70.0.1';

    $firstResponse = $this->withServerVariables(['REMOTE_ADDR' => $cloudflareAddress])
        ->withHeaders(['CF-Connecting-IP' => fake()->ipv4(), 'X-Forwarded-For' => fake()->ipv4()])
        ->getJson(route('api.venues.search', ['q' => fake()->word()]));
    $secondResponse = $this->withServerVariables(['REMOTE_ADDR' => $cloudflareAddress])
        ->withHeaders(['CF-Connecting-IP' => fake()->ipv4(), 'X-Forwarded-For' => fake()->ipv4()])
        ->getJson(route('api.venues.search', ['q' => fake()->word()]));

    expect((int) $secondResponse->headers->get('X-RateLimit-Remaining'))
        ->toBe((int) $firstResponse->headers->get('X-RateLimit-Remaining') - 1);
});
