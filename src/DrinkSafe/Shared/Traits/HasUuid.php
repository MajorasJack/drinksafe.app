<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * HasUuid Trait
 *
 * Automatically generates UUID primary keys for Eloquent models.
 *
 * Usage:
 * - Add this trait to any model that uses UUID as primary key
 * - Ensure migration defines primary key as: $table->uuid('uuid')->primary()
 * - Model will automatically generate UUID on creation if not already set
 *
 * Overrides:
 * - getKeyType(): Returns 'string' instead of 'int'
 * - getIncrementing(): Returns false to disable auto-increment
 */
trait HasUuid
{
    /**
     * Boot the HasUuid trait.
     *
     * Listens for the 'creating' event and automatically generates
     * a UUID for the primary key if one has not been set.
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the data type of the primary key.
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Get whether the primary key is auto-incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }
}
