<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Commands;

use DrinkSafe\Partners\Models\PartnerLink;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
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
    public function handle(): int
    {
        $validator = Validator::make(
            ['since' => $this->option('since'), 'until' => $this->option('until')],
            ['since' => ['nullable', 'date_format:Y-m-d'], 'until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:since']],
        );

        if ($validator->fails()) {
            collect($validator->errors()->all())->each(fn (string $message) => $this->error($message));

            return self::FAILURE;
        }

        $partnerLinks = PartnerLink::query()
            ->withSum(['dailyCounts as total' => fn (Builder $query): Builder => $query
                ->when($this->option('since'), fn (Builder $query, string $since): Builder => $query->where('date', '>=', $since))
                ->when($this->option('until'), fn (Builder $query, string $until): Builder => $query->where('date', '<=', $until)),
            ], 'count')
            ->orderBy('code')
            ->get()
            ->sortByDesc(fn (PartnerLink $partnerLink): int => (int) $partnerLink->total)
            ->values();

        $this->table(
            ['Code', 'Label', 'Active', 'Total'],
            $partnerLinks->map(fn (PartnerLink $partnerLink): array => [
                $partnerLink->code,
                $partnerLink->label,
                $partnerLink->active ? 'yes' : 'no',
                (int) $partnerLink->total,
            ])->all(),
        );

        return self::SUCCESS;
    }
}
