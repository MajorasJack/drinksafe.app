<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Policies;

use App\Models\User;

/**
 * ReportPolicy
 *
 * Decides who can work the report review queue. Only users flagged as
 * admins (see the drinksafe:make-admin command) can publish or reject.
 */
final class ReportPolicy
{
    /**
     * Determine whether the user can view the review queue and publish or reject reports.
     */
    public function moderate(User $user): bool
    {
        return $user->is_admin;
    }
}
