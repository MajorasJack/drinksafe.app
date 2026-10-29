<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * PublishedVenueScope
 *
 * Limits every Venue query to published venues. A venue named for the first
 * time in a report submission stays unpublished until an admin publishes that
 * report, so free text nobody has reviewed never shows on the site. The
 * moderation queue opts out with withoutGlobalScope(PublishedVenueScope::class).
 */
final class PublishedVenueScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNotNull($model->qualifyColumn('published_at'));
    }
}
