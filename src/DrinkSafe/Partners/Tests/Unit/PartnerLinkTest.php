<?php

declare(strict_types=1);

use DrinkSafe\Partners\Models\PartnerLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('PartnerLink', function (): void {
    it('accepts a path on this site as a destination', function (string $destination): void {
        expect(PartnerLink::isInternalPath($destination))->toBeTrue();
    })->with(['/', '/submit-report', '/map?search=Leeds&radius=5', '/venues/the-crown-inn']);

    it('rejects anything that could leave this site as a destination', function (string $destination): void {
        expect(PartnerLink::isInternalPath($destination))->toBeFalse();
    })->with([
        'protocol relative' => '//evil.example',
        'backslash' => '/\\evil.example',
        'absolute' => 'https://evil.example',
        'scheme' => 'javascript:alert(1)',
        'relative' => 'submit-report',
        'tab' => "/\t/evil.example",
        'newline' => "/\n/evil.example",
        'trailing newline' => "/submit-report\n",
        'empty' => '',
    ]);

    it('records scans with one row per day', function (): void {
        $partnerLink = PartnerLink::factory()->create();

        $partnerLink->recordScan();
        $partnerLink->recordScan();

        expect($partnerLink->dailyCounts()->get(['date', 'count'])->toArray())
            ->toBe([['date' => today('Europe/London')->toDateString(), 'count' => 2]]);
    });

    it('builds the address its QR code points at', function (): void {
        $partnerLink = PartnerLink::factory()->make(['code' => 'girls-who-walk']);

        expect($partnerLink->url())->toBe('https://drinksafe.app/go/girls-who-walk');
    });
});
