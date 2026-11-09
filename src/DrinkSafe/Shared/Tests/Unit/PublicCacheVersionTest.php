<?php

declare(strict_types=1);

use DrinkSafe\Shared\Services\PublicCacheVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('starts at one and goes up by one when bumped', function (): void {
    $publicCacheVersion = new PublicCacheVersion;

    expect($publicCacheVersion->current())->toBe(1)
        ->and($publicCacheVersion->bump())->toBe(2)
        ->and($publicCacheVersion->current())->toBe(2);
});

it('keeps the version in the cache under its own key', function (): void {
    (new PublicCacheVersion)->bump();

    expect(Cache::get('public-cache-version'))->toBe(2);
});
