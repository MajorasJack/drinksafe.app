<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('drinksafe:make-admin', function (): void {
    it('flags an existing user as an admin', function (): void {
        $user = User::factory()->create();

        $this->artisan('drinksafe:make-admin', ['email' => $user->email])->assertSuccessful();

        expect($user->fresh()->is_admin)->toBeTrue();
    });

    it('fails when no user has the email', function (): void {
        $email = fake()->safeEmail();

        $this->artisan('drinksafe:make-admin', ['email' => $email])
            ->expectsOutput(sprintf('No user found with the email %s.', $email))
            ->assertFailed();
    });
});
