<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Services;

use Carbon\CarbonImmutable;
use DrinkSafe\Partners\Models\PartnerLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * PartnerScanReport
 *
 * Totals each partner link's scans from the daily counts, most first, with
 * links that had no scans included. Daily totals are the finest detail kept,
 * so nothing here can be finer.
 */
final class PartnerScanReport
{
    /**
     * Total scans per partner link between two UK dates, both included.
     *
     * The dates are compared as Y-m-d strings, the same as the scans are
     * recorded. Leaving out a date leaves that end of the range open.
     *
     * @param  CarbonImmutable|null  $from  First UK date to count
     * @param  CarbonImmutable|null  $to  Last UK date to count
     * @return Collection<int, array{code: string, label: string, destination: string, active: bool, total: int}>
     */
    public function totals(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Collection
    {
        return PartnerLink::query()
            ->withSum(['dailyCounts as total' => fn (Builder $query): Builder => $query
                ->when($from, fn (Builder $query, CarbonImmutable $from): Builder => $query->where('date', '>=', $from->toDateString()))
                ->when($to, fn (Builder $query, CarbonImmutable $to): Builder => $query->where('date', '<=', $to->toDateString())),
            ], 'count')
            ->orderBy('code')
            ->get()
            ->map(fn (PartnerLink $partnerLink): array => [
                'code' => $partnerLink->code,
                'label' => $partnerLink->label,
                'destination' => $partnerLink->destination,
                'active' => $partnerLink->active,
                'total' => (int) $partnerLink->total,
            ])
            ->sortByDesc('total')
            ->values();
    }
}
