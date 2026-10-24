<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

describe('Support page donation links', function (): void {
    it('passes every configured donation link', function (): void {
        $kofiUrl = fake()->url();
        $paypalUrl = fake()->url();
        config(['services.donations' => ['kofi' => $kofiUrl, 'paypal' => $paypalUrl]]);

        $response = $this->get(route('support'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Support')
            ->where('donationLinks', ['kofi' => $kofiUrl, 'paypal' => $paypalUrl])
        );
    });

    it('omits a donation link that has not been configured', function (): void {
        $kofiUrl = fake()->url();
        config(['services.donations' => ['kofi' => $kofiUrl, 'paypal' => null]]);

        $response = $this->get(route('support'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('donationLinks', ['kofi' => $kofiUrl])
        );
    });
});
