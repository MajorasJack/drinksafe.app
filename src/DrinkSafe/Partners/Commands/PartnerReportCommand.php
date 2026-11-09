<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Commands;

use Carbon\CarbonImmutable;
use DrinkSafe\Partners\Services\PartnerScanReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * PartnerReportCommand
 *
 * Prints how many times each partner link was opened, most first, including
 * links with no scans. It only reads.
 */
final class PartnerReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drinksafe:partners:report
        {--since= : First day to count, as YYYY-MM-DD}
        {--until= : Last day to count, as YYYY-MM-DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List partner QR scan totals per code';

    /**
     * Execute the console command.
     */
    public function handle(PartnerScanReport $partnerScanReport): int
    {
        $validator = Validator::make(
            ['since' => $this->option('since'), 'until' => $this->option('until')],
            ['since' => ['nullable', 'date_format:Y-m-d'], 'until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:since']],
        );

        if ($validator->fails()) {
            collect($validator->errors()->all())->each(fn (string $message) => $this->error($message));

            return self::FAILURE;
        }

        $this->table(
            ['Code', 'Label', 'Active', 'Total'],
            $partnerScanReport->totals(
                from: $this->option('since') ? CarbonImmutable::parse($this->option('since')) : null,
                to: $this->option('until') ? CarbonImmutable::parse($this->option('until')) : null,
            )->map(fn (array $partnerTotal): array => [
                $partnerTotal['code'],
                $partnerTotal['label'],
                $partnerTotal['active'] ? 'yes' : 'no',
                $partnerTotal['total'],
            ])->all(),
        );

        return self::SUCCESS;
    }
}
