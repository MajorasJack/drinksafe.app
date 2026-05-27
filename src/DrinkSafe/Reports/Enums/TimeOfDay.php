<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Enums;

/**
 * TimeOfDay Enum
 *
 * Represents the time of day when a spiking incident occurred.
 * Used for filtering and categorising reports by temporal patterns.
 */
enum TimeOfDay: string
{
    case Morning = 'Morning';
    case Afternoon = 'Afternoon';
    case Evening = 'Evening';
    case Night = 'Night';
    case Unknown = 'Unknown';

    /**
     * Get human-readable label for the time of day.
     */
    public function label(): string
    {
        return $this->value;
    }

    /**
     * Get Lucide icon name for the time of day.
     * Used for UI display in frontend components.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Morning => 'sunrise',
            self::Afternoon => 'sun',
            self::Evening => 'sunset',
            self::Night => 'moon',
            self::Unknown => 'clock',
        };
    }
}
