<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('drinksafe:make-admin', function (): void {
    it('flags an existing user as an admin', function (): void {
        $user = User::factory()->create();

        $this->artisan('drinksafe:make-admin', ['email' => $user->email])->assertSuccessful();

        expect($user->fresh()->is_admin)->toBeTrue();
    });

    it('removes the admin flag with --revoke', function (): void {
        $user = User::factory()->admin()->create();

        $this->artisan('drinksafe:make-admin', ['email' => $user->email, '--revoke' => true])
            ->expectsOutput(sprintf('%s can no longer review reports.', $user->email))
            ->assertSuccessful();

        expect($user->fresh()->is_admin)->toBeFalse();
    });

    it('creates a verified admin when no user has the email', function (): void {
        $email = fake()->safeEmail();
        $name = fake()->name();
        $password = fake()->password(minLength: 12);

        $this->artisan('drinksafe:make-admin', ['email' => $email])
            ->expectsQuestion('Name', $name)
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->assertSuccessful();

        $user = User::where('email', $email)->sole();
        expect($user)
            ->name->toBe($name)
            ->is_admin->toBeTrue()
            ->email_verified_at->not->toBeNull()
            ->and(Hash::check($password, $user->password))->toBeTrue();
    });

    it('refuses to create a user without interaction', function (): void {
        $email = fake()->safeEmail();

        $this->artisan('drinksafe:make-admin', ['email' => $email, '--no-interaction' => true])
            ->expectsOutput(sprintf('No user has the email %s. Run this again without --no-interaction to create one.', $email))
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => $email]);
    });

    it('refuses an invalid email address', function (): void {
        $this->artisan('drinksafe:make-admin', ['email' => 'not-an-email'])
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    });

    it('fails to revoke when no user has the email', function (): void {
        $email = fake()->safeEmail();

        $this->artisan('drinksafe:make-admin', ['email' => $email, '--revoke' => true])
            ->expectsOutput(sprintf('No user found with the email %s.', $email))
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => $email]);
    });
});
