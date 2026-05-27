<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * HasSlug Trait
 *
 * Automatically generates URL-friendly slugs for Eloquent models.
 *
 * Usage:
 * - Add this trait to any model that requires slug-based URLs
 * - Ensure migration defines slug column: $table->string('slug')->unique()
 * - Model will automatically generate unique slug on creation
 * - Slug is used for route model binding instead of primary key
 *
 * Overrides:
 * - getRouteKeyName(): Returns 'slug' for route model binding
 * - resolveRouteBinding(): Resolves model by slug field
 */
trait HasSlug
{
    /**
     * Boot the HasSlug trait.
     *
     * Registers model events for automatic slug generation.
     */
    protected static function bootHasSlug(): void
    {
        static::creating(function (Model $model): void {
            if ($model->slug === null || $model->slug === '') {
                $sourceField = $model->getSlugSourceField();
                $sourceValue = $model->{$sourceField};

                if ($sourceValue !== null && $sourceValue !== '') {
                    $model->slug = static::generateUniqueSlug($model, $sourceValue);
                }
            }
        });

        static::updating(function (Model $model): void {
            $sourceField = $model->getSlugSourceField();

            if ($model->isDirty($sourceField) && ($model->slug === null || $model->slug === '')) {
                $model->slug = static::generateUniqueSlug(
                    $model,
                    $model->{$sourceField},
                    $model->getKey()
                );
            }
        });
    }

    /**
     * Get the route key name for Laravel route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Resolve the model by route binding value.
     *
     * @param  mixed  $value  The route parameter value (slug)
     * @param  string|null  $field  The field to query (defaults to 'slug')
     */
    public function resolveRouteBinding($value, $field = null): static
    {
        /** @var static */
        return $this->where($field ?? 'slug', $value)
            ->firstOrFail();
    }

    /**
     * Get the source field used for slug generation.
     *
     * Override this method in the model to use a different source field.
     */
    public function getSlugSourceField(): string
    {
        return 'name';
    }

    /**
     * Generate a unique slug for the model.
     *
     * If the base slug already exists, appends an incrementing counter
     * until a unique slug is found.
     *
     * @param  Model  $model  The model instance
     * @param  string  $value  The source value to generate slug from
     * @param  mixed  $excludeKey  The primary key value to exclude from uniqueness check
     */
    protected static function generateUniqueSlug(Model $model, string $value, mixed $excludeKey = null): string
    {
        $slug = Str::slug($value);
        $originalSlug = $slug;
        $counter = 1;

        while (static::slugExists($model, $slug, $excludeKey)) {
            $slug = sprintf('%s-%d', $originalSlug, $counter);
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists in the database.
     *
     * @param  Model  $model  The model instance
     * @param  string  $slug  The slug to check
     * @param  mixed  $excludeKey  The primary key value to exclude from the check
     */
    protected static function slugExists(Model $model, string $slug, mixed $excludeKey = null): bool
    {
        $query = $model->newQuery()->where('slug', $slug);

        if ($excludeKey !== null) {
            $query->where($model->getKeyName(), '!=', $excludeKey);
        }

        return $query->exists();
    }
}
