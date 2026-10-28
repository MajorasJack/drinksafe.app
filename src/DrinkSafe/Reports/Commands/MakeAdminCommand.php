<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * MakeAdminCommand
 *
 * Flags an existing user as an admin so they can work the report review
 * queue. Admins can't be created through the site, only with this command.
 */
final class MakeAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drinksafe:make-admin {email : Email address of an existing user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Let an existing user review and publish reports';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error(sprintf('No user found with the email %s.', $this->argument('email')));

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => true])->save();

        $this->info(sprintf('%s can now review reports at /admin/reports.', $user->email));

        return self::SUCCESS;
    }
}
