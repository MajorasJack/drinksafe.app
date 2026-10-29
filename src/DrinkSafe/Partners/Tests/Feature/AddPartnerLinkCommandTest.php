<?php

declare(strict_types=1);

use DrinkSafe\Partners\Models\PartnerLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('drinksafe:partners:add', function (): void {
    it('adds an active link that goes to the report form by default', function (): void {
        Process::fake();
        $label = fake()->company();

        $this->artisan('drinksafe:partners:add', ['code' => 'new-partner', 'label' => $label])->assertSuccessful();

        expect(PartnerLink::query()->where('code', 'new-partner')->first(['label', 'destination', 'active'])->toArray())
            ->toBe(['label' => $label, 'destination' => '/submit-report', 'active' => true]);
    });

    it('generates the QR code with the pinned segno command', function (): void {
        Process::fake();

        $this->artisan('drinksafe:partners:add', ['code' => 'new-partner', 'label' => fake()->company()])
            ->expectsOutput(sprintf('Wrote %s.', base_path('print/qr/partners/new-partner.svg')))
            ->assertSuccessful();

        Process::assertRan(fn (PendingProcess $process): bool => $process->command === [
            'uvx',
            '--with',
            'segno==1.6.6',
            'python',
            '-c',
            <<<'PYTHON'
                import sys, segno
                qr = segno.make(sys.argv[1], error='m', micro=False, boost_error=False)
                qr.save(sys.argv[2], scale=106 / qr.symbol_size()[0], unit='mm',
                        dark='#0b1220', light='#fff', svgclass=None, lineclass=None, nl=False)
                open(sys.argv[2], 'a').write('\n')
                PYTHON,
            'https://drinksafe.app/go/new-partner',
            base_path('print/qr/partners/new-partner.svg'),
        ]);
    });

    it('stores a destination given with --destination', function (): void {
        Process::fake();

        $this->artisan('drinksafe:partners:add', ['code' => 'new-partner', 'label' => fake()->company(), '--destination' => '/map'])
            ->assertSuccessful();

        expect(PartnerLink::query()->where('code', 'new-partner')->value('destination'))->toBe('/map');
    });

    it('still adds the link and prints the command to run by hand when segno cannot run', function (): void {
        Process::fake(['*' => Process::result(errorOutput: 'uvx: command not found', exitCode: 127)]);

        $this->artisan('drinksafe:partners:add', ['code' => 'new-partner', 'label' => fake()->company()])
            ->expectsOutputToContain("The link was added, but the QR code couldn't be generated. Install uv, then run:\n'uvx' '--with' 'segno==1.6.6' 'python' '-c'")
            ->assertSuccessful();

        expect(PartnerLink::query()->where('code', 'new-partner')->exists())->toBeTrue();
    });

    it('rejects a code that is not lowercase letters, digits and hyphens', function (string $code): void {
        Process::fake();

        $this->artisan('drinksafe:partners:add', ['code' => $code, 'label' => fake()->company()])
            ->expectsOutput('The code may only contain lowercase letters, digits and hyphens.')
            ->assertFailed();

        expect(PartnerLink::query()->where('code', $code)->exists())->toBeFalse();
        Process::assertNothingRan();
    })->with([
        'uppercase' => 'Partner',
        'space' => 'new partner',
        'slash' => 'new/partner',
        'dot' => 'new.partner',
        'underscore' => 'new_partner',
    ]);

    it('rejects a code that is already taken', function (): void {
        Process::fake();

        $this->artisan('drinksafe:partners:add', ['code' => 'uom', 'label' => fake()->company()])
            ->expectsOutput('The code has already been taken.')
            ->assertFailed();

        Process::assertNothingRan();
    });

    it('rejects a destination that is not a path on this site', function (string $destination): void {
        Process::fake();

        $this->artisan('drinksafe:partners:add', ['code' => 'new-partner', 'label' => fake()->company(), '--destination' => $destination])
            ->expectsOutput('The destination must be a path on this site, starting with a single /.')
            ->assertFailed();

        expect(PartnerLink::query()->where('code', 'new-partner')->exists())->toBeFalse();
    })->with([
        'protocol relative' => '//evil.example',
        'absolute' => 'https://evil.example/submit-report',
        'backslash' => '/\\evil.example',
        'relative' => 'submit-report',
        'scheme' => 'javascript:alert(1)',
        'whitespace' => "/\t/evil.example",
    ]);
});
