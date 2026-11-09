<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Services;

use Carbon\CarbonImmutable;
use DrinkSafe\Partners\Services\PartnerScanReport;
use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Scopes\PublishedScope;
use DrinkSafe\Reports\Services\ReportService;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AdminDashboardStats
 *
 * The numbers on the admin dashboard. Every one is an aggregate, so nothing
 * here returns a report or a venue. "The last 30 days" means the last 30 UK
 * dates, today included, the same as the partner scans are recorded.
 */
final class AdminDashboardStats
{
    /**
     * Timezone the dashboard's days are counted in.
     */
    private const string TIMEZONE = 'Europe/London';

    /**
     * How many days the dashboard looks back, today included.
     */
    private const int DAYS = 30;

    /**
     * How many partner links the dashboard lists.
     */
    private const int TOP_PARTNERS = 5;

    /**
     * Create a new AdminDashboardStats instance.
     */
    public function __construct(
        private readonly ReportService $reportService,
        private readonly PartnerScanReport $partnerScanReport,
    ) {}

    /**
     * Build the dashboard summary.
     *
     * @return array{pending_count: int, oldest_pending_at: ?string, received_per_day: array<string, int>, published_30d: int, rejected_30d: int, top_partners: list<array{code: string, label: string, destination: string, active: bool, total: int}>, orphaned_venues: int}
     */
    public function summary(): array
    {
        $firstDay = CarbonImmutable::today(self::TIMEZONE)->subDays(self::DAYS - 1);
        $decidedCounts = $this->countDecidedSince($firstDay);

        return [
            'pending_count' => $this->reportService->countPendingReports(),
            'oldest_pending_at' => Report::withoutGlobalScope(PublishedScope::class)
                ->where('status', ReportStatus::Pending)
                ->oldest()
                ->value('created_at')
                ?->toIso8601String(),
            'received_per_day' => $this->countReceivedPerDay($firstDay),
            'published_30d' => (int) $decidedCounts->get(ReportStatus::Published->value, 0),
            'rejected_30d' => (int) $decidedCounts->get(ReportStatus::Rejected->value, 0),
            'top_partners' => $this->partnerScanReport
                ->totals(from: $firstDay, to: $firstDay->addDays(self::DAYS - 1))
                ->take(self::TOP_PARTNERS)
                ->all(),
            'orphaned_venues' => Venue::withoutGlobalScope(PublishedVenueScope::class)->orphaned()->count(),
        ];
    }

    /**
     * Count the reports received on each UK date from the first day to today,
     * in every status, with a zero for a day that had none.
     *
     * The database groups by UTC hour and PHP moves each hour to its UK date.
     * The UK is always a whole number of hours from UTC, so no hour straddles
     * two UK dates, and the dates stay right across the clock changes.
     *
     * @param  CarbonImmutable  $firstDay  Midnight on the first UK date
     * @return array<string, int> Count per UK date, as Y-m-d, oldest first
     */
    private function countReceivedPerDay(CarbonImmutable $firstDay): array
    {
        $utcHour = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d %H', created_at)",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d %H')",
        };

        $receivedByDate = Report::withoutGlobalScope(PublishedScope::class)
            ->where('created_at', '>=', $firstDay->utc())
            ->toBase()
            ->selectRaw(sprintf('%s as utc_hour, count(*) as received', $utcHour))
            ->groupBy('utc_hour')
            ->get()
            ->groupBy(fn (object $row): string => CarbonImmutable::createFromFormat('Y-m-d H|', $row->utc_hour, 'UTC')
                ->setTimezone(self::TIMEZONE)
                ->toDateString())
            ->map(fn (Collection $rows): int => (int) $rows->sum('received'));

        return collect(range(0, self::DAYS - 1))
            ->map(fn (int $offset): string => $firstDay->addDays($offset)->toDateString())
            ->mapWithKeys(fn (string $date): array => [$date => $receivedByDate->get($date, 0)])
            ->all();
    }

    /**
     * Count the reports published and rejected since the first day, by status.
     *
     * A takedown sets a report to rejected, so takedowns count as rejections.
     *
     * @param  CarbonImmutable  $firstDay  Midnight on the first UK date
     * @return Collection<string, int> Count per status value
     */
    private function countDecidedSince(CarbonImmutable $firstDay): Collection
    {
        return Report::withoutGlobalScope(PublishedScope::class)
            ->whereIn('status', [ReportStatus::Published, ReportStatus::Rejected])
            ->where('reviewed_at', '>=', $firstDay->utc())
            ->toBase()
            ->selectRaw('status, count(*) as decided')
            ->groupBy('status')
            ->pluck('decided', 'status');
    }
}
