<?php

declare(strict_types=1);

namespace Database\Factories\DrinkSafe;

use DrinkSafe\Partners\Models\PartnerLink;
use DrinkSafe\Partners\Models\PartnerLinkDailyCount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * PartnerLinkDailyCountFactory
 *
 * Factory for generating PartnerLinkDailyCount model instances for testing.
 *
 * @extends Factory<PartnerLinkDailyCount>
 */
final class PartnerLinkDailyCountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PartnerLinkDailyCount>
     */
    protected $model = PartnerLinkDailyCount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'partner_link_id' => PartnerLink::factory(),
            'date' => fake()->unique()->date(),
            'count' => fake()->numberBetween(1, 50),
        ];
    }

    /**
     * Scans counted on the given day.
     */
    public function on(string $date): static
    {
        return $this->state(fn (): array => ['date' => $date]);
    }
}
