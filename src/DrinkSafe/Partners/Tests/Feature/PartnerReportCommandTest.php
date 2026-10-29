<?php

declare(strict_types=1);

use DrinkSafe\Partners\Models\PartnerLink;
use DrinkSafe\Partners\Models\PartnerLinkDailyCount;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('drinksafe:partners:report', function (): void {
    beforeEach(function (): void {
        PartnerLink::query()->delete();
    });

    it('lists each code with its total scans, most first, including codes with none', function (): void {
        $busy = PartnerLink::factory()->create(['code' => 'busy', 'label' => 'Busy Union']);
        $quiet = PartnerLink::factory()->inactive()->create(['code' => 'quiet', 'label' => 'Quiet Group']);
        PartnerLink::factory()->create(['code' => 'unused', 'label' => 'Unused Venue']);
        PartnerLinkDailyCount::factory()->for($busy)->on('2026-10-01')->create(['count' => 7]);
        PartnerLinkDailyCount::factory()->for($busy)->on('2026-10-02')->create(['count' => 5]);
        PartnerLinkDailyCount::factory()->for($quiet)->on('2026-10-01')->create(['count' => 3]);

        $this->artisan('drinksafe:partners:report')
            ->expectsTable(['Code', 'Label', 'Active', 'Total'], [
                ['busy', 'Busy Union', 'yes', 12],
                ['quiet', 'Quiet Group', 'no', 3],
                ['unused', 'Unused Venue', 'yes', 0],
            ])
            ->assertSuccessful();
    });

    it('counts only the days between --since and --until', function (): void {
        $partnerLink = PartnerLink::factory()->create(['code' => 'busy', 'label' => 'Busy Union']);
        PartnerLinkDailyCount::factory()->for($partnerLink)->on('2026-09-30')->create(['count' => 100]);
        PartnerLinkDailyCount::factory()->for($partnerLink)->on('2026-10-01')->create(['count' => 4]);
        PartnerLinkDailyCount::factory()->for($partnerLink)->on('2026-10-31')->create(['count' => 6]);
        PartnerLinkDailyCount::factory()->for($partnerLink)->on('2026-11-01')->create(['count' => 100]);

        $this->artisan('drinksafe:partners:report', ['--since' => '2026-10-01', '--until' => '2026-10-31'])
            ->expectsTable(['Code', 'Label', 'Active', 'Total'], [['busy', 'Busy Union', 'yes', 10]])
            ->assertSuccessful();
    });

    it('rejects a date that is not YYYY-MM-DD', function (): void {
        $this->artisan('drinksafe:partners:report', ['--since' => '01/10/2026'])
            ->expectsOutput('The since field must match the format Y-m-d.')
            ->assertFailed();
    });

    it('rejects an --until before --since', function (): void {
        $this->artisan('drinksafe:partners:report', ['--since' => '2026-10-02', '--until' => '2026-10-01'])
            ->expectsOutput('The until field must be a date after or equal to since.')
            ->assertFailed();
    });

    it('reads the totals in one select query', function (): void {
        PartnerLinkDailyCount::factory()->count(3)->create();
        $statements = collect();
        DB::listen(fn (QueryExecuted $query): Collection => $statements->push($query->sql));

        $this->artisan('drinksafe:partners:report')->assertSuccessful();

        expect($statements)->toHaveCount(1)
            ->and(strtolower($statements->first()))->toStartWith('select');
    });
});
