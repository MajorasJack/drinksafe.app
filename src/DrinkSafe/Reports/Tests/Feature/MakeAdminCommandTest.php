<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

    it('creates an admin when no user has the email', function (): void {
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
            ->expectsOutput('The email field must be a valid email address.')
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

    it('stores a padded mixed-case email in lowercase', function (): void {
        $password = fake()->password(minLength: 12);

        $this->artisan('drinksafe:make-admin', ['email' => ' Admin@Example.com '])
            ->expectsQuestion('Name', fake()->name())
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->assertSuccessful();

        expect(User::sole()->email)->toBe('admin@example.com');
    });

    it('finds an existing user whatever the case of the email', function (): void {
        $user = User::factory()->create(['email' => Str::lower(fake()->safeEmail())]);

        $this->artisan('drinksafe:make-admin', ['email' => Str::upper($user->email)])->assertSuccessful();

        expect($user->fresh()->is_admin)->toBeTrue();
        $this->assertDatabaseCount('users', 1);
    });

    it('asks for both passwords again when the confirmation does not match', function (): void {
        $email = fake()->safeEmail();
        $password = fake()->password(minLength: 12);

        $this->artisan('drinksafe:make-admin', ['email' => $email])
            ->expectsQuestion('Name', fake()->name())
            ->expectsQuestion('Password', fake()->password(minLength: 12))
            ->expectsQuestion('Confirm password', fake()->password(minLength: 12))
            ->expectsOutput('The passwords do not match. Enter both again.')
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->assertSuccessful();

        expect(Hash::check($password, User::where('email', $email)->sole()->password))->toBeTrue();
    });

    it('sets a new password for an existing user with --password', function (): void {
        $user = User::factory()->create();
        $password = fake()->password(minLength: 12);

        $this->artisan('drinksafe:make-admin', ['email' => $user->email, '--password' => true])
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->expectsOutput(sprintf('Updated the password for %s.', $user->email))
            ->doesntExpectOutputToContain($password)
            ->assertSuccessful();

        expect(Hash::check($password, $user->fresh()->password))->toBeTrue();
    });

    it('leaves the admin flag alone with --password', function (): void {
        $user = User::factory()->create();
        $password = fake()->password(minLength: 12);

        $this->artisan('drinksafe:make-admin', ['email' => $user->email, '--password' => true])
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->assertSuccessful();

        expect($user->fresh()->is_admin)->toBeFalse();
    });

    it('refuses to set a password without interaction', function (): void {
        $user = User::factory()->create();

        $this->artisan('drinksafe:make-admin', ['email' => $user->email, '--password' => true, '--no-interaction' => true])
            ->expectsOutput(sprintf('Run this again without --no-interaction to set a new password for %s.', $user->email))
            ->assertFailed();

        expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    });

    it('fails to set a password when no user has the email', function (): void {
        $email = fake()->safeEmail();

        $this->artisan('drinksafe:make-admin', ['email' => $email, '--password' => true])
            ->expectsOutput(sprintf('No user found with the email %s.', $email))
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => $email]);
    });

    it('rotates the remember token with --password', function (): void {
        $user = User::factory()->create(['remember_token' => Str::random(60)]);
        $password = fake()->password(minLength: 12);

        $this->artisan('drinksafe:make-admin', ['email' => $user->email, '--password' => true])
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->assertSuccessful();

        expect($user->fresh()->remember_token)->not->toBe($user->remember_token);
    });

    it('deletes the database sessions of the user with --password', function (): void {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $password = fake()->password(minLength: 12);
        DB::table('sessions')->insert([
            ['id' => Str::random(40), 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => Str::random(40), 'user_id' => $otherUser->id, 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        $this->artisan('drinksafe:make-admin', ['email' => $user->email, '--password' => true])
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->assertSuccessful();

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['user_id' => $otherUser->id]);
    });

    it('logs out a session made before --password on its next request', function (): void {
        $user = User::factory()->admin()->create();
        $password = fake()->password(minLength: 12);

        $this->artisan('drinksafe:make-admin', ['email' => $user->email, '--password' => true])
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->assertSuccessful();

        $this->actingAs($user->fresh())
            ->withSession(['password_hash_web' => $user->getAuthPassword()])
            ->getJson(route('admin.reports.index'))
            ->assertUnauthorized();
    });

    it('keeps a session made with the current password logged in', function (): void {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->withSession(['password_hash_web' => $user->getAuthPassword()])
            ->getJson(route('admin.reports.index'))
            ->assertOk();
    });

    it('refuses --revoke together with --password', function (): void {
        $user = User::factory()->admin()->create();

        $this->artisan('drinksafe:make-admin', ['email' => $user->email, '--revoke' => true, '--password' => true])
            ->expectsOutput('Use either --revoke or --password, not both.')
            ->assertFailed();

        expect($user->fresh()->is_admin)->toBeTrue();
    });
});
