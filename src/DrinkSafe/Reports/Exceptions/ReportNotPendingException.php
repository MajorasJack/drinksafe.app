<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Exceptions;

use DrinkSafe\Shared\Exceptions\DrinkSafeException;

/**
 * ReportNotPendingException
 *
 * Thrown when an admin tries to move a report that has already been
 * published or rejected onto another venue. A reviewed report counts towards
 * its venue's public figures, so moving it would leave both venues' caches
 * and publish state out of date.
 */
final class ReportNotPendingException extends DrinkSafeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct()
    {
        parent::__construct('Only a report waiting for review can be moved to another venue.');
    }
}
