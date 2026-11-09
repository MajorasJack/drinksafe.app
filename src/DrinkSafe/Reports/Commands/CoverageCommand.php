<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Commands;

use DrinkSafe\Reports\Services\CoverageReport;
use Illuminate\Console\Command;

/**
 * CoverageCommand
 *
 * Prints how many published venues, published reports and pending reports
 * each city has, to show where the site has something to show before
 * outreach starts there. It only reads.
 */
final class CoverageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drinksafe:coverage
        {--city=* : City to count instead of the default list (repeatable)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Count published venues and reports per city';

    /**
     * Execute the console command.
     */
    public function handle(CoverageReport $coverageReport): int
    {
        $this->table(
            ['City', 'Venues', 'Published reports', 'Pending reports'],
            array_map(array_values(...), $coverageReport->forCities($this->option('city'))),
        );

        $this->line("Counts use the venue's stored city, so venues in central Salford may be stored as Manchester.");

        return self::SUCCESS;
    }
}
