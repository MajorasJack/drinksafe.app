<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Services;

use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Scopes\PublishedScope;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * CoverageReport
 *
 * Counts how many published venues, published reports and pending reports
 * each city has, to show where the site has something to show before
 * outreach starts there. It only reads.
 */
final class CoverageReport
{
    /**
     * Cities counted when none are given.
     *
     * @var list<string>
     */
    public const array DEFAULT_CITIES = [
        'Manchester',
        'Salford',
        'Bolton',
        'Leeds',
        'Liverpool',
        'Sheffield',
        'Newcastle',
        'Nottingham',
        'Birmingham',
        'Bristol',
        'Cardiff',
        'Glasgow',
        'Edinburgh',
    ];

    /**
     * Other names people type for a city, keyed by the lowercase city name.
     *
     * The importer stores "Newcastle", but the report form takes free text.
     *
     * @var array<string, list<string>>
     */
    private const array CITY_ALIASES = [
        'newcastle' => ['newcastle upon tyne', 'newcastle-upon-tyne'],
    ];

    /**
     * Count each city's venues and reports, in the order given, or the
     * default cities when the list is empty.
     *
     * @param  list<string>  $cities  City names as typed
     * @return list<array{city: string, venues: int, published_reports: int, pending_reports: int}>
     */
    public function forCities(array $cities): array
    {
        return array_map(fn (string $city): array => $this->countsFor($city), $cities ?: self::DEFAULT_CITIES);
    }

    /**
     * Count one city's venues and reports, matching the city name without
     * regard to case or surrounding spaces.
     *
     * @return array{city: string, venues: int, published_reports: int, pending_reports: int}
     */
    private function countsFor(string $city): array
    {
        $names = [mb_strtolower(trim($city)), ...self::CITY_ALIASES[mb_strtolower(trim($city))] ?? []];
        $inCity = fn (Builder $query): Builder => $query->whereIn(DB::raw('LOWER(TRIM(city))'), $names);

        return [
            'city' => $city,
            'venues' => Venue::query()->where($inCity)->count(),
            'published_reports' => Report::query()->whereHas('venue', $inCity)->count(),
            'pending_reports' => Report::withoutGlobalScope(PublishedScope::class)
                ->where('status', ReportStatus::Pending)
                ->whereHas('venue', fn (Builder $query): Builder => $query->withoutGlobalScope(PublishedVenueScope::class)->where($inCity))
                ->count(),
        ];
    }
}
