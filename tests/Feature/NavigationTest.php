<?php

declare(strict_types=1);

it('renders home page successfully', function (): void {
    $response = $this->getJson('/');

    $response->assertOk();
});

it('renders map page successfully', function (): void {
    $response = $this->getJson('/map');

    $response->assertOk();
});

it('renders submit report page successfully', function (): void {
    $response = $this->getJson('/submit-report');

    $response->assertOk();
});

it('renders about page successfully', function (): void {
    $response = $this->getJson('/about');

    $response->assertOk();
});

it('returns 404 for old report route', function (): void {
    $response = $this->getJson('/report');

    $response->assertNotFound();
});
