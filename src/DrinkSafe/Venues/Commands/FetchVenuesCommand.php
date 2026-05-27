<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Commands;

use DrinkSafe\Venues\Services\OverpassService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * FetchVenuesCommand
 *
 * Fetches real venue data from OpenStreetMap's Overpass API
 * and caches it to a JSON file for use by the VenueSeeder.
 */
final class FetchVenuesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:fetch
                            {--radius=5000 : Search radius in metres around each city centre}
                            {--limit= : Maximum venues per city (default: unlimited)}
                            {--cities= : Comma-separated list of cities to fetch (default: all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch real venue data from OpenStreetMap Overpass API';

    /**
     * Default UK cities with their approximate centre coordinates.
     *
     * @var array<string, array{lat: float, lng: float}>
     */
    private const array DEFAULT_CITIES = [
        'London' => ['lat' => 51.5074, 'lng' => -0.1278],
        'Manchester' => ['lat' => 53.4808, 'lng' => -2.2426],
        'Birmingham' => ['lat' => 52.4862, 'lng' => -1.8904],
        'Leeds' => ['lat' => 53.8008, 'lng' => -1.5491],
        'Bristol' => ['lat' => 51.4545, 'lng' => -2.5879],
        'Liverpool' => ['lat' => 53.4084, 'lng' => -2.9916],
        'Newcastle' => ['lat' => 54.9783, 'lng' => -1.6178],
        'Sheffield' => ['lat' => 53.3811, 'lng' => -1.4701],
        'Edinburgh' => ['lat' => 55.9533, 'lng' => -3.1883],
        'Glasgow' => ['lat' => 55.8642, 'lng' => -4.2518],
    ];

    public function __construct(
        private readonly OverpassService $overpassService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $radius = (int) $this->option('radius');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $cities = $this->getCitiesToFetch();

        $this->info(sprintf('Fetching venues from OpenStreetMap Overpass API...'));
        $this->info(sprintf('Cities: %s', implode(', ', array_keys($cities))));
        $this->info(sprintf('Radius: %dm', $radius));

        if ($limit !== null) {
            $this->info(sprintf('Limit per city: %d', $limit));
        }

        $this->newLine();

        $allVenues = collect();
        $progressBar = $this->output->createProgressBar(count($cities));
        $progressBar->start();

        foreach ($cities as $cityName => $coords) {
            $venues = $this->overpassService->fetchVenuesForCity(
                $cityName,
                $coords['lat'],
                $coords['lng'],
                $radius
            );

            if ($limit !== null) {
                $venues = $venues->take($limit);
            }

            $allVenues = $allVenues->merge($venues);
            $progressBar->advance();

            // Rate limiting between cities
            usleep(500000);
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($allVenues->isEmpty()) {
            $this->error('No venues found. The Overpass API may be unavailable or rate-limited.');

            return self::FAILURE;
        }

        // Remove duplicates by OSM ID
        $uniqueVenues = $allVenues->unique('osm_id')->values();

        $this->saveToJson($uniqueVenues->toArray());

        $this->info(sprintf('Successfully fetched %d unique venues', $uniqueVenues->count()));

        $this->newLine();
        $this->table(
            ['City', 'Venues'],
            $uniqueVenues->groupBy('city')
                ->map(fn ($venues, $city) => [$city, $venues->count()])
                ->values()
                ->toArray()
        );

        return self::SUCCESS;
    }

    /**
     * Get the cities to fetch based on command options.
     *
     * @return array<string, array{lat: float, lng: float}>
     */
    private function getCitiesToFetch(): array
    {
        $citiesOption = $this->option('cities');

        if ($citiesOption === null) {
            return self::DEFAULT_CITIES;
        }

        $requestedCities = array_map('trim', explode(',', $citiesOption));

        return array_filter(
            self::DEFAULT_CITIES,
            fn (string $city): bool => in_array($city, $requestedCities, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Save venues data to JSON file.
     *
     * @param  array<int, array<string, mixed>>  $venues
     */
    private function saveToJson(array $venues): void
    {
        $outputPath = $this->getOutputPath();
        $directory = dirname($outputPath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $data = [
            'fetched_at' => now()->toIso8601String(),
            'source' => 'OpenStreetMap Overpass API',
            'count' => count($venues),
            'venues' => $venues,
        ];

        File::put($outputPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info(sprintf('Saved to: %s', $outputPath));
    }

    /**
     * Get the output file path.
     */
    private function getOutputPath(): string
    {
        return database_path('seeders/data/venues.json');
    }
}
