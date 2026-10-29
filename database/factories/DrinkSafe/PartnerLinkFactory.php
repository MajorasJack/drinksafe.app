<?php

declare(strict_types=1);

namespace Database\Factories\DrinkSafe;

use DrinkSafe\Partners\Models\PartnerLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * PartnerLinkFactory
 *
 * Factory for generating PartnerLink model instances for testing.
 *
 * @extends Factory<PartnerLink>
 */
final class PartnerLinkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PartnerLink>
     */
    protected $model = PartnerLink::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[a-z]{4,8}-[a-z0-9]{3}'),
            'label' => fake()->company(),
            'destination' => '/submit-report',
            'active' => true,
        ];
    }

    /**
     * A link that has been switched off.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
