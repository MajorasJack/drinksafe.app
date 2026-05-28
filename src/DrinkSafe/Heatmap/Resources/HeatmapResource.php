<?php

declare(strict_types=1);

namespace DrinkSafe\Heatmap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * HeatmapResource
 *
 * Transforms heatmap data into a consistent JSON API response.
 * Wraps points array with metadata about the query.
 *
 * @property array{
 *     points: array<int, array{lat: float, lng: float, intensity: float}>,
 *     period_days: int,
 *     total_reports: int
 * } $resource
 */
final class HeatmapResource extends JsonResource
{
    /**
     * The resource being transformed.
     *
     * @var array{
     *     points: array<int, array{lat: float, lng: float, intensity: float}>,
     *     period_days: int,
     *     total_reports: int
     * }
     */
    public $resource;

    /**
     * Create a new resource instance.
     *
     * @param  array{
     *     points: array<int, array{lat: float, lng: float, intensity: float}>,
     *     period_days: int,
     *     total_reports: int
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
     *     points: array<int, array{lat: float, lng: float, intensity: float}>,
     *     period_days: int,
     *     total_reports: int
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'points' => $this->resource['points'],
            'period_days' => $this->resource['period_days'],
            'total_reports' => $this->resource['total_reports'],
        ];
    }
}
