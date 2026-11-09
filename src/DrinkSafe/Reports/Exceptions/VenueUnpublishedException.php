<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Exceptions;

use DrinkSafe\Shared\Exceptions\DrinkSafeException;

/**
 * VenueUnpublishedException
 *
 * Thrown when an admin tries to publish a report about a venue they
 * unpublished. Publishing the report would otherwise put the venue back on
 * the site without anyone deciding to.
 */
final class VenueUnpublishedException extends DrinkSafeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct()
    {
        parent::__construct('This venue is unpublished. Publish the venue first.');
    }
}
