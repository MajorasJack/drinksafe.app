<?php

declare(strict_types=1);

namespace Database\Factories\DrinkSafe;

use DrinkSafe\Reports\Enums\TimeOfDay;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ReportFactory
 *
 * Generates realistic spiking incident reports for testing and seeding.
 *
 * @extends Factory<Report>
 */
final class ReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Report>
     */
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_uuid' => Venue::factory(),
            'incident_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'time_of_day' => fake()->randomElement(TimeOfDay::cases()),
            'description' => $this->generateRealisticDescription(),
        ];
    }

    /**
     * Generate realistic spiking incident description.
     * Returns varied, realistic descriptions typical of spiking reports.
     */
    private function generateRealisticDescription(): string
    {
        $templates = [
            'Felt dizzy and disoriented after finishing my drink. Had to leave early.',
            'My friend became suddenly very unwell after one drink. We had to call for help.',
            'Noticed my drink tasted strange and felt effects that were unusual for the amount consumed.',
            'Experienced memory loss and felt extremely confused after drinking here.',
            'Had to be taken to hospital after feeling very ill following a night out here.',
            'Witnessed someone tampering with drinks at the bar. Staff were notified.',
            'Left my drink unattended briefly and felt very unwell shortly after returning.',
            'Felt symptoms inconsistent with alcohol consumption. Concerned about drink safety.',
            'My drink was left unattended for a moment. Soon after felt very strange and had to leave.',
            'Friend became violently ill after one cocktail. Very concerning experience.',
        ];

        return fake()->randomElement($templates);
    }

    /**
     * Factory state for recent reports (within last 7 days).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'incident_date' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Factory state for old reports (4-6 months ago).
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes): array => [
            'incident_date' => fake()->dateTimeBetween('-6 months', '-4 months'),
        ]);
    }

    /**
     * Factory state for night-time reports.
     */
    public function night(): static
    {
        return $this->state(fn (array $attributes): array => [
            'time_of_day' => TimeOfDay::Night,
        ]);
    }

    /**
     * Factory state for morning reports.
     */
    public function morning(): static
    {
        return $this->state(fn (array $attributes): array => [
            'time_of_day' => TimeOfDay::Morning,
        ]);
    }

    /**
     * Factory state for afternoon reports.
     */
    public function afternoon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'time_of_day' => TimeOfDay::Afternoon,
        ]);
    }

    /**
     * Factory state for evening reports.
     */
    public function evening(): static
    {
        return $this->state(fn (array $attributes): array => [
            'time_of_day' => TimeOfDay::Evening,
        ]);
    }

    /**
     * Factory state for a report exactly N days ago.
     *
     * Useful for testing time-decay algorithms in safety scores.
     *
     * @param  int  $days  Number of days ago the incident occurred
     */
    public function daysAgo(int $days): static
    {
        return $this->state(fn (array $attributes): array => [
            'incident_date' => now()->subDays($days)->toDateString(),
        ]);
    }

    /**
     * Factory state for reports within the last 30 days (for safety score testing).
     */
    public function within30Days(): static
    {
        return $this->state(fn (array $attributes): array => [
            'incident_date' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Factory state for reports between 90-180 days ago.
     */
    public function aged(): static
    {
        return $this->state(fn (array $attributes): array => [
            'incident_date' => fake()->dateTimeBetween('-180 days', '-90 days'),
        ]);
    }
}
