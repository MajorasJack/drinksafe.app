<?php

declare(strict_types=1);

use DrinkSafe\Partners\Models\PartnerLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('drinksafe:partners:qr', function (): void {
    it('regenerates the QR code for every partner link', function (): void {
        Process::fake();

        $this->artisan('drinksafe:partners:qr')
            ->expectsOutput(sprintf('Wrote %1$d of %1$d QR codes to print/qr/partners/.', PartnerLink::query()->count()))
            ->assertSuccessful();

        Process::assertRanTimes(fn (PendingProcess $process): bool => $process->command[0] === 'uvx', PartnerLink::query()->count());
    });

    it('regenerates the QR code for one code', function (): void {
        Process::fake();

        $this->artisan('drinksafe:partners:qr', ['code' => 'uom'])->assertSuccessful();

        Process::assertRanTimes(fn (PendingProcess $process): bool => $process->command[6] === 'https://drinksafe.app/go/uom'
            && $process->command[7] === base_path('print/qr/partners/uom.svg'), 1);
    });

    it('fails for a code that does not exist', function (): void {
        Process::fake();

        $this->artisan('drinksafe:partners:qr', ['code' => 'not-a-partner'])
            ->expectsOutput('No partner link found with the code not-a-partner.')
            ->assertFailed();

        Process::assertNothingRan();
    });

    it('fails and prints the command to run by hand when segno cannot run', function (): void {
        Process::fake(['*' => Process::result(exitCode: 127)]);

        $this->artisan('drinksafe:partners:qr', ['code' => 'uom'])
            ->expectsOutputToContain("Couldn't generate the QR code for uom. Install uv, then run:")
            ->assertFailed();
    });
});
