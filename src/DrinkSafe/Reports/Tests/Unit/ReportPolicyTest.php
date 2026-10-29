<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Reports\Policies\ReportPolicy;
use Tests\TestCase;

uses(TestCase::class);

describe('ReportPolicy', function (): void {
    it('lets admins moderate reports', function (): void {
        $user = User::factory()->admin()->make();

        expect((new ReportPolicy)->moderate($user))->toBeTrue();
    });

    it('does not let other users moderate reports', function (): void {
        $user = User::factory()->make();

        expect((new ReportPolicy)->moderate($user))->toBeFalse();
    });
});
