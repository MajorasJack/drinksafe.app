<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Commands;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * MakeAdminCommand
 *
 * Gives a user the admin flag so they can work the report review queue. The
 * site has no registration, so this command is the only way to create an
 * account. --revoke takes the flag away again.
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
        {--revoke : Remove the admin flag instead of setting it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Let a user review and publish reports, creating them if needed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            return $this->option('revoke') ? $this->failForMissingUser() : $this->createAdmin();
        }

        $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();

        $this->info($this->option('revoke')
            ? sprintf('%s can no longer review reports.', $user->email)
            : sprintf('%s can now review reports at /admin/reports.', $user->email));

        return self::SUCCESS;
    }

    /**
     * Report that there is no user to revoke.
     */
    private function failForMissingUser(): int
    {
        $this->error(sprintf('No user found with the email %s.', $this->argument('email')));

        return self::FAILURE;
    }

    /**
     * Create a verified admin, asking for their name and password.
     *
     * Without input there is no password to set, so the command stops rather
     * than create an account nobody can log in to.
     */
    private function createAdmin(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error(sprintf('No user has the email %s. Run this again without --no-interaction to create one.', $this->argument('email')));

            return self::FAILURE;
        }

        $emailValidator = Validator::make(['email' => $this->argument('email')], ['email' => $this->emailRules()]);

        if ($emailValidator->fails()) {
            $this->error($emailValidator->errors()->first('email'));

            return self::FAILURE;
        }

        $name = text(label: 'Name', required: true, validate: ['name' => $this->nameRules()]);
        $password = password(label: 'Password', required: true, validate: ['password' => ['required', 'string', Password::defaults()]]);

        password(
            label: 'Confirm password',
            required: true,
            validate: fn (string $confirmation): ?string => $confirmation === $password ? null : 'The passwords do not match.',
        );

        $user = User::forceCreate([
            'name' => $name,
            'email' => $this->argument('email'),
            'password' => $password,
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $this->info(sprintf('Created %s. They can now review reports at /admin/reports.', $user->email));

        return self::SUCCESS;
    }
}
