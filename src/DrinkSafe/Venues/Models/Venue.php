<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Models;

use Database\Factories\DrinkSafe\VenueFactory;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Shared\Traits\HasSlug;
use DrinkSafe\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Venue Model
 *
 * Represents a physical venue where incidents can be reported.
 *
 * @property string $uuid Primary key (UUID)
 * @property string $name Venue name
 * @property string $slug URL-friendly slug (unique)
 * @property string $city City where venue is located
 * @property string|null $address Full street address (optional)
 * @property float $latitude Latitude coordinate
 * @property float $longitude Longitude coordinate
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Report> $reports
 * @property-read int $reports_count
 *
 * @method static VenueFactory factory($count = null, $state = [])
 * @method static Builder|Venue nearby(float $latitude, float $longitude, int $radiusKm)
 * @method static Builder|Venue inCity(string $city)
 * @method static Builder|Venue search(string $term)
 */
final class Venue extends Model
{
    use HasFactory;
    use HasSlug;
    use HasUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'venues';

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
        'name',
        'slug',
        'city',
        'address',
        'latitude',
        'longitude',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    /**
     * Get all reports for this venue.
     *
     * @return HasMany<Report, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'venue_uuid', 'uuid');
    }

    /**
     * Scope a query to find venues within a specific radius of coordinates.
     *
     * Uses Haversine formula to calculate distance in kilometres.
     *
     * @param  Builder<Venue>  $query
     * @param  float  $latitude  Centre point latitude
     * @param  float  $longitude  Centre point longitude
     * @param  int  $radiusKm  Search radius in kilometres
     * @return Builder<Venue>
     */
    public function scopeNearby(Builder $query, float $latitude, float $longitude, int $radiusKm): Builder
    {
        // Haversine formula to calculate distance
        // Earth radius = 6371 km
        // Using whereRaw instead of havingRaw for SQLite compatibility
        return $query->selectRaw(
            '*, ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
            [$latitude, $longitude, $latitude]
        )
            ->whereRaw('( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) <= ?', [$latitude, $longitude, $latitude, $radiusKm])
            ->orderBy('distance');
    }

    /**
     * Scope a query to filter venues by city.
     *
     * @param  Builder<Venue>  $query
     * @param  string  $city  City name (exact match, case-insensitive)
     * @return Builder<Venue>
     */
    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('city', '=', $city);
    }

    /**
     * Scope a query to search venues by name or city.
     *
     * Performs case-insensitive partial match on name and city fields.
     *
     * @param  Builder<Venue>  $query
     * @param  string  $term  Search term
     * @return Builder<Venue>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term): void {
            $q->where('name', 'LIKE', sprintf('%%%s%%', $term))
                ->orWhere('city', 'LIKE', sprintf('%%%s%%', $term));
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): VenueFactory
    {
        return VenueFactory::new();
    }
}
