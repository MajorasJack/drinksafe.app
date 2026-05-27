<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Exceptions;

use DrinkSafe\Shared\Exceptions\DrinkSafeException;

/**
 * Exception thrown when attempting to create a duplicate venue.
 */
class VenueDuplicateException extends DrinkSafeException
{
    public function __construct(string $name, string $city)
    {
        parent::__construct(
            sprintf("A venue named '%s' already exists in %s.", $name, $city)
        );
    }
}
