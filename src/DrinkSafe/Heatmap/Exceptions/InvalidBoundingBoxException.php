<?php

declare(strict_types=1);

namespace DrinkSafe\Heatmap\Exceptions;

use DrinkSafe\Shared\Exceptions\DrinkSafeException;

/**
 * InvalidBoundingBoxException
 *
 * Thrown when heatmap bounding box coordinates are invalid.
 * This includes missing coordinates, out-of-range values, or
 * logically invalid bounds (e.g., south > north).
 */
final class InvalidBoundingBoxException extends DrinkSafeException
{
    /**
     * Create a new InvalidBoundingBoxException instance.
     *
     * @param  string  $message  Human-readable error message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * Create exception for missing bounding box coordinates.
     */
    public static function missingCoordinates(): self
    {
        return new self('Bounding box must include north, south, east, and west coordinates');
    }

    /**
     * Create exception for invalid latitude range.
     */
    public static function invalidLatitude(): self
    {
        return new self('Latitude must be between -90 and 90');
    }

    /**
     * Create exception for invalid longitude range.
     */
    public static function invalidLongitude(): self
    {
        return new self('Longitude must be between -180 and 180');
    }

    /**
     * Create exception for inverted bounds.
     */
    public static function invertedBounds(): self
    {
        return new self('South latitude must be less than north latitude');
    }
}
