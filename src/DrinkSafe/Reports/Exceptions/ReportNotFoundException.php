<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Exceptions;

use DrinkSafe\Shared\Exceptions\DrinkSafeException;

/**
 * ReportNotFoundException
 *
 * Thrown when a report cannot be found by its UUID.
 * This exception should be caught by controllers and returned as a 404 response.
 */
final class ReportNotFoundException extends DrinkSafeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $reportUuid  The UUID of the report that was not found
     */
    public function __construct(string $reportUuid)
    {
        parent::__construct(
            sprintf('Report with UUID %s not found.', $reportUuid)
        );
    }
}
