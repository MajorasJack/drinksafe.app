<?php

declare(strict_types=1);

namespace Database\Factories\DrinkSafe;

use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * VenueFactory
 *
 * Factory for generating Venue model instances for testing and seeding.
 *
 * @extends Factory<Venue>
 */
final class VenueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Venue>
     */
    protected $model = Venue::class;

    /**
     * UK venue types for realistic venue name generation.
     *
     * @var list<string>
     */
    private const VENUE_TYPES = [
        'Bar',
        'Pub',
        'Club',
        'Lounge',
        'Tavern',
        'Inn',
        'Sports Bar',
        'Cocktail Bar',
        'Wine Bar',
        'Nightclub',
    ];

    /**
     * UK venue name prefixes for realistic names.
     *
     * @var list<string>
     */
    private const VENUE_PREFIXES = [
        'The',
        '',
    ];

    /**
     * Common UK venue name words.
     *
     * @var list<string>
     */
    private const VENUE_NAMES = [
        'Crown',
        'Rose',
        'Fox',
        'Lion',
        'Duke',
        'King',
        'Queen',
        'Railway',
        'Royal',
        'White Horse',
        'Red Lion',
        'Black Bull',
        'Blue Moon',
        'Green Man',
        'Golden Eagle',
        'Silver Star',
        'Old Oak',
        'Three Horseshoes',
        'Coach and Horses',
        'Rising Sun',
    ];

    /**
     * Major UK cities for venue locations.
     *
     * @var list<string>
     */
    private const UK_CITIES = [
        'London',
        'Manchester',
        'Birmingham',
        'Leeds',
        'Bristol',
        'Liverpool',
        'Sheffield',
        'Newcastle',
        'Edinburgh',
        'Glasgow',
        'Cardiff',
        'Belfast',
        'Brighton',
        'Nottingham',
        'Leicester',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prefix = fake()->randomElement(self::VENUE_PREFIXES);
        $name = fake()->randomElement(self::VENUE_NAMES);
        $type = fake()->randomElement(self::VENUE_TYPES);

        $venueName = trim(sprintf('%s %s %s', $prefix, $name, $type));

        return [
            'name' => $venueName,
            'city' => fake()->randomElement(self::UK_CITIES),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->randomFloat(8, 50.0, 56.0), // UK latitude bounds
            'longitude' => fake()->randomFloat(8, -6.0, 2.0), // UK longitude bounds
        ];
    }

    /**
     * State for venues located in London.
     */
    public function london(): static
    {
        return $this->state(fn (array $attributes): array => [
            'city' => 'London',
            'latitude' => fake()->randomFloat(8, 51.3, 51.7), // London latitude range
            'longitude' => fake()->randomFloat(8, -0.5, 0.3), // London longitude range
        ]);
    }

    /**
     * State for venues located in Manchester.
     */
    public function manchester(): static
    {
        return $this->state(fn (array $attributes): array => [
            'city' => 'Manchester',
            'latitude' => fake()->randomFloat(8, 53.3, 53.6), // Manchester latitude range
            'longitude' => fake()->randomFloat(8, -2.4, -2.0), // Manchester longitude range
        ]);
    }
}
