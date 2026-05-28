<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TemporalAnalyticsResource
 *
 * Transforms temporal analytics data into a consistent JSON API response.
 * Wraps the grid, totals, insights, and metadata in a standardised format.
 *
 * @property array{
 *     grid: array<int, array{day: string, time: string, count: int, percentage: float}>,
 *     totals: array{by_day: array<string, int>, by_time: array<string, int>},
 *     insights: array<int, string>,
 *     meta: array{period_days: int, total_reports: int, city: string|null, venue_uuid: string|null}
 * } $resource
 */
final class TemporalAnalyticsResource extends JsonResource
{
    /**
     * The resource being transformed.
     *
     * @var array{
     *     grid: array<int, array{day: string, time: string, count: int, percentage: float}>,
     *     totals: array{by_day: array<string, int>, by_time: array<string, int>},
     *     insights: array<int, string>,
     *     meta: array{period_days: int, total_reports: int, city: string|null, venue_uuid: string|null}
     * }
     */
    public $resource;

    /**
     * Create a new resource instance.
     *
     * @param  array{
     *     grid: array<int, array{day: string, time: string, count: int, percentage: float}>,
     *     totals: array{by_day: array<string, int>, by_time: array<string, int>},
     *     insights: array<int, string>,
     *     meta: array{period_days: int, total_reports: int, city: string|null, venue_uuid: string|null}
     * }  $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     grid: array<int, array{day: string, time: string, count: int, percentage: float}>,
     *     totals: array{by_day: array<string, int>, by_time: array<string, int>},
     *     insights: array<int, string>,
     *     meta: array{period_days: int, total_reports: int, city: string|null, venue_uuid: string|null}
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'grid' => $this->resource['grid'],
            'totals' => $this->resource['totals'],
            'insights' => $this->resource['insights'],
            'meta' => $this->resource['meta'],
        ];
    }
}
