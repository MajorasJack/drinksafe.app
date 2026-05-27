<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('AboutController', function (): void {
    it('renders about page successfully', function (): void {
        $response = $this->get(route('about'));

        $response->assertOk();
    });

    it('shows about page with Inertia component', function (): void {
        $response = $this->get(route('about'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('About')
        );
    });
});
