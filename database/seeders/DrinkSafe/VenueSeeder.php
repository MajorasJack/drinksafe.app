<?php

declare(strict_types=1);

namespace Database\Seeders\DrinkSafe;

use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Seeder;
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

        $created = 0;
        $updated = 0;

        foreach ($venues as $venue) {
            $slug = Str::slug($venue['name']);

            $result = Venue::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $venue['name'],
                    'city' => $venue['city'],
                    'address' => $venue['address'],
                    'latitude' => $venue['latitude'],
                    'longitude' => $venue['longitude'],
                ]
            );

            $result->wasRecentlyCreated ? $created++ : $updated++;
        }

        $this->command->info(sprintf(
            'Seeded venues from OpenStreetMap (fetched: %s): %d created, %d updated',
            $data['fetched_at'] ?? 'unknown',
            $created,
            $updated
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

        $created = 0;
        $updated = 0;
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

                $result = Venue::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $venueName,
                        'city' => $cityName,
                        'address' => sprintf(
                            '%d %s, %s',
                            fake()->buildingNumber(),
                            fake()->streetName(),
                            $cityName
                        ),
                        'latitude' => $coords['lat'] + $latVariation,
                        'longitude' => $coords['lng'] + $lngVariation,
                    ]
                );

                $result->wasRecentlyCreated ? $created++ : $updated++;
            }
        }

        $this->command->info(sprintf(
            'Seeded generated venues across %d cities: %d created, %d updated',
            count($cities),
            $created,
            $updated
        ));
    }
}
