<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Commands;

use DrinkSafe\Partners\Models\PartnerLink;
use DrinkSafe\Partners\Services\PartnerQrGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * AddPartnerLinkCommand
 *
 * Adds a partner link for /go/{code} and writes its QR code to
 * print/qr/partners/<code>.svg. If segno can't run, the link is still added
 * and the command to make the QR by hand is printed instead.
 */
final class AddPartnerLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drinksafe:partners:add
        {code : Lowercase letters, digits and hyphens, used in /go/{code}}
        {label : The partner\'s name}
        {--destination=/submit-report : Path on this site the link redirects to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a partner QR link and generate its QR code';

    /**
     * Execute the console command.
     */
    public function handle(PartnerQrGenerator $partnerQrGenerator): int
    {
        $validator = Validator::make([
            'code' => $this->argument('code'),
            'label' => $this->argument('label'),
            'destination' => $this->option('destination'),
        ], [
            'code' => ['required', 'string', 'max:50', sprintf('regex:%s', PartnerLink::CODE_PATTERN), Rule::unique(PartnerLink::class, 'code')],
            'label' => ['required', 'string', 'max:100'],
            'destination' => ['required', 'string', 'max:255', sprintf('regex:%s', PartnerLink::DESTINATION_PATTERN)],
        ], [
            'code.regex' => 'The code may only contain lowercase letters, digits and hyphens.',
            'destination.regex' => 'The destination must be a path on this site, starting with a single /.',
        ]);

        if ($validator->fails()) {
            collect($validator->errors()->all())->each(fn (string $message) => $this->error($message));

            return self::FAILURE;
        }

        $partnerLink = PartnerLink::create($validator->validated());

        $this->info(sprintf('Added %s, redirecting %s to %s.', $partnerLink->label, $partnerLink->url(), $partnerLink->destination));

        if (! $partnerQrGenerator->generate($partnerLink)) {
            $this->warn(sprintf("The link was added, but the QR code couldn't be generated. Install uv, then run:\n%s", $partnerQrGenerator->commandLine($partnerLink)));

            return self::SUCCESS;
        }

        $this->info(sprintf('Wrote %s.', $partnerQrGenerator->path($partnerLink)));

        return self::SUCCESS;
    }
}
