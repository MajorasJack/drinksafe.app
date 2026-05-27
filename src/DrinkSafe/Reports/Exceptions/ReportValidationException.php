<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Exceptions;

use DrinkSafe\Shared\Exceptions\DrinkSafeException;

/**
 * ReportValidationException
 *
 * Thrown when report validation fails, particularly for PII detection.
 * This exception should be caught by controllers and returned as a 422 response.
 */
final class ReportValidationException extends DrinkSafeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message  The validation error message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
