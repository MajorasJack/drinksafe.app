<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Commands;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * MakeAdminCommand
 *
 * Gives a user the admin flag so they can work the report review queue. The
 * site has no registration or password reset, so this command is the only way
 * to create an account or replace a lost password. --revoke takes the flag
 * away again.
 */
final class MakeAdminCommand extends Command
{
    use ProfileValidationRules;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drinksafe:make-admin
        {email : Email address of the admin, created if no user has it yet}
        {--revoke : Remove the admin flag instead of setting it}
        {--password : Set a new password for an existing user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Let a user review and publish reports, creating them if needed';

    /**
     * Execute the console command.
     *
     * The email is lowercased because Fortify lowercases it on login, so an
     * account stored with capitals could never log in.
     */
    public function handle(): int
    {
        $email = Str::lower(trim($this->argument('email')));
        $user = User::where('email', $email)->first();

        return match (true) {
            $this->option('revoke') && $this->option('password') => $this->failForConflictingOptions(),
            $user === null && ($this->option('revoke') || $this->option('password')) => $this->failForMissingUser($email),
            $user === null => $this->createAdmin($email),
            $this->option('password') => $this->resetPassword($user),
            default => $this->setAdminFlag($user),
        };
    }

    /**
     * Refuse --revoke and --password together, as the command does one or the other.
     */
    private function failForConflictingOptions(): int
    {
        $this->error('Use either --revoke or --password, not both.');

        return self::FAILURE;
    }

    /**
     * Report that there is no user to revoke or reset.
     */
    private function failForMissingUser(string $email): int
    {
        $this->error(sprintf('No user found with the email %s.', $email));

        return self::FAILURE;
    }

    /**
     * Set or remove the admin flag on an existing user.
     */
    private function setAdminFlag(User $user): int
    {
        $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();

        $this->info($this->option('revoke')
            ? sprintf('%s can no longer review reports.', $user->email)
            : sprintf('%s can now review reports at /admin/reports.', $user->email));

        return self::SUCCESS;
    }

    /**
     * Replace an existing user's password, leaving the admin flag as it is.
     *
     * A lost or stolen password is the reason to run this, so it also ends
     * every login made with the old one. Rotating the remember token stops
     * remember-me cookies, deleting session rows covers the database driver,
     * and the `auth.session` middleware logs out cookie sessions on their next
     * request.
     */
    private function resetPassword(User $user): int
    {
        if (! $this->input->isInteractive()) {
            $this->error(sprintf('Run this again without --no-interaction to set a new password for %s.', $user->email));

            return self::FAILURE;
        }

        $user->forceFill([
            'password' => $this->askForPassword(),
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table'))->where('user_id', $user->id)->delete();
        }

        $this->info(sprintf('Updated the password for %s.', $user->email));

        return self::SUCCESS;
    }

    /**
     * Create an admin, asking for their name and password.
     *
     * Without input there is no password to set, so the command stops rather
     * than create an account nobody can log in to.
     */
    private function createAdmin(string $email): int
    {
        if (! $this->input->isInteractive()) {
            $this->error(sprintf('No user has the email %s. Run this again without --no-interaction to create one.', $email));

            return self::FAILURE;
        }

        $emailValidator = Validator::make(['email' => $email], ['email' => $this->emailRules()]);

        if ($emailValidator->fails()) {
            $this->error($emailValidator->errors()->first('email'));

            return self::FAILURE;
        }

        $user = User::forceCreate([
            'name' => text(label: 'Name', required: true, validate: ['name' => $this->nameRules()]),
            'email' => $email,
            'password' => $this->askForPassword(),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $this->info(sprintf('Created %s. They can now review reports at /admin/reports.', $user->email));

        return self::SUCCESS;
    }

    /**
     * Ask for a password twice, starting both prompts again if they differ.
     *
     * Both are asked again because the mistake could be in either entry.
     */
    private function askForPassword(): string
    {
        $password = password(label: 'Password', required: true, validate: ['password' => ['required', 'string', Password::defaults()]]);

        if (password(label: 'Confirm password', required: true) === $password) {
            return $password;
        }

        $this->error('The passwords do not match. Enter both again.');

        return $this->askForPassword();
    }
}
