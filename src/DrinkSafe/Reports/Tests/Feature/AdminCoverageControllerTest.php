<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Requests\AdminCoverageRequest;
use DrinkSafe\Reports\Services\CoverageReport;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
});

describe('AdminCoverageController', function (): void {
    it('shows coverage for the default cities', function (): void {
        $response = $this->get(route('admin.coverage'));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/Coverage')
            ->where('cities', '')
            ->where('coverage', array_map(
                fn (string $city): array => ['city' => $city, 'venues' => 0, 'published_reports' => 0, 'pending_reports' => 0],
                CoverageReport::DEFAULT_CITIES,
            )));
    });

    it('shows coverage for cities typed by the admin', function (): void {
        $publishedCount = fake()->numberBetween(1, 5);
        Venue::factory()->has(Report::factory()->count($publishedCount))->create(['city' => 'Newcastle upon Tyne']);
        Venue::factory()->unpublished()->has(Report::factory()->pending())->create(['city' => 'Leeds']);

        $response = $this->get(route('admin.coverage', ['cities' => ' Newcastle, Leeds,, Leeds ']));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('cities', 'Newcastle, Leeds')
            ->where('coverage', [
                ['city' => 'Newcastle', 'venues' => 1, 'published_reports' => $publishedCount, 'pending_reports' => 0],
                ['city' => 'Leeds', 'venues' => 0, 'published_reports' => 0, 'pending_reports' => 1],
            ]));
    });

    it('leaves out a city repeated in a different case', function (): void {
        $response = $this->get(route('admin.coverage', ['cities' => 'Leeds, LEEDS, leeds, York']));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('cities', 'Leeds, York'));
    });

    it('counts as many cities as it allows at once', function (): void {
        $cities = implode(',', array_map(fn (int $number): string => sprintf('City %d', $number), range(1, AdminCoverageRequest::MAX_CITIES)));

        $response = $this->get(route('admin.coverage', ['cities' => $cities]));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('coverage', AdminCoverageRequest::MAX_CITIES));
    });

    it('refuses more cities than it counts at once', function (): void {
        $cities = implode(',', array_map(fn (int $number): string => sprintf('City %d', $number), range(1, AdminCoverageRequest::MAX_CITIES + 1)));

        $response = $this->getJson(route('admin.coverage', ['cities' => $cities]));

        $response->assertUnprocessable()->assertJsonValidationErrors('cities');
    });
});
