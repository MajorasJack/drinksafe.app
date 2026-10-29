<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Enums;

/**
 * ReportStatus Enum
 *
 * Where a report sits in the review queue. Every report starts as Pending
 * and only Published reports are shown on the public site.
 */
enum ReportStatus: string
{
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
}
