<?php

declare(strict_types=1);

it('wires the Turnstile keys into the services config the verifier reads', function (): void {
    expect(config('services.turnstile'))->toHaveKeys(['key', 'secret']);
});
