<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Commands;

use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Scopes\PublishedScope;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * CoverageCommand
 *
 * Prints how many published venues, published reports and pending reports
 * each city has, to show where the site has something to show before
 * outreach starts there. It only reads.
 */
final class CoverageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drinksafe:coverage
        {--city=* : City to count instead of the default list (repeatable)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Count published venues and reports per city';

    /**
     * Cities counted when no --city is given.
     *
     * @var list<string>
     */
    private const array DEFAULT_CITIES = [
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
     * Execute the console command.
     */
    public function handle(): int
    {
        $cities = $this->option('city') ?: self::DEFAULT_CITIES;

        $this->table(
            ['City', 'Venues', 'Published reports', 'Pending reports'],
            array_map(fn (string $city): array => $this->countsFor($city), $cities),
        );

        return self::SUCCESS;
    }

    /**
     * Count one city's venues and reports, matching the city name without
     * regard to case or surrounding spaces.
     *
     * @return array{string, int, int, int}
     */
    private function countsFor(string $city): array
    {
        $names = [mb_strtolower(trim($city)), ...self::CITY_ALIASES[mb_strtolower(trim($city))] ?? []];
        $inCity = fn (Builder $query): Builder => $query->whereIn(DB::raw('LOWER(TRIM(city))'), $names);

        return [
            $city,
            Venue::query()->where($inCity)->count(),
            Report::query()->whereHas('venue', $inCity)->count(),
            Report::withoutGlobalScope(PublishedScope::class)
                ->where('status', ReportStatus::Pending)
                ->whereHas('venue', fn (Builder $query): Builder => $query->withoutGlobalScope(PublishedVenueScope::class)->where($inCity))
                ->count(),
        ];
    }
}
