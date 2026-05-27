<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Exceptions;

use DrinkSafe\Shared\Exceptions\DrinkSafeException;

/**
 * VenueNotFoundException
 *
 * Thrown when a venue cannot be found by its UUID.
 * This exception should be caught by controllers and returned as a 404 response.
 */
final class VenueNotFoundException extends DrinkSafeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $venueUuid  The UUID of the venue that was not found
     */
    public function __construct(string $venueUuid)
    {
        parent::__construct(
            sprintf('Venue with UUID %s not found.', $venueUuid)
        );
    }
}
