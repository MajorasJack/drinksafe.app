<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Services;

use Carbon\CarbonImmutable;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\DataTransferObjects\SafetyScoreResult;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * SafetyScoreService
 *
 * Computes venue safety scores based on report history with time-decay weighting.
 * Scores are cached per venue with automatic invalidation on new reports.
 *
 * Algorithm:
 * - Each report contributes a weight based on its age
 * - Weight = 0.5^(days_since_incident / 90) - halves every 90 days
 * - Reports older than 365 days are excluded
 * - Minimum 3 reports required for a valid score
 * - Raw weighted sum is normalised to 0-100 scale
 * - Tiers: Low (0-30), Moderate (31-60), High (61-100)
 */
final class SafetyScoreService
{
    /**
     * Decay factor for time-weighted scoring (0.5 = halves over decay period).
     */
    private const float DECAY_FACTOR = 0.5;

    /**
     * Decay period in days (reports halve in weight every 90 days).
     */
    private const int DECAY_PERIOD_DAYS = 90;

    /**
     * Maximum report age to consider in days.
     */
    private const int MAXIMUM_AGE_DAYS = 365;

    /**
     * Minimum reports required for a valid score.
     */
    private const int MINIMUM_REPORTS_FOR_SCORE = 3;

    /**
     * Cache TTL in seconds (24 hours).
     */
    private const int CACHE_TTL_SECONDS = 86400;

    /**
     * Cache key prefix for venue safety scores.
     */
    private const string CACHE_KEY_PREFIX = 'venue:safety_score:';

    /**
     * Score threshold for low tier (Green).
     */
    private const int TIER_LOW_THRESHOLD = 30;

    /**
     * Score threshold for moderate tier (Amber).
     */
    private const int TIER_MODERATE_THRESHOLD = 60;

    /**
     * Compute safety score for a venue.
     *
     * Returns a SafetyScoreResult DTO with computed score, tier, and metadata.
     * Results are cached for 24 hours with automatic invalidation on new reports.
     *
     * @param  Venue  $venue  The venue to compute score for
     *
     * @throws VenueNotFoundException If venue not found
     */
    public function computeScore(Venue $venue): SafetyScoreResult
    {
        $cacheKey = $this->getCacheKey($venue->uuid);

        $cachedData = Cache::get($cacheKey);

        if ($cachedData !== null) {
            return SafetyScoreResult::fromArray($cachedData);
        }

        $result = $this->calculateScoreForVenue($venue);

        Cache::put($cacheKey, $result->toArray(), self::CACHE_TTL_SECONDS);

        return $result;
    }

    /**
     * Calculate safety score for a venue by UUID.
     *
     * @param  string  $venueUuid  The venue UUID
     *
     * @throws VenueNotFoundException If venue not found
     */
    public function computeScoreByUuid(string $venueUuid): SafetyScoreResult
    {
        $venue = Venue::find($venueUuid);

        if ($venue === null) {
            throw new VenueNotFoundException($venueUuid);
        }

        return $this->computeScore($venue);
    }

    /**
     * Invalidate cached score for a venue.
     *
     * Should be called when a new report is submitted for the venue.
     *
     * @param  string  $venueUuid  The venue UUID
     */
    public function invalidateScore(string $venueUuid): void
    {
        Cache::forget($this->getCacheKey($venueUuid));
    }

    /**
     * Batch compute scores for multiple venues.
     *
     * @param  Collection<int, Venue>  $venues
     * @return Collection<string, SafetyScoreResult>
     */
    public function computeScoresForVenues(Collection $venues): Collection
    {
        return $venues->mapWithKeys(
            fn (Venue $venue): array => [
                $venue->uuid => $this->computeScore($venue),
            ]
        );
    }

    /**
     * Generate cache key for a venue's safety score.
     *
     * @param  string  $venueUuid  The venue UUID
     */
    private function getCacheKey(string $venueUuid): string
    {
        return sprintf('%s%s', self::CACHE_KEY_PREFIX, $venueUuid);
    }

    /**
     * Calculate the raw score for a venue (internal, not cached).
     *
     * @param  Venue  $venue  The venue to calculate score for
     */
    private function calculateScoreForVenue(Venue $venue): SafetyScoreResult
    {
        $cutoffDate = CarbonImmutable::now()->subDays(self::MAXIMUM_AGE_DAYS);

        $reports = Report::where('venue_uuid', $venue->uuid)
            ->where('incident_date', '>=', $cutoffDate)
            ->get();

        $now = CarbonImmutable::now();

        $reportCount30d = $reports->filter(
            fn (Report $report): bool => $report->incident_date->gte($now->subDays(30))
        )->count();

        $reportCount90d = $reports->filter(
            fn (Report $report): bool => $report->incident_date->gte($now->subDays(90))
        )->count();

        if ($reports->count() < self::MINIMUM_REPORTS_FOR_SCORE) {
            return SafetyScoreResult::insufficientData(
                venueUuid: $venue->uuid,
                reportCount30d: $reportCount30d,
                reportCount90d: $reportCount90d,
            );
        }

        $weightedSum = $reports->sum(
            fn (Report $report): float => $this->calculateReportWeight($report)
        );

        $rawScore = $this->normaliseScore($weightedSum);

        return new SafetyScoreResult(
            venueUuid: $venue->uuid,
            score: $rawScore,
            tier: $this->determineTier($rawScore),
            reportCount30d: $reportCount30d,
            reportCount90d: $reportCount90d,
            lastUpdated: $now,
        );
    }

    /**
     * Calculate time-decayed weight for a single report.
     *
     * Uses the formula: weight = 0.5^(days_since_incident / 90)
     * A report from today has weight 1.0, a report from 90 days ago has weight 0.5.
     *
     * @param  Report  $report  The report to calculate weight for
     */
    private function calculateReportWeight(Report $report): float
    {
        $daysSinceIncident = (int) $report->incident_date->diffInDays(CarbonImmutable::now());

        return pow(self::DECAY_FACTOR, $daysSinceIncident / self::DECAY_PERIOD_DAYS);
    }

    /**
     * Normalise weighted sum to 0-100 scale.
     *
     * The weighted sum is multiplied by 10 and capped at 100.
     * This means a weighted sum of 10 or more equals a score of 100.
     *
     * @param  float  $weightedSum  The sum of all report weights
     */
    private function normaliseScore(float $weightedSum): int
    {
        return min(100, (int) round($weightedSum * 10));
    }

    /**
     * Determine safety tier from numeric score.
     *
     * Tiers:
     * - low (Green): 0-30 (few/no recent reports)
     * - moderate (Amber): 31-60 (some recent activity)
     * - high (Red): 61-100 (significant recent reports)
     *
     * @param  int  $score  Score between 0-100
     * @return string One of: 'low', 'moderate', 'high'
     */
    private function determineTier(int $score): string
    {
        return match (true) {
            $score <= self::TIER_LOW_THRESHOLD => 'low',
            $score <= self::TIER_MODERATE_THRESHOLD => 'moderate',
            default => 'high',
        };
    }
}
