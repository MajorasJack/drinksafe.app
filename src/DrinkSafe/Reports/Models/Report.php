<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Models;

use Carbon\Carbon;
use Database\Factories\DrinkSafe\ReportFactory;
use DrinkSafe\Reports\Enums\TimeOfDay;
use DrinkSafe\Shared\Traits\HasUuid;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Report Model
 *
 * Represents an anonymous spiking incident report submitted by users.
 * Reports are linked to venues and include incident date, time of day, and description.
 *
 * @property string $uuid Primary key (UUID)
 * @property string $venue_uuid Foreign key to venues table
 * @property Carbon $incident_date Date when incident occurred
 * @property TimeOfDay $time_of_day Time of day when incident occurred
 * @property string $description User-provided incident description
 * @property Carbon $created_at Timestamp when report was submitted
 * @property Carbon $updated_at Timestamp when report was last updated
 * @property Carbon|null $deleted_at Soft delete timestamp
 * @property-read Venue $venue Related venue
 *
 * @method static Builder recent(int $days = 30) Reports from last N days
 * @method static Builder byTimeOfDay(TimeOfDay $timeOfDay) Filter by time of day
 */
final class Report extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reports';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'venue_uuid',
        'incident_date',
        'time_of_day',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'time_of_day' => TimeOfDay::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the venue associated with this report.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_uuid', 'uuid');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ReportFactory::new();
    }

    /**
     * Scope query to recent reports from last N days.
     *
     * @param  Builder<Report>  $query
     * @param  int  $days  Number of days to look back (default: 30)
     * @return Builder<Report>
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('incident_date', '>=', now()->subDays($days));
    }

    /**
     * Scope query to filter reports by time of day.
     *
     * @param  Builder<Report>  $query
     * @param  TimeOfDay  $timeOfDay  Time of day to filter by
     * @return Builder<Report>
     */
    public function scopeByTimeOfDay(Builder $query, TimeOfDay $timeOfDay): Builder
    {
        return $query->where('time_of_day', $timeOfDay);
    }

    /**
     * Get formatted incident date as relative time.
     * Returns human-readable format like "2 days ago", "1 week ago", etc.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->incident_date->diffForHumans();
    }
}
