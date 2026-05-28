<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Exceptions;

use DrinkSafe\Shared\Exceptions\DrinkSafeException;

/**
 * InsufficientDataException
 *
 * Thrown when there is insufficient data for meaningful analytics.
 * This typically occurs when the minimum report threshold is not met
 * for a given filter combination (city, period, venue).
 */
final class InsufficientDataException extends DrinkSafeException
{
    /**
     * The minimum number of reports required for analysis.
     */
    private readonly int $minimumRequired;

    /**
     * The actual number of reports found.
     */
    private readonly int $actualCount;

    /**
     * Create a new InsufficientDataException instance.
     *
     * @param  string  $message  Human-readable error message
     * @param  int  $minimumRequired  Minimum reports required for analysis
     * @param  int  $actualCount  Actual number of reports found
     */
    public function __construct(
        string $message,
        int $minimumRequired = 0,
        int $actualCount = 0,
    ) {
        parent::__construct($message);

        $this->minimumRequired = $minimumRequired;
        $this->actualCount = $actualCount;
    }

    /**
     * Create an exception with default message format.
     *
     * @param  int  $minimumRequired  Minimum reports required
     * @param  int  $actualCount  Actual reports found
     */
    public static function create(int $minimumRequired, int $actualCount): self
    {
        $message = sprintf(
            'Insufficient data for analysis. Minimum %d reports required, found %d.',
            $minimumRequired,
            $actualCount,
        );

        return new self($message, $minimumRequired, $actualCount);
    }

    /**
     * Get the minimum number of reports required.
     */
    public function getMinimumRequired(): int
    {
        return $this->minimumRequired;
    }

    /**
     * Get the actual number of reports found.
     */
    public function getActualCount(): int
    {
        return $this->actualCount;
    }
}
