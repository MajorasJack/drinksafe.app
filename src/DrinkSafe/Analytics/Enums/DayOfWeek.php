<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Enums;

/**
 * DayOfWeek Enum
 *
 * Represents days of the week for temporal analytics aggregation.
 * Provides conversion between PHP weekday format and MySQL DAYOFWEEK() format.
 *
 * MySQL DAYOFWEEK() returns: 1=Sunday, 2=Monday, 3=Tuesday, 4=Wednesday,
 * 5=Thursday, 6=Friday, 7=Saturday.
 */
enum DayOfWeek: string
{
    case Sunday = 'Sunday';
    case Monday = 'Monday';
    case Tuesday = 'Tuesday';
    case Wednesday = 'Wednesday';
    case Thursday = 'Thursday';
    case Friday = 'Friday';
    case Saturday = 'Saturday';

    /**
     * Create a DayOfWeek from MySQL DAYOFWEEK() value.
     *
     * MySQL DAYOFWEEK() returns 1 for Sunday through 7 for Saturday.
     *
     * @param  int  $dayNumber  MySQL day number (1-7)
     *
     * @throws \ValueError If day number is outside valid range
     */
    public static function fromMySqlDayOfWeek(int $dayNumber): self
    {
        return match ($dayNumber) {
            1 => self::Sunday,
            2 => self::Monday,
            3 => self::Tuesday,
            4 => self::Wednesday,
            5 => self::Thursday,
            6 => self::Friday,
            7 => self::Saturday,
            default => throw new \ValueError(
                sprintf('Invalid MySQL DAYOFWEEK value: %d. Expected 1-7.', $dayNumber)
            ),
        };
    }

    /**
     * Get the MySQL DAYOFWEEK() value for this day.
     *
     * @return int MySQL day number (1-7, where 1=Sunday)
     */
    public function mysqlDayNumber(): int
    {
        return match ($this) {
            self::Sunday => 1,
            self::Monday => 2,
            self::Tuesday => 3,
            self::Wednesday => 4,
            self::Thursday => 5,
            self::Friday => 6,
            self::Saturday => 7,
        };
    }

    /**
     * Get human-readable display label for the day.
     *
     * @return string Day name (e.g., "Monday", "Tuesday")
     */
    public function toLabel(): string
    {
        return $this->value;
    }

    /**
     * Check if this day is a weekend day.
     *
     * @return bool True if Saturday or Sunday
     */
    public function isWeekend(): bool
    {
        return $this === self::Saturday || $this === self::Sunday;
    }

    /**
     * Check if this day is a weekday.
     *
     * @return bool True if Monday through Friday
     */
    public function isWeekday(): bool
    {
        return ! $this->isWeekend();
    }

    /**
     * Get abbreviated day name.
     *
     * @return string Three-letter abbreviation (e.g., "Mon", "Tue")
     */
    public function toAbbreviation(): string
    {
        return match ($this) {
            self::Sunday => 'Sun',
            self::Monday => 'Mon',
            self::Tuesday => 'Tue',
            self::Wednesday => 'Wed',
            self::Thursday => 'Thu',
            self::Friday => 'Fri',
            self::Saturday => 'Sat',
        };
    }
}
