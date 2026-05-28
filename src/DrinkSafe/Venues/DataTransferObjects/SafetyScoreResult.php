<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * SafetyScoreResult
 *
 * Data transfer object representing a computed venue safety score.
 * Contains the numeric score, tier classification, and supporting metadata.
 *
 * @property-read string $venueUuid The venue UUID this score belongs to
 * @property-read int $score Numeric score from 0-100 (higher = more concern)
 * @property-read string $tier Safety tier: 'low', 'moderate', 'high', or 'insufficient_data'
 * @property-read int $reportCount30d Number of reports in the last 30 days
 * @property-read int $reportCount90d Number of reports in the last 90 days
 * @property-read CarbonImmutable $lastUpdated Timestamp when score was computed
 */
final readonly class SafetyScoreResult
{
    /**
     * Create a new SafetyScoreResult instance.
     *
     * @param  string  $venueUuid  The venue UUID this score belongs to
     * @param  int  $score  Numeric score from 0-100 (higher = more concern)
     * @param  string  $tier  Safety tier classification
     * @param  int  $reportCount30d  Number of reports in the last 30 days
     * @param  int  $reportCount90d  Number of reports in the last 90 days
     * @param  CarbonImmutable  $lastUpdated  Timestamp when score was computed
     */
    public function __construct(
        public string $venueUuid,
        public int $score,
        public string $tier,
        public int $reportCount30d,
        public int $reportCount90d,
        public CarbonImmutable $lastUpdated,
    ) {}

    /**
     * Create a SafetyScoreResult for insufficient data scenario.
     *
     * @param  string  $venueUuid  The venue UUID
     * @param  int  $reportCount30d  Number of reports in the last 30 days
     * @param  int  $reportCount90d  Number of reports in the last 90 days
     */
    public static function insufficientData(
        string $venueUuid,
        int $reportCount30d = 0,
        int $reportCount90d = 0,
    ): self {
        return new self(
            venueUuid: $venueUuid,
            score: 0,
            tier: 'insufficient_data',
            reportCount30d: $reportCount30d,
            reportCount90d: $reportCount90d,
            lastUpdated: CarbonImmutable::now(),
        );
    }

    /**
     * Convert the DTO to an array suitable for JSON serialisation.
     *
     * @return array{
     *     venue_uuid: string,
     *     score: int,
     *     tier: string,
     *     report_count_30d: int,
     *     report_count_90d: int,
     *     last_updated: string
     * }
     */
    public function toArray(): array
    {
        return [
            'venue_uuid' => $this->venueUuid,
            'score' => $this->score,
            'tier' => $this->tier,
            'report_count_30d' => $this->reportCount30d,
            'report_count_90d' => $this->reportCount90d,
            'last_updated' => $this->lastUpdated->toIso8601String(),
        ];
    }

    /**
     * Create a SafetyScoreResult from a cached array.
     *
     * @param  array{
     *     venue_uuid: string,
     *     score: int,
     *     tier: string,
     *     report_count_30d: int,
     *     report_count_90d: int,
     *     last_updated: string
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            venueUuid: $data['venue_uuid'],
            score: $data['score'],
            tier: $data['tier'],
            reportCount30d: $data['report_count_30d'],
            reportCount90d: $data['report_count_90d'],
            lastUpdated: CarbonImmutable::parse($data['last_updated']),
        );
    }
}
