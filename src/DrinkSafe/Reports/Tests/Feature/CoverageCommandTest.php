<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('drinksafe:coverage', function (): void {
    it('counts published venues, published reports and pending reports per city', function (): void {
        $publishedCount = fake()->numberBetween(1, 5);
        Venue::factory()->has(Report::factory()->count($publishedCount))->create(['city' => 'Manchester']);
        Venue::factory()->has(Report::factory()->pending())->create(['city' => ' manchester ']);
        Venue::factory()->unpublished()->has(Report::factory()->pending())->create(['city' => 'Manchester']);
        Venue::factory()->has(Report::factory()->rejected())->create(['city' => 'Salford']);
        Venue::factory()->has(Report::factory())->create(['city' => 'Newcastle upon Tyne']);

        $this->artisan('drinksafe:coverage', ['--city' => ['Manchester', 'Salford', 'Newcastle', 'Bolton']])
            ->expectsTable(['City', 'Venues', 'Published reports', 'Pending reports'], [
                ['Manchester', 2, $publishedCount, 2],
                ['Salford', 1, 0, 0],
                ['Newcastle', 1, 1, 0],
                ['Bolton', 0, 0, 0],
            ])
            ->assertSuccessful();
    });

    it('counts the thirteen outreach cities when no city is given', function (): void {
        $this->artisan('drinksafe:coverage')
            ->expectsTable(['City', 'Venues', 'Published reports', 'Pending reports'], array_map(
                fn (string $city): array => [$city, 0, 0, 0],
                ['Manchester', 'Salford', 'Bolton', 'Leeds', 'Liverpool', 'Sheffield', 'Newcastle', 'Nottingham', 'Birmingham', 'Bristol', 'Cardiff', 'Glasgow', 'Edinburgh'],
            ))
            ->assertSuccessful();
    });

    it('runs only select queries', function (): void {
        Venue::factory()->has(Report::factory()->pending())->create(['city' => 'Leeds']);
        $statements = collect();
        DB::listen(fn (QueryExecuted $query): Collection => $statements->push($query->sql));

        $this->artisan('drinksafe:coverage')->assertSuccessful();

        expect($statements)->not->toBeEmpty()
            ->and($statements->reject(fn (string $sql): bool => str_starts_with(strtolower($sql), 'select')))->toBeEmpty();
    });
});
