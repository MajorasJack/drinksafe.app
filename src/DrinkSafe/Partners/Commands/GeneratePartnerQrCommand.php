<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Commands;

use DrinkSafe\Partners\Models\PartnerLink;
use DrinkSafe\Partners\Services\PartnerQrGenerator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * GeneratePartnerQrCommand
 *
 * Regenerates print/qr/partners/<code>.svg for one existing partner link, or
 * for every link when no code is given.
 */
final class GeneratePartnerQrCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drinksafe:partners:qr
        {code? : One partner code, or leave out for every link}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate partner QR codes';

    /**
     * Execute the console command.
     */
    public function handle(PartnerQrGenerator $partnerQrGenerator): int
    {
        $partnerLinks = PartnerLink::query()
            ->when($this->argument('code'), fn (Builder $query, string $code): Builder => $query->where('code', $code))
            ->orderBy('code')
            ->get();

        if ($partnerLinks->isEmpty()) {
            $this->error(sprintf('No partner link found with the code %s.', $this->argument('code')));

            return self::FAILURE;
        }

        $failedLinks = $partnerLinks->reject(fn (PartnerLink $partnerLink): bool => $partnerQrGenerator->generate($partnerLink));

        $this->info(sprintf('Wrote %d of %d QR codes to print/qr/partners/.', $partnerLinks->count() - $failedLinks->count(), $partnerLinks->count()));

        if ($failedLinks->isNotEmpty()) {
            $this->error(sprintf("Couldn't generate the QR code for %s. Install uv, then run:\n%s", $failedLinks->pluck('code')->implode(', '), $partnerQrGenerator->commandLine($failedLinks->first())));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
