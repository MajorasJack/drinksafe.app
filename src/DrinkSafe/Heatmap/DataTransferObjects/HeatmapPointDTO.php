<?php

declare(strict_types=1);

namespace DrinkSafe\Heatmap\DataTransferObjects;

/**
 * HeatmapPointDTO
 *
 * Data transfer object representing a single heatmap point.
 * Contains geographic coordinates and normalised intensity value.
 *
 * @property-read float $latitude Grid cell centre latitude
 * @property-read float $longitude Grid cell centre longitude
 * @property-read float $intensity Normalised intensity value (0.0 to 1.0)
 */
final readonly class HeatmapPointDTO
{
    /**
     * Create a new HeatmapPointDTO instance.
     *
     * @param  float  $latitude  Grid cell centre latitude
     * @param  float  $longitude  Grid cell centre longitude
     * @param  float  $intensity  Normalised intensity value (0.0 to 1.0)
     */
    public function __construct(
        public float $latitude,
        public float $longitude,
        public float $intensity,
    ) {}

    /**
     * Convert the DTO to an array for JSON serialisation.
     *
     * @return array{lat: float, lng: float, intensity: float}
     */
    public function toArray(): array
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'intensity' => $this->intensity,
        ];
    }

    /**
     * Create a HeatmapPointDTO from database row data.
     *
     * @param  object  $row  Database row with grid_lat, grid_lng, and report_count
     * @param  int  $maxReportsForNormalisation  Maximum reports for intensity normalisation
     */
    public static function fromDatabaseRow(object $row, int $maxReportsForNormalisation): self
    {
        $intensity = min(1.0, (float) $row->report_count / $maxReportsForNormalisation);

        return new self(
            latitude: (float) $row->grid_lat,
            longitude: (float) $row->grid_lng,
            intensity: $intensity,
        );
    }
}
