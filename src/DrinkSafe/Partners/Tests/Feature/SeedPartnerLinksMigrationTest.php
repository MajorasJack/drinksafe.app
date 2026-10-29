<?php

declare(strict_types=1);

use DrinkSafe\Partners\Models\PartnerLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('partner links seed migration', function (): void {
    it('creates every first-round partner code as an active link to the report form', function (): void {
        $codes = ['uom', 'mmu', 'salford', 'ugm', 'rncm', 'tmc', 'xaverian', 'hopwood', 'tscg', 'loreto', 'cityco', 'village-angels', 'girl-gang', 'girls-who-walk', 'her-social', 'vim', 'sos', 'hallam', 'liverpool-guild', 'ntu', 'bham', 'bristol-su', 'cardiff', 'glasgow', 'newcastle', 'leeds-beckett', 'reddit', 'press'];

        $partnerLinks = PartnerLink::query()->orderBy('code')->get();

        expect($partnerLinks->pluck('code')->all())->toBe(collect($codes)->sort()->values()->all())
            ->and($partnerLinks->every(fn (PartnerLink $partnerLink): bool => $partnerLink->active && $partnerLink->destination === '/submit-report'))->toBeTrue();
    });

    it('labels each code with a readable version of it', function (): void {
        expect(PartnerLink::query()->whereIn('code', ['uom', 'girls-who-walk'])->orderBy('code')->pluck('label')->all())
            ->toBe(['Girls Who Walk', 'Uom']);
    });
});
