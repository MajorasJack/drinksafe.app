<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Scopes;

use DrinkSafe\Reports\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * PublishedScope
 *
 * Limits every Report query to published reports, so relationships, counts
 * and joins on the public site can't show a report that hasn't been reviewed.
 * The moderation queue opts out with withoutGlobalScope(PublishedScope::class).
 */
final class PublishedScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->qualifyColumn('status'), ReportStatus::Published);
    }
}
