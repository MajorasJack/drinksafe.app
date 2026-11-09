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
 * to create an account, replace a lost password or turn off two-factor for an
 * admin who has lost their device. --revoke takes the flag away again.
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
        {--password : Set a new password for an existing user}
        {--reset-two-factor : Turn off two-factor for an existing user so they can set it up again}';

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

        $chosenOptions = array_filter([$this->option('revoke'), $this->option('password'), $this->option('reset-two-factor')]);

        return match (true) {
            count($chosenOptions) > 1 => $this->failForConflictingOptions(),
            $user === null && $chosenOptions !== [] => $this->failForMissingUser($email),
            $user === null => $this->createAdmin($email),
            $this->option('password') => $this->resetPassword($user),
            $this->option('reset-two-factor') => $this->resetTwoFactor($user),
            default => $this->setAdminFlag($user),
        };
    }

    /**
     * Refuse more than one of --revoke, --password and --reset-two-factor, as the command does one at a time.
     */
    private function failForConflictingOptions(): int
    {
        $this->error('Use only one of --revoke, --password and --reset-two-factor.');

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
     * every login made with the old one.
     */
    private function resetPassword(User $user): int
    {
        if (! $this->input->isInteractive()) {
            $this->error(sprintf('Run this again without --no-interaction to set a new password for %s.', $user->email));

            return self::FAILURE;
        }

        $this->updateAndEndSessions($user, ['password' => $this->askForPassword()]);

        $this->info(sprintf('Updated the password for %s.', $user->email));

        return self::SUCCESS;
    }

    /**
     * Turn off two-factor for a user who has lost their device.
     *
     * Remember-me cookies and database sessions are ended in case the device
     * was stolen. A cookie session stays logged in, as the password has not
     * changed, but the admin area refuses it until two-factor is set up again,
     * and that asks for the password.
     */
    private function resetTwoFactor(User $user): int
    {
        $this->updateAndEndSessions($user, [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $this->info(sprintf('Turned off two-factor for %s. They will be asked to set it up again at their next admin visit.', $user->email));

        if (config('session.driver') !== 'database') {
            $this->warn('Existing logins are only ended by --password. Run that too if the device may be compromised.');
        }

        return self::SUCCESS;
    }

    /**
     * Save the given attributes and end every existing login of the user.
     *
     * Rotating the remember token stops remember-me cookies, and deleting
     * session rows covers the database driver. The `auth.session` middleware
     * logs out cookie sessions on their next request when the password changes.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function updateAndEndSessions(User $user, array $attributes): void
    {
        $user->forceFill([...$attributes, 'remember_token' => Str::random(60)])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table'))->where('user_id', $user->id)->delete();
        }
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
