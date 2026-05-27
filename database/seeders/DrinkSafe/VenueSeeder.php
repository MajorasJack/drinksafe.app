<?php

declare(strict_types=1);

namespace Database\Seeders\DrinkSafe;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class VenueSeeder extends Seeder
{
    /**
     * Path to the cached venues JSON file.
     */
    private const string VENUES_JSON_PATH = 'seeders/data/venues.json';

    public function run(): void
    {
        $jsonPath = database_path(self::VENUES_JSON_PATH);

        if (File::exists($jsonPath)) {
            $this->seedFromJson($jsonPath);

            return;
        }

        $this->command->warn('No cached venue data found. Run "php artisan venues:fetch" first for real data.');
        $this->command->info('Falling back to generated venue data...');

        $this->seedGenerated();
    }

    /**
     * Seed venues from the cached JSON file.
     */
    private function seedFromJson(string $jsonPath): void
    {
        $data = json_decode(File::get($jsonPath), true);
        $venues = $data['venues'] ?? [];

        if ($venues === []) {
            $this->command->error('Venues JSON file is empty or malformed.');

            return;
        }

        $now = now();
        $insertData = [];
        $usedSlugs = [];

        foreach ($venues as $venue) {
            $slug = $this->generateUniqueSlug($venue['name'], $usedSlugs);
            $usedSlugs[] = $slug;

            $insertData[] = [
                'uuid' => Str::uuid()->toString(),
                'name' => $venue['name'],
                'slug' => $slug,
                'city' => $venue['city'],
                'address' => $venue['address'],
                'latitude' => $venue['latitude'],
                'longitude' => $venue['longitude'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($insertData, 100) as $chunk) {
            DB::table('venues')->insert($chunk);
        }

        $this->command->info(sprintf(
            'Seeded %d real venues from OpenStreetMap (fetched: %s)',
            count($insertData),
            $data['fetched_at'] ?? 'unknown'
        ));
    }

    /**
     * Generate a unique slug from the venue name.
     *
     * @param  list<string>  $usedSlugs  Already used slugs in this seeding run
     */
    private function generateUniqueSlug(string $name, array $usedSlugs): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (in_array($slug, $usedSlugs, true)) {
            $slug = sprintf('%s-%d', $baseSlug, $counter);
            $counter++;
        }

        return $slug;
    }

    /**
     * Seed venues with generated data (fallback).
     */
    private function seedGenerated(): void
    {
        $cities = [
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

        $venueTypes = [
            'Bar',
            'Pub',
            'Club',
            'Lounge',
            'Nightclub',
            'Tavern',
            'Inn',
            'Sports Bar',
            'Wine Bar',
            'Cocktail Bar',
        ];

        $venueNames = [
            'The Crown',
            'The Rising Sun',
            'The Fox and Hound',
            'The Red Lion',
            'The Black Horse',
            'The Blue Moon',
            'The Golden Eagle',
            'The White Hart',
            'The Green Dragon',
            'Neon Nights',
            'Electric Dreams',
            'The Underground',
            'Velocity',
            'Fusion',
            'Eclipse',
            'Mirage',
            'Spectrum',
            'Zenith',
            'Phoenix',
            'Revolution',
        ];

        $venues = [];
        $usedSlugs = [];

        foreach ($cities as $cityName => $coords) {
            $numVenuesInCity = fake()->numberBetween(3, 7);

            for ($i = 0; $i < $numVenuesInCity; $i++) {
                $venueName = sprintf(
                    '%s %s',
                    fake()->randomElement($venueNames),
                    fake()->randomElement($venueTypes)
                );

                $slug = $this->generateUniqueSlug($venueName, $usedSlugs);
                $usedSlugs[] = $slug;

                // Add slight variation to coordinates (within ~5km radius)
                $latVariation = fake()->randomFloat(4, -0.045, 0.045);
                $lngVariation = fake()->randomFloat(4, -0.045, 0.045);

                $venues[] = [
                    'uuid' => Str::uuid()->toString(),
                    'name' => $venueName,
                    'slug' => $slug,
                    'city' => $cityName,
                    'address' => sprintf(
                        '%d %s, %s',
                        fake()->buildingNumber(),
                        fake()->streetName(),
                        $cityName
                    ),
                    'latitude' => $coords['lat'] + $latVariation,
                    'longitude' => $coords['lng'] + $lngVariation,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('venues')->insert($venues);

        $this->command->info(sprintf('Seeded %d generated venues across %d cities', count($venues), count($cities)));
    }
}
