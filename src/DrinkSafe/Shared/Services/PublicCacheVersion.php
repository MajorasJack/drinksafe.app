<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Services;

use Illuminate\Support\Facades\Cache;

/**
 * PublicCacheVersion
 *
 * A version number that the heatmap and temporal analytics caches put in
 * their keys. Bumping it makes every existing entry unreachable at once, so
 * those caches can be cleared without flushing the whole store, which would
 * also wipe rate limiter counters and sessions. Old entries expire on their
 * own TTL.
 */
final class PublicCacheVersion
{
    /**
     * Cache key the version is stored under, forever.
     */
    private const string CACHE_KEY = 'public-cache-version';

    /**
     * Get the current version, which is 1 until the first bump.
     */
    public function current(): int
    {
        return (int) Cache::get(self::CACHE_KEY, 1);
    }

    /**
     * Move to the next version, so the public caches are rebuilt on the next request.
     *
     * Two bumps at the same moment can land on the same number. Both still move
     * the version past the one the old entries were stored under.
     *
     * @return int The new version
     */
    public function bump(): int
    {
        return tap($this->current() + 1, fn (int $nextVersion): bool => Cache::forever(self::CACHE_KEY, $nextVersion));
    }
}
