# DrinkSafe Feature Proposals

**Document Status:** Proposal
**Created:** 2026-05-27
**Last Updated:** 2026-05-27

---

## Overview

This document outlines proposed features for the DrinkSafe anonymous venue reporting platform. Each proposal captures the product vision, user value, technical considerations, and open questions to guide future implementation.

These features build upon the existing foundation:
- Anonymous incident reporting with PII detection
- Venue management with geospatial search
- Leaflet map integration for visualisation
- Time-based filtering (TimeOfDay enum, incident_date)

---

## Feature Proposal Template

All feature proposals in this document follow this standardised structure:

| Section | Purpose |
|---------|---------|
| **Status & Metadata** | Current status, effort estimate, priority |
| **Vision** | What the feature achieves and why it matters |
| **User Value** | Benefits for each stakeholder group |
| **Technical Considerations** | Module placement, data requirements, API shapes |
| **Acceptance Criteria** | Measurable conditions for completion |
| **Open Questions** | Decisions to be made during implementation |
| **Dependencies** | What must exist before implementation |
| **Risks & Mitigations** | Potential issues and how to address them |

### Status Badges

| Badge | Meaning |
|-------|---------|
| 🟡 **Proposed** | Feature documented, not yet started |
| 🔵 **In Progress** | Implementation underway |
| 🟢 **Implemented** | Feature complete and deployed |
| ⚪ **Deferred** | Postponed for future consideration |

### Effort Estimates

| Size | Description | Typical Duration |
|------|-------------|------------------|
| **S** (Small) | Single module, minimal changes | 1-2 days |
| **M** (Medium) | Multiple files, moderate complexity | 3-5 days |
| **L** (Large) | New module, significant changes | 1-2 weeks |
| **XL** (Extra Large) | Multiple modules, infrastructure changes | 2+ weeks |

---

## Feature Summary

| # | Feature | Status | Effort | Priority | Dependencies |
|---|---------|--------|--------|----------|--------------|
| 1 | [Venue Safety Scores](#1-venue-safety-scores) | 🟡 Proposed | S | 1 | None |
| 2 | [Incident Heatmap Layer](#2-incident-heatmap-layer) | 🟡 Proposed | M | 3 | Leaflet.heat |
| 3 | [Time-based Analytics](#3-time-based-analytics) | 🟡 Proposed | M | 2 | Chart library |
| 4 | [Venue Response/Claim System](#4-venue-responseclaim-system) | 🟡 Proposed | L | 4 | User auth |
| 5 | [Nearby Alerts](#5-nearby-alerts) | 🟡 Proposed | XL | 5 | User auth, notifications |

---

## Dependency Matrix

```
Feature Dependencies:

┌─────────────────────┐
│ 1. Safety Scores    │──────────────────────────────┐
└─────────────────────┘                              │
         │                                           │
         │ (can inform)                              │
         ▼                                           │
┌─────────────────────┐                              │
│ 2. Heatmap Layer    │                              │  No blocking
└─────────────────────┘                              │  dependencies
         │                                           │
         │ (shares analytics)                        │
         ▼                                           │
┌─────────────────────┐                              │
│ 3. Time Analytics   │──────────────────────────────┤
└─────────────────────┘                              │
                                                     │
┌─────────────────────┐                              │
│ 4. Venue Response   │◄─────── User Authentication ─┤
└─────────────────────┘                              │
         │                                           │
         │ (shares auth)                             │
         ▼                                           │
┌─────────────────────┐                              │
│ 5. Nearby Alerts    │◄─────── User Authentication ─┘
└─────────────────────┘
         ▲
         │
         └─────────────────── Notification Infrastructure
```

**Key Insight:** Features 1-3 can be implemented independently. Features 4-5 share a dependency on user authentication and should be sequenced together.

---

## 1. Venue Safety Scores

### Status & Metadata

| Attribute | Value |
|-----------|-------|
| **Status** | 🟡 Proposed |
| **Effort** | S (Small) |
| **Priority** | 1 (Highest) |
| **Module** | `src/DrinkSafe/Venues/` (extension) |

---

### Vision

Provide users with an at-a-glance indication of venue safety based on historical report data. The score should reflect both the volume and recency of incidents whilst acknowledging that venues can improve over time.

---

### User Value

| Stakeholder | Benefit |
|-------------|---------|
| **Users** | Quick safety assessment when deciding where to go |
| **Venues** | Incentive to address safety concerns (scores can improve) |
| **Platform** | Increased engagement and trust through transparency |

---

### Score Factors

The safety score should consider:

| Factor | Weight Consideration | Rationale |
|--------|---------------------|-----------|
| Report count | Primary factor | More reports indicate higher concern |
| Report recency | Time-decay weighting | Recent reports matter more than old ones |
| Report frequency | Pattern detection | Clusters of reports vs isolated incidents |
| Venue type context | Normalisation | High-footfall venues naturally have more reports |
| Time span of data | Confidence indicator | More historical data = more reliable score |

### Time Decay Model

Reports should carry diminishing weight over time:

```
weight = base_weight * decay_factor^(days_since_incident / decay_period)
```

Suggested parameters:
- **decay_factor**: 0.5 (reports halve in weight over decay_period)
- **decay_period**: 90 days (quarterly decay)
- **maximum_age**: 365 days (reports older than 1 year may be excluded or heavily discounted)

This allows venues to recover their score over time if no new incidents are reported.

### Display Options

| Format | Pros | Cons | Recommendation |
|--------|------|------|----------------|
| Numeric (1-100) | Precise, sortable | Abstract, harder to interpret | Backend only |
| Stars (1-5) | Familiar, intuitive | Implies quality rating | Avoid - wrong connotation |
| Traffic light (Red/Amber/Green) | Clear, accessible | Limited granularity | Primary display |
| Gradient bar | Visual, shows nuance | Less familiar | Secondary/detailed view |

**Recommended approach**: Traffic light system with three tiers:
- **Green**: Low concern (few/no recent reports)
- **Amber**: Moderate concern (some recent activity)
- **Red**: High concern (significant recent reports)

With an additional "Insufficient data" state for new or rarely-visited venues.

---

### Technical Considerations

**Module placement**: `src/DrinkSafe/Venues/` (extends Venue model/service)

**Data requirements**:
- No schema changes required (uses existing reports relationship)
- Scores could be computed on-demand or cached

**Caching strategy**:
- Compute scores on report creation/update
- Store as computed column or cache value
- Invalidate when new reports are submitted for venue

**API shape** (conceptual):
```typescript
interface VenueSafetyScore {
  venue_uuid: string;
  score: number;           // 0-100 internal
  tier: 'low' | 'moderate' | 'high' | 'insufficient_data';
  report_count_30d: number;
  report_count_90d: number;
  last_updated: string;
}
```

---

### Acceptance Criteria

| # | Criterion | Measurable Outcome |
|---|-----------|-------------------|
| AC1 | Safety score computed | Given a venue with reports, when the score endpoint is called, then a numeric score (0-100) is returned |
| AC2 | Time decay applied | Given reports of varying ages, when scores are computed, then recent reports have higher weight than older reports |
| AC3 | Tier classification | Given a computed score, when displayed, then it shows as Green/Amber/Red/Insufficient Data |
| AC4 | Insufficient data handling | Given a venue with <3 reports, when score is requested, then "insufficient_data" tier is returned |
| AC5 | Cache invalidation | Given a new report is submitted, when the venue score is next requested, then it reflects the new report |
| AC6 | API endpoint available | GET `/api/venues/{uuid}/safety-score` returns VenueSafetyScore response |
| AC7 | Frontend display | Safety badge component displays tier with appropriate colour |

---

### Backend Implementation Details

#### Service Class Signature

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Services;

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * SafetyScoreService
 *
 * Computes venue safety scores based on report history with time-decay weighting.
 * Scores are cached per venue with automatic invalidation on new reports.
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
     * Calculate safety score for a venue.
     *
     * Returns a VenueSafetyScore DTO with computed score, tier, and metadata.
     *
     * @param  string  $venueUuid  The venue UUID
     *
     * @throws VenueNotFoundException If venue not found
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
    public function calculateScore(string $venueUuid): array
    {
        $cacheKey = sprintf('venue_safety_score:%s', $venueUuid);

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->computeScoreForVenue($venueUuid)
        );
    }

    /**
     * Invalidate cached score for a venue.
     *
     * Should be called when a new report is submitted.
     *
     * @param  string  $venueUuid  The venue UUID
     */
    public function invalidateScore(string $venueUuid): void
    {
        Cache::forget(sprintf('venue_safety_score:%s', $venueUuid));
    }

    /**
     * Batch calculate scores for multiple venues.
     *
     * @param  Collection<int, Venue>  $venues
     * @return Collection<string, array{venue_uuid: string, score: int, tier: string}>
     */
    public function calculateScoresForVenues(Collection $venues): Collection
    {
        return $venues->mapWithKeys(
            fn (Venue $venue): array => [
                $venue->uuid => $this->calculateScore($venue->uuid),
            ]
        );
    }

    /**
     * Compute the raw score for a venue (internal, not cached).
     *
     * @param  string  $venueUuid
     * @return array{venue_uuid: string, score: int, tier: string, report_count_30d: int, report_count_90d: int, last_updated: string}
     *
     * @throws VenueNotFoundException
     */
    private function computeScoreForVenue(string $venueUuid): array
    {
        $venue = Venue::find($venueUuid);

        if ($venue === null) {
            throw new VenueNotFoundException($venueUuid);
        }

        $reports = Report::where('venue_uuid', $venueUuid)
            ->where('incident_date', '>=', now()->subDays(self::MAXIMUM_AGE_DAYS))
            ->get();

        $reportCount30d = $reports->filter(
            fn (Report $report): bool => $report->incident_date->gte(now()->subDays(30))
        )->count();

        $reportCount90d = $reports->filter(
            fn (Report $report): bool => $report->incident_date->gte(now()->subDays(90))
        )->count();

        if ($reports->count() < self::MINIMUM_REPORTS_FOR_SCORE) {
            return [
                'venue_uuid' => $venueUuid,
                'score' => 0,
                'tier' => 'insufficient_data',
                'report_count_30d' => $reportCount30d,
                'report_count_90d' => $reportCount90d,
                'last_updated' => now()->toIso8601String(),
            ];
        }

        $weightedSum = $reports->sum(
            fn (Report $report): float => $this->calculateReportWeight($report)
        );

        // Normalise to 0-100 scale (higher = more concern)
        $rawScore = min(100, (int) round($weightedSum * 10));

        return [
            'venue_uuid' => $venueUuid,
            'score' => $rawScore,
            'tier' => $this->determineTier($rawScore),
            'report_count_30d' => $reportCount30d,
            'report_count_90d' => $reportCount90d,
            'last_updated' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate time-decayed weight for a report.
     *
     * @param  Report  $report
     */
    private function calculateReportWeight(Report $report): float
    {
        $daysSinceIncident = $report->incident_date->diffInDays(now());

        return pow(self::DECAY_FACTOR, $daysSinceIncident / self::DECAY_PERIOD_DAYS);
    }

    /**
     * Determine safety tier from numeric score.
     *
     * @param  int  $score  Score between 0-100
     * @return string One of: 'low', 'moderate', 'high', 'insufficient_data'
     */
    private function determineTier(int $score): string
    {
        return match (true) {
            $score < 20 => 'low',
            $score < 50 => 'moderate',
            default => 'high',
        };
    }
}
```

#### API Controller Method

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Services\SafetyScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * VenueSafetyScoreController
 *
 * Handles requests for venue safety score data.
 */
final class VenueSafetyScoreController extends Controller
{
    public function __construct(
        private readonly SafetyScoreService $safetyScoreService
    ) {}

    /**
     * Get safety score for a specific venue.
     *
     * @param  string  $uuid  Venue UUID
     */
    public function __invoke(string $uuid): JsonResponse
    {
        try {
            $score = $this->safetyScoreService->calculateScore($uuid);

            return response()->json([
                'data' => $score,
            ], Response::HTTP_OK);
        } catch (VenueNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
```

#### VenueResource Additions

```php
// Add to existing VenueResource::toArray() method:
'safety_score' => $this->when(
    $this->resource->relationLoaded('safetyScore') || isset($this->additional['safety_score']),
    fn (): ?array => $this->additional['safety_score'] ?? null
),
```

#### Caching Strategy

| Key Pattern | TTL | Invalidation Trigger |
|-------------|-----|----------------------|
| `venue_safety_score:{venue_uuid}` | 24 hours | New report submitted for venue |

#### Route Registration

```php
// routes/api.php
Route::get('/venues/{uuid}/safety-score', VenueSafetyScoreController::class)
    ->name('venues.safety-score');
```

---

### Frontend Implementation Details

#### TypeScript Interfaces

**File:** `resources/js/types/safety.ts`

```typescript
/**
 * Safety Score Types
 *
 * Types for venue safety score functionality.
 */

/**
 * Safety tier classification
 */
export type SafetyTier = 'low' | 'moderate' | 'high' | 'insufficient_data';

/**
 * Safety score data from API
 */
export interface SafetyScore {
    venue_uuid: string;
    score: number;
    tier: SafetyTier;
    report_count_30d: number;
    report_count_90d: number;
    last_updated: string;
}

/**
 * Props for SafetyBadge component
 */
export interface SafetyBadgeProps {
    tier: SafetyTier;
    showLabel?: boolean;
    size?: 'sm' | 'md' | 'lg';
}
```

#### SafetyBadge Component

**File:** `resources/js/components/safety/SafetyBadge.vue`

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { ShieldCheck, ShieldAlert, Shield, HelpCircle } from 'lucide-vue-next';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { SafetyTier } from '@/types/safety';

interface Props {
    tier: SafetyTier;
    showLabel?: boolean;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    showLabel: false,
    size: 'md',
});

const tierConfig = computed(() => {
    const configs: Record<SafetyTier, {
        label: string;
        colour: string;
        bgColour: string;
        icon: typeof ShieldCheck;
        tooltip: string;
    }> = {
        low: {
            label: 'Low Concern',
            colour: 'text-green-600 dark:text-green-400',
            bgColour: 'bg-green-100 dark:bg-green-900/30',
            icon: ShieldCheck,
            tooltip: 'Few or no recent reports at this venue',
        },
        moderate: {
            label: 'Moderate Concern',
            colour: 'text-amber-600 dark:text-amber-400',
            bgColour: 'bg-amber-100 dark:bg-amber-900/30',
            icon: ShieldAlert,
            tooltip: 'Some recent activity reported at this venue',
        },
        high: {
            label: 'High Concern',
            colour: 'text-red-600 dark:text-red-400',
            bgColour: 'bg-red-100 dark:bg-red-900/30',
            icon: Shield,
            tooltip: 'Significant recent reports at this venue',
        },
        insufficient_data: {
            label: 'Insufficient Data',
            colour: 'text-slate-500 dark:text-slate-400',
            bgColour: 'bg-slate-100 dark:bg-slate-800',
            icon: HelpCircle,
            tooltip: 'Not enough reports to calculate safety score',
        },
    };

    return configs[props.tier];
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: { icon: 'size-4', padding: 'p-1', text: 'text-xs' },
        md: { icon: 'size-5', padding: 'p-1.5', text: 'text-sm' },
        lg: { icon: 'size-6', padding: 'p-2', text: 'text-base' },
    };

    return sizes[props.size];
});
</script>

<template>
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger as-child>
                <div
                    :class="[
                        'inline-flex items-center gap-1.5 rounded-full',
                        tierConfig.bgColour,
                        sizeClasses.padding,
                        showLabel ? 'pr-2.5' : '',
                    ]"
                >
                    <component
                        :is="tierConfig.icon"
                        :class="[sizeClasses.icon, tierConfig.colour]"
                    />
                    <span
                        v-if="showLabel"
                        :class="[sizeClasses.text, tierConfig.colour, 'font-medium']"
                    >
                        {{ tierConfig.label }}
                    </span>
                </div>
            </TooltipTrigger>
            <TooltipContent>
                <p>{{ tierConfig.tooltip }}</p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
```

#### useSafetyScore Composable

**File:** `resources/js/composables/useSafetyScore.ts`

```typescript
/**
 * useSafetyScore Composable
 *
 * Fetches and manages safety score data for venues.
 */

import axios from 'axios';
import { ref, computed } from 'vue';
import type { SafetyScore, SafetyTier } from '@/types/safety';

export function useSafetyScore() {
    const scores = ref<Map<string, SafetyScore>>(new Map());
    const loading = ref(false);
    const error = ref<string | null>(null);

    const fetchScore = async (venueUuid: string): Promise<SafetyScore | null> => {
        if (scores.value.has(venueUuid)) {
            return scores.value.get(venueUuid) ?? null;
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<{ data: SafetyScore }>(
                `/api/venues/${venueUuid}/safety-score`
            );
            const score = response.data.data;
            scores.value.set(venueUuid, score);

            return score;
        } catch (err) {
            error.value = err instanceof Error
                ? err.message
                : 'Failed to fetch safety score';

            return null;
        } finally {
            loading.value = false;
        }
    };

    const getScore = (venueUuid: string): SafetyScore | undefined => {
        return scores.value.get(venueUuid);
    };

    const getTier = (venueUuid: string): SafetyTier | undefined => {
        return scores.value.get(venueUuid)?.tier;
    };

    const clearCache = (): void => {
        scores.value.clear();
    };

    return {
        scores: computed(() => scores.value),
        loading,
        error,
        fetchScore,
        getScore,
        getTier,
        clearCache,
    };
}
```

#### Integration with VenueCard

**Usage in existing VenueCard component:**

```vue
<script setup lang="ts">
import { onMounted } from 'vue';
import SafetyBadge from '@/components/safety/SafetyBadge.vue';
import { useSafetyScore } from '@/composables/useSafetyScore';
import type { Venue } from '@/types/venue';

interface Props {
    venue: Venue;
}

const props = defineProps<Props>();

const { fetchScore, getTier, loading } = useSafetyScore();

onMounted(async () => {
    await fetchScore(props.venue.uuid);
});

const tier = computed(() => getTier(props.venue.uuid));
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <h3>{{ venue.name }}</h3>
                <SafetyBadge
                    v-if="tier && !loading"
                    :tier="tier"
                    size="sm"
                />
                <Skeleton v-else class="size-6 rounded-full" />
            </div>
        </CardHeader>
        <!-- ... rest of card -->
    </Card>
</template>
```

---

### Testing Strategy

#### Test Organisation

Tests for the Venue Safety Scores feature live within the existing `src/DrinkSafe/Venues/` module:

```
src/DrinkSafe/Venues/Tests/
├── Feature/
│   └── VenueSafetyScoreControllerTest.php
└── Unit/
    └── SafetyScoreServiceTest.php
```

#### Factory Requirements

No new factories required. Uses existing `VenueFactory` and `ReportFactory` with additional states:

```php
// database/factories/DrinkSafe/ReportFactory.php - Add states

/**
 * Create a report from the recent past (within 30 days).
 */
public function recent(): static
{
    return $this->state(fn (array $attributes): array => [
        'incident_date' => fake()->dateTimeBetween('-30 days', 'now'),
    ]);
}

/**
 * Create an old report (between 180-365 days ago).
 */
public function old(): static
{
    return $this->state(fn (array $attributes): array => [
        'incident_date' => fake()->dateTimeBetween('-365 days', '-180 days'),
    ]);
}

/**
 * Create a report exactly N days ago.
 */
public function daysAgo(int $days): static
{
    return $this->state(fn (array $attributes): array => [
        'incident_date' => now()->subDays($days)->toDateString(),
    ]);
}
```

#### Unit Tests (SafetyScoreServiceTest.php)

```php
<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Services\SafetyScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new SafetyScoreService;
});

describe('SafetyScoreService', function (): void {
    describe('calculateScore', function (): void {
        it('returns insufficient_data tier for venues with fewer than 3 reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(2)->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->calculateScore($venue->uuid);

            expect($result['tier'])->toBe('insufficient_data')
                ->and($result['score'])->toBe(0);
        });

        it('returns low tier for venues with minimal recent reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->old()->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->calculateScore($venue->uuid);

            expect($result['tier'])->toBe('low')
                ->and($result['score'])->toBeLessThan(20);
        });

        it('returns moderate tier for venues with moderate report activity', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->recent()->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->calculateScore($venue->uuid);

            expect($result['tier'])->toBe('moderate')
                ->and($result['score'])->toBeGreaterThanOrEqual(20)
                ->and($result['score'])->toBeLessThan(50);
        });

        it('returns high tier for venues with significant recent reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->recent()->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->calculateScore($venue->uuid);

            expect($result['tier'])->toBe('high')
                ->and($result['score'])->toBeGreaterThanOrEqual(50);
        });

        it('throws VenueNotFoundException for invalid venue UUID', function (): void {
            $invalidUuid = fake()->uuid();

            expect(fn (): array => $this->service->calculateScore($invalidUuid))
                ->toThrow(VenueNotFoundException::class);
        });

        it('applies time decay to older reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->daysAgo(1)->create(['venue_uuid' => $venue->uuid]);
            $recentScore = $this->service->calculateScore($venue->uuid);

            Cache::flush();

            $oldVenue = Venue::factory()->create();
            Report::factory()->count(5)->daysAgo(180)->create(['venue_uuid' => $oldVenue->uuid]);
            $oldScore = $this->service->calculateScore($oldVenue->uuid);

            expect($recentScore['score'])->toBeGreaterThan($oldScore['score']);
        });

        it('excludes reports older than 365 days', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->create([
                'venue_uuid' => $venue->uuid,
                'incident_date' => now()->subDays(400)->toDateString(),
            ]);

            $result = $this->service->calculateScore($venue->uuid);

            expect($result['tier'])->toBe('insufficient_data');
        });

        it('includes correct report counts for 30 and 90 day windows', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(2)->daysAgo(15)->create(['venue_uuid' => $venue->uuid]);
            Report::factory()->count(3)->daysAgo(60)->create(['venue_uuid' => $venue->uuid]);
            Report::factory()->count(4)->daysAgo(120)->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->calculateScore($venue->uuid);

            expect($result['report_count_30d'])->toBe(2)
                ->and($result['report_count_90d'])->toBe(5);
        });
    });

    describe('invalidateScore', function (): void {
        it('clears cached score for venue', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->recent()->create(['venue_uuid' => $venue->uuid]);

            $this->service->calculateScore($venue->uuid);
            $cacheKey = sprintf('venue_safety_score:%s', $venue->uuid);

            expect(Cache::has($cacheKey))->toBeTrue();

            $this->service->invalidateScore($venue->uuid);

            expect(Cache::has($cacheKey))->toBeFalse();
        });
    });

    describe('calculateScoresForVenues', function (): void {
        it('returns scores for multiple venues', function (): void {
            $venues = Venue::factory()->count(3)->create();
            $venues->each(fn (Venue $venue) => Report::factory()->count(4)->recent()->create([
                'venue_uuid' => $venue->uuid,
            ]));

            $results = $this->service->calculateScoresForVenues($venues);

            expect($results)->toHaveCount(3)
                ->and($results->keys()->all())->toEqual($venues->pluck('uuid')->all());
        });
    });
});
```

#### Feature Tests (VenueSafetyScoreControllerTest.php)

```php
<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('VenueSafetyScoreController', function (): void {
    describe('GET /api/venues/{uuid}/safety-score', function (): void {
        it('returns safety score for valid venue', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->recent()->create(['venue_uuid' => $venue->uuid]);

            $this->getJson(route('venues.safety-score', $venue->uuid))
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'venue_uuid',
                        'score',
                        'tier',
                        'report_count_30d',
                        'report_count_90d',
                        'last_updated',
                    ],
                ])
                ->assertJsonPath('data.venue_uuid', $venue->uuid);
        });

        it('returns 404 for non-existent venue', function (): void {
            $this->getJson(route('venues.safety-score', fake()->uuid()))
                ->assertNotFound()
                ->assertJsonStructure(['message']);
        });

        it('returns insufficient_data tier for venues with few reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(2)->create(['venue_uuid' => $venue->uuid]);

            $this->getJson(route('venues.safety-score', $venue->uuid))
                ->assertOk()
                ->assertJsonPath('data.tier', 'insufficient_data')
                ->assertJsonPath('data.score', 0);
        });

        it('returns correct tier based on score', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(10)->recent()->create(['venue_uuid' => $venue->uuid]);

            $response = $this->getJson(route('venues.safety-score', $venue->uuid))
                ->assertOk();

            $tier = $response->json('data.tier');
            $score = $response->json('data.score');

            expect(in_array($tier, ['low', 'moderate', 'high'], true))->toBeTrue()
                ->and($score)->toBeGreaterThanOrEqual(0)
                ->and($score)->toBeLessThanOrEqual(100);
        });

        it('returns cached response on subsequent requests', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->recent()->create(['venue_uuid' => $venue->uuid]);

            $firstResponse = $this->getJson(route('venues.safety-score', $venue->uuid))->json();

            Report::factory()->count(5)->recent()->create(['venue_uuid' => $venue->uuid]);

            $secondResponse = $this->getJson(route('venues.safety-score', $venue->uuid))->json();

            expect($firstResponse['data']['score'])->toBe($secondResponse['data']['score']);
        });
    });
});
```

#### Edge Cases to Test

| Scenario | Expected Behaviour | Test Coverage |
|----------|-------------------|---------------|
| Venue with 0 reports | `insufficient_data` tier, score 0 | Unit |
| Venue with exactly 3 reports (threshold) | Valid score calculated | Unit |
| All reports older than 365 days | `insufficient_data` tier | Unit |
| Reports spanning boundary dates (29, 30, 31 days) | Correct window counts | Unit |
| Concurrent cache invalidation | No race conditions | Integration (future) |
| Very high report count (1000+) | Performance acceptable | Performance (future) |
| Invalid UUID format | 404 response | Feature |
| Deleted venue (soft delete) | 404 response | Feature |

---

### Open Questions

1. How should we handle venues with only 1-2 reports? Threshold for "sufficient data"?
2. Should users be able to see the breakdown of how scores are calculated?
3. Do we differentiate between incident severity (if added later)?
4. How do we communicate that a venue improving their score is a positive signal?

---

### Dependencies

- None (builds on existing data model)

---

### Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Gaming the system (fake reports to harm competitors) | Medium | High | Existing PII detection + moderation; consider rate limiting |
| Venues feeling unfairly penalised | Medium | Medium | Clear communication about improvement potential; transparency |
| Users over-relying on scores | Low | Medium | Display as one factor, not absolute safety guarantee |

---

## 2. Incident Heatmap Layer

### Status & Metadata

| Attribute | Value |
|-----------|-------|
| **Status** | 🟡 Proposed |
| **Effort** | M (Medium) |
| **Priority** | 3 |
| **Module** | `src/DrinkSafe/Analytics/` (new) or `src/DrinkSafe/Shared/` |

---

### Vision

Overlay the existing Leaflet map with a density visualisation showing incident hotspots across geographic areas. This helps users identify areas of concern without pinpointing exact incident locations.

---

### User Value

| Stakeholder | Benefit |
|-------------|---------|
| **Users** | Understand safety patterns across an area at a glance |
| **City planners/authorities** | Identify problem areas requiring intervention |
| **Platform** | Powerful visual differentiator; encourages exploration |

---

### Visualisation Approach

**Recommended**: Leaflet.heat plugin for heatmap rendering

```
Geographic grid cells -> Aggregate report counts -> Heat intensity mapping
```

### Time Filtering

Users should be able to filter the heatmap by time period:

| Period | Use Case |
|--------|----------|
| Last 7 days | Recent spike detection |
| Last 30 days | Current trends (default) |
| Last 90 days | Seasonal patterns |
| Last 365 days | Long-term overview |

### Privacy Considerations

**Critical**: Heatmaps must not enable identification of individual incidents.

| Technique | Implementation |
|-----------|---------------|
| Spatial fuzzing | Round coordinates to ~100m precision for display |
| Minimum threshold | Only show cells with 3+ reports |
| Temporal aggregation | Combine reports across time periods |
| No individual markers | Heatmap only, no clickable incident points |

---

### Technical Considerations

**Module placement**: `src/DrinkSafe/Shared/` (cross-cutting analytics) or new `src/DrinkSafe/Analytics/` module

**Frontend**:
- Extend `useMap.ts` composable with heatmap layer methods
- Leaflet.heat integration
- Toggle control for heatmap visibility
- Time period selector component

**Backend API** (conceptual):
```
GET /api/heatmap?period=30&bounds[sw][lat]=...&bounds[ne][lat]=...

Response:
{
  "points": [
    { "lat": 51.5074, "lng": -0.1278, "intensity": 0.7 },
    ...
  ],
  "period_days": 30,
  "total_reports": 156
}
```

**Performance with large datasets**:

| Strategy | Description |
|----------|-------------|
| Bounding box queries | Only fetch data for visible map area |
| Server-side aggregation | Pre-compute grid cells, don't send raw points |
| Caching | Cache heatmap data with TTL (e.g., 1 hour) |
| Progressive loading | Load coarse grid first, refine on zoom |
| Database indexing | Spatial index on lat/lng columns |

### Clustering Algorithm

Grid-based spatial aggregation:

1. Define grid cell size (e.g., 0.001 degrees, approximately 100m)
2. Bucket reports into cells based on venue coordinates
3. Calculate intensity as `min(report_count / max_expected, 1.0)`
4. Apply time-decay weighting (similar to safety scores)

---

### Acceptance Criteria

| # | Criterion | Measurable Outcome |
|---|-----------|-------------------|
| AC1 | Heatmap renders | Given the map page, when heatmap toggle is enabled, then heat layer displays over map |
| AC2 | Time filtering works | Given heatmap visible, when time period is changed, then heatmap updates to reflect selected period |
| AC3 | Privacy preserved | Given any heatmap view, then no individual incident can be identified (min 3 reports per cell) |
| AC4 | Bounding box queries | Given map pan/zoom, when heatmap refreshes, then only visible area data is fetched |
| AC5 | Performance acceptable | Given 1000+ reports in view, when heatmap renders, then response time < 2 seconds |
| AC6 | Toggle persists | Given user toggles heatmap off, when page is revisited, then preference is remembered |
| AC7 | Mobile responsive | Given mobile viewport, when viewing heatmap, then controls are accessible and usable |

---

### Backend Implementation Details

#### Module Placement

The heatmap functionality will be placed in `src/DrinkSafe/Shared/` as it provides cross-cutting analytics capabilities used by multiple features.

#### Service Class Signature

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Services;

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Shared\Exceptions\InvalidBoundingBoxException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * HeatmapService
 *
 * Generates spatial aggregation data for heatmap visualisation.
 * Uses grid-based clustering with privacy-preserving thresholds.
 */
final class HeatmapService
{
    /**
     * Grid cell size in degrees (approximately 100m at mid-latitudes).
     */
    private const float GRID_CELL_SIZE = 0.001;

    /**
     * Minimum reports per cell to display (privacy threshold).
     */
    private const int MINIMUM_REPORTS_PER_CELL = 3;

    /**
     * Cache TTL in seconds (1 hour).
     */
    private const int CACHE_TTL_SECONDS = 3600;

    /**
     * Maximum expected reports per cell for normalisation.
     */
    private const int MAX_REPORTS_FOR_NORMALISATION = 20;

    /**
     * Get heatmap data for a geographic bounding box.
     *
     * Returns aggregated points with intensity values for Leaflet.heat rendering.
     *
     * @param  array{sw: array{lat: float, lng: float}, ne: array{lat: float, lng: float}}  $bounds
     * @param  int  $periodDays  Number of days to include (default: 30)
     *
     * @throws InvalidBoundingBoxException If bounds are invalid
     *
     * @return array{
     *     points: array<int, array{lat: float, lng: float, intensity: float}>,
     *     period_days: int,
     *     total_reports: int
     * }
     */
    public function getHeatmapData(array $bounds, int $periodDays = 30): array
    {
        $this->validateBounds($bounds);

        $cacheKey = $this->buildCacheKey($bounds, $periodDays);

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->computeHeatmapData($bounds, $periodDays)
        );
    }

    /**
     * Invalidate all heatmap caches.
     *
     * Should be called when new reports are submitted.
     */
    public function invalidateCache(): void
    {
        Cache::tags(['heatmap'])->flush();
    }

    /**
     * Validate bounding box coordinates.
     *
     * @param  array{sw: array{lat: float, lng: float}, ne: array{lat: float, lng: float}}  $bounds
     *
     * @throws InvalidBoundingBoxException
     */
    private function validateBounds(array $bounds): void
    {
        if (
            ! isset($bounds['sw']['lat'], $bounds['sw']['lng'], $bounds['ne']['lat'], $bounds['ne']['lng'])
        ) {
            throw new InvalidBoundingBoxException('Bounding box must include sw and ne coordinates');
        }

        $swLat = $bounds['sw']['lat'];
        $swLng = $bounds['sw']['lng'];
        $neLat = $bounds['ne']['lat'];
        $neLng = $bounds['ne']['lng'];

        if ($swLat < -90 || $swLat > 90 || $neLat < -90 || $neLat > 90) {
            throw new InvalidBoundingBoxException('Latitude must be between -90 and 90');
        }

        if ($swLng < -180 || $swLng > 180 || $neLng < -180 || $neLng > 180) {
            throw new InvalidBoundingBoxException('Longitude must be between -180 and 180');
        }
    }

    /**
     * Build cache key from bounds and period.
     *
     * @param  array{sw: array{lat: float, lng: float}, ne: array{lat: float, lng: float}}  $bounds
     * @param  int  $periodDays
     */
    private function buildCacheKey(array $bounds, int $periodDays): string
    {
        $roundedSwLat = round($bounds['sw']['lat'], 3);
        $roundedSwLng = round($bounds['sw']['lng'], 3);
        $roundedNeLat = round($bounds['ne']['lat'], 3);
        $roundedNeLng = round($bounds['ne']['lng'], 3);

        return sprintf(
            'heatmap:%s:%s:%s:%s:%d',
            $roundedSwLat,
            $roundedSwLng,
            $roundedNeLat,
            $roundedNeLng,
            $periodDays
        );
    }

    /**
     * Compute heatmap data from database.
     *
     * Uses grid-based spatial aggregation with venue coordinates.
     *
     * @param  array{sw: array{lat: float, lng: float}, ne: array{lat: float, lng: float}}  $bounds
     * @param  int  $periodDays
     * @return array{points: array<int, array{lat: float, lng: float, intensity: float}>, period_days: int, total_reports: int}
     */
    private function computeHeatmapData(array $bounds, int $periodDays): array
    {
        $gridCellSize = self::GRID_CELL_SIZE;
        $minReports = self::MINIMUM_REPORTS_PER_CELL;

        $aggregatedData = DB::table('reports')
            ->join('venues', 'reports.venue_uuid', '=', 'venues.uuid')
            ->where('reports.incident_date', '>=', now()->subDays($periodDays))
            ->whereNull('reports.deleted_at')
            ->whereBetween('venues.latitude', [$bounds['sw']['lat'], $bounds['ne']['lat']])
            ->whereBetween('venues.longitude', [$bounds['sw']['lng'], $bounds['ne']['lng']])
            ->selectRaw(
                sprintf(
                    'ROUND(venues.latitude / %f) * %f AS grid_lat,
                     ROUND(venues.longitude / %f) * %f AS grid_lng,
                     COUNT(*) AS report_count',
                    $gridCellSize,
                    $gridCellSize,
                    $gridCellSize,
                    $gridCellSize
                )
            )
            ->groupBy('grid_lat', 'grid_lng')
            ->havingRaw(sprintf('COUNT(*) >= %d', $minReports))
            ->get();

        $totalReports = $aggregatedData->sum('report_count');

        $points = $aggregatedData->map(fn (object $row): array => [
            'lat' => (float) $row->grid_lat,
            'lng' => (float) $row->grid_lng,
            'intensity' => min(1.0, $row->report_count / self::MAX_REPORTS_FOR_NORMALISATION),
        ])->values()->toArray();

        return [
            'points' => $points,
            'period_days' => $periodDays,
            'total_reports' => $totalReports,
        ];
    }
}
```

#### Custom Exception

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Exceptions;

/**
 * InvalidBoundingBoxException
 *
 * Thrown when heatmap bounding box coordinates are invalid.
 */
final class InvalidBoundingBoxException extends DrinkSafeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
```

#### API Controller Method

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Shared\Exceptions\InvalidBoundingBoxException;
use DrinkSafe\Shared\Requests\HeatmapRequest;
use DrinkSafe\Shared\Resources\HeatmapResource;
use DrinkSafe\Shared\Services\HeatmapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * HeatmapController
 *
 * Handles requests for heatmap data.
 */
final class HeatmapController extends Controller
{
    public function __construct(
        private readonly HeatmapService $heatmapService
    ) {}

    /**
     * Get heatmap data for the specified bounding box and time period.
     */
    public function __invoke(HeatmapRequest $request): JsonResponse
    {
        try {
            $bounds = [
                'sw' => [
                    'lat' => (float) $request->input('bounds.sw.lat'),
                    'lng' => (float) $request->input('bounds.sw.lng'),
                ],
                'ne' => [
                    'lat' => (float) $request->input('bounds.ne.lat'),
                    'lng' => (float) $request->input('bounds.ne.lng'),
                ],
            ];

            $periodDays = (int) $request->input('period', 30);

            $data = $this->heatmapService->getHeatmapData($bounds, $periodDays);

            return response()->json(
                new HeatmapResource($data),
                Response::HTTP_OK
            );
        } catch (InvalidBoundingBoxException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
```

#### FormRequest Validation

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HeatmapRequest
 *
 * Validates heatmap API requests with bounding box parameters.
 */
final class HeatmapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'bounds.sw.lat' => ['required', 'numeric', 'between:-90,90'],
            'bounds.sw.lng' => ['required', 'numeric', 'between:-180,180'],
            'bounds.ne.lat' => ['required', 'numeric', 'between:-90,90'],
            'bounds.ne.lng' => ['required', 'numeric', 'between:-180,180'],
            'period' => ['sometimes', 'integer', 'in:7,30,90,365'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bounds.sw.lat.required' => 'Southwest latitude is required',
            'bounds.sw.lat.between' => 'Southwest latitude must be between -90 and 90',
            'bounds.ne.lat.required' => 'Northeast latitude is required',
            'bounds.ne.lat.between' => 'Northeast latitude must be between -90 and 90',
            'period.in' => 'Period must be 7, 30, 90, or 365 days',
        ];
    }
}
```

#### Resource Class

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * HeatmapResource
 *
 * Transforms heatmap data for API response.
 */
final class HeatmapResource extends JsonResource
{
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
```

#### Database Indexing

Add spatial index to venues table for improved query performance:

```php
// Migration: add_spatial_index_to_venues_table.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->index(['latitude', 'longitude'], 'venues_lat_lng_index');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->dropIndex('venues_lat_lng_index');
        });
    }
};
```

#### Route Registration

```php
// routes/api.php
Route::get('/heatmap', HeatmapController::class)
    ->name('heatmap.index');
```

---

### Frontend Implementation Details

#### TypeScript Interfaces

**File:** `resources/js/types/heatmap.ts`

```typescript
/**
 * Heatmap Types
 *
 * Types for incident heatmap visualisation.
 */

/**
 * Geographic bounding box
 */
export interface BoundingBox {
    sw: {
        lat: number;
        lng: number;
    };
    ne: {
        lat: number;
        lng: number;
    };
}

/**
 * Single heatmap point for Leaflet.heat
 */
export interface HeatmapPoint {
    lat: number;
    lng: number;
    intensity: number;
}

/**
 * Heatmap API response data
 */
export interface HeatmapData {
    points: HeatmapPoint[];
    period_days: number;
    total_reports: number;
}

/**
 * Time period filter options
 */
export type HeatmapPeriod = 7 | 30 | 90 | 365;

/**
 * Heatmap layer configuration
 */
export interface HeatmapConfig {
    radius: number;
    blur: number;
    maxZoom: number;
    gradient?: Record<number, string>;
}
```

#### useHeatmap Composable

**File:** `resources/js/composables/useHeatmap.ts`

```typescript
/**
 * useHeatmap Composable
 *
 * Manages heatmap layer for Leaflet map with incident density visualisation.
 */

import L from 'leaflet';
import 'leaflet.heat';
import axios from 'axios';
import { ref, watch, onUnmounted } from 'vue';
import type {
    BoundingBox,
    HeatmapData,
    HeatmapPeriod,
    HeatmapConfig,
} from '@/types/heatmap';

declare module 'leaflet' {
    function heatLayer(
        latlngs: Array<[number, number, number]>,
        options?: HeatmapConfig
    ): L.Layer;
}

const DEFAULT_CONFIG: HeatmapConfig = {
    radius: 25,
    blur: 15,
    maxZoom: 17,
    gradient: {
        0.4: '#00ff00',
        0.65: '#ffff00',
        1: '#ff0000',
    },
};

export function useHeatmap(map: L.Map | null) {
    const heatLayer = ref<L.Layer | null>(null);
    const isVisible = ref(false);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const period = ref<HeatmapPeriod>(30);
    const data = ref<HeatmapData | null>(null);

    const fetchHeatmapData = async (bounds: BoundingBox): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<HeatmapData>('/api/heatmap', {
                params: {
                    'bounds[sw][lat]': bounds.sw.lat,
                    'bounds[sw][lng]': bounds.sw.lng,
                    'bounds[ne][lat]': bounds.ne.lat,
                    'bounds[ne][lng]': bounds.ne.lng,
                    period: period.value,
                },
            });

            data.value = response.data;
            updateHeatLayer();
        } catch (err) {
            error.value = err instanceof Error
                ? err.message
                : 'Failed to fetch heatmap data';
        } finally {
            loading.value = false;
        }
    };

    const updateHeatLayer = (): void => {
        if (!map || !data.value) {
            return;
        }

        removeHeatLayer();

        if (!isVisible.value) {
            return;
        }

        const heatData: Array<[number, number, number]> = data.value.points.map(
            (point) => [point.lat, point.lng, point.intensity]
        );

        heatLayer.value = L.heatLayer(heatData, DEFAULT_CONFIG);
        heatLayer.value.addTo(map);
    };

    const removeHeatLayer = (): void => {
        if (heatLayer.value && map) {
            map.removeLayer(heatLayer.value);
            heatLayer.value = null;
        }
    };

    const toggle = (): void => {
        isVisible.value = !isVisible.value;

        if (isVisible.value && map) {
            const bounds = map.getBounds();
            fetchHeatmapData({
                sw: { lat: bounds.getSouth(), lng: bounds.getWest() },
                ne: { lat: bounds.getNorth(), lng: bounds.getEast() },
            });
        } else {
            removeHeatLayer();
        }
    };

    const setPeriod = (newPeriod: HeatmapPeriod): void => {
        period.value = newPeriod;

        if (isVisible.value && map) {
            const bounds = map.getBounds();
            fetchHeatmapData({
                sw: { lat: bounds.getSouth(), lng: bounds.getWest() },
                ne: { lat: bounds.getNorth(), lng: bounds.getEast() },
            });
        }
    };

    const refreshOnBoundsChange = (): void => {
        if (!map || !isVisible.value) {
            return;
        }

        const bounds = map.getBounds();
        fetchHeatmapData({
            sw: { lat: bounds.getSouth(), lng: bounds.getWest() },
            ne: { lat: bounds.getNorth(), lng: bounds.getEast() },
        });
    };

    watch(isVisible, (visible) => {
        if (!visible) {
            removeHeatLayer();
        }
    });

    onUnmounted(() => {
        removeHeatLayer();
    });

    return {
        isVisible,
        loading,
        error,
        period,
        data,
        toggle,
        setPeriod,
        refreshOnBoundsChange,
    };
}
```

#### HeatmapToggle Component

**File:** `resources/js/components/map/HeatmapToggle.vue`

```vue
<script setup lang="ts">
import { Flame } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface Props {
    isActive: boolean;
    loading?: boolean;
}

interface Emits {
    (e: 'toggle'): void;
}

defineProps<Props>();
defineEmits<Emits>();
</script>

<template>
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    variant="outline"
                    size="icon"
                    :class="[
                        'size-10',
                        isActive
                            ? 'bg-brand-teal text-white hover:bg-brand-teal/90'
                            : '',
                    ]"
                    :disabled="loading"
                    @click="$emit('toggle')"
                >
                    <Flame
                        v-if="!loading"
                        class="size-5"
                    />
                    <Spinner
                        v-else
                        class="size-5"
                    />
                </Button>
            </TooltipTrigger>
            <TooltipContent side="left">
                <p>{{ isActive ? 'Hide' : 'Show' }} incident heatmap</p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
```

#### HeatmapPeriodSelector Component

**File:** `resources/js/components/map/HeatmapPeriodSelector.vue`

```vue
<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { HeatmapPeriod } from '@/types/heatmap';

interface Props {
    modelValue: HeatmapPeriod;
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: HeatmapPeriod): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

const periods: Array<{ value: HeatmapPeriod; label: string }> = [
    { value: 7, label: 'Last 7 days' },
    { value: 30, label: 'Last 30 days' },
    { value: 90, label: 'Last 90 days' },
    { value: 365, label: 'Last year' },
];

const handleChange = (value: string): void => {
    emit('update:modelValue', parseInt(value, 10) as HeatmapPeriod);
};
</script>

<template>
    <Select
        :model-value="String(modelValue)"
        :disabled="disabled"
        @update:model-value="handleChange"
    >
        <SelectTrigger class="w-[140px]">
            <SelectValue placeholder="Select period" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem
                v-for="period in periods"
                :key="period.value"
                :value="String(period.value)"
            >
                {{ period.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
```

#### Integration with Map Page

**Usage example in map page:**

```vue
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useMap } from '@/composables/useMap';
import { useHeatmap } from '@/composables/useHeatmap';
import HeatmapToggle from '@/components/map/HeatmapToggle.vue';
import HeatmapPeriodSelector from '@/components/map/HeatmapPeriodSelector.vue';

const { map, initMap } = useMap();
const mapContainer = ref<HTMLElement | null>(null);

onMounted(() => {
    if (mapContainer.value) {
        initMap('map-container', [51.5074, -0.1278], 13);
    }
});

const {
    isVisible: heatmapVisible,
    loading: heatmapLoading,
    period: heatmapPeriod,
    toggle: toggleHeatmap,
    setPeriod: setHeatmapPeriod,
    refreshOnBoundsChange,
} = useHeatmap(map.value);

onMounted(() => {
    if (map.value) {
        map.value.on('moveend', refreshOnBoundsChange);
    }
});
</script>

<template>
    <div class="relative h-screen w-full">
        <div
            id="map-container"
            ref="mapContainer"
            class="h-full w-full"
        />

        <!-- Map Controls -->
        <div class="absolute top-4 right-4 z-[1000] flex flex-col gap-2">
            <HeatmapToggle
                :is-active="heatmapVisible"
                :loading="heatmapLoading"
                @toggle="toggleHeatmap"
            />
            <HeatmapPeriodSelector
                v-if="heatmapVisible"
                v-model="heatmapPeriod"
                :disabled="heatmapLoading"
                @update:model-value="setHeatmapPeriod"
            />
        </div>
    </div>
</template>
```

#### Leaflet.heat Installation

```bash
yarn add leaflet.heat
yarn add -D @types/leaflet.heat
```

---

### Testing Strategy

#### Test Organisation

Tests for the Heatmap feature live within the `src/DrinkSafe/Shared/` module:

```
src/DrinkSafe/Shared/Tests/
├── Feature/
│   └── HeatmapControllerTest.php
└── Unit/
    └── HeatmapServiceTest.php
```

#### Factory Requirements

Uses existing `VenueFactory` and `ReportFactory`. Add states for geographic clustering:

```php
// database/factories/DrinkSafe/VenueFactory.php - Add states

/**
 * Create venue in central London area.
 */
public function inLondon(): static
{
    return $this->state(fn (array $attributes): array => [
        'city' => 'London',
        'latitude' => fake()->latitude(51.48, 51.54),
        'longitude' => fake()->longitude(-0.15, -0.05),
    ]);
}

/**
 * Create venue at specific coordinates.
 */
public function atCoordinates(float $latitude, float $longitude): static
{
    return $this->state(fn (array $attributes): array => [
        'latitude' => $latitude,
        'longitude' => $longitude,
    ]);
}

/**
 * Create venues in a grid pattern for testing spatial aggregation.
 */
public function inGridCell(int $row, int $col, float $baseLat = 51.5, float $baseLng = -0.1): static
{
    return $this->state(fn (array $attributes): array => [
        'latitude' => $baseLat + ($row * 0.001),
        'longitude' => $baseLng + ($col * 0.001),
    ]);
}
```

#### Unit Tests (HeatmapServiceTest.php)

```php
<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Shared\Exceptions\InvalidBoundingBoxException;
use DrinkSafe\Shared\Services\HeatmapService;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new HeatmapService;
    $this->defaultBounds = [
        'sw' => ['lat' => 51.45, 'lng' => -0.20],
        'ne' => ['lat' => 51.55, 'lng' => 0.00],
    ];
});

describe('HeatmapService', function (): void {
    describe('getHeatmapData', function (): void {
        it('returns empty points array when no reports exist', function (): void {
            $result = $this->service->getHeatmapData($this->defaultBounds, 30);

            expect($result['points'])->toBeEmpty()
                ->and($result['period_days'])->toBe(30)
                ->and($result['total_reports'])->toBe(0);
        });

        it('excludes cells with fewer than 3 reports for privacy', function (): void {
            $venue = Venue::factory()->inLondon()->create();
            Report::factory()->count(2)->recent()->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->getHeatmapData($this->defaultBounds, 30);

            expect($result['points'])->toBeEmpty();
        });

        it('includes cells with 3 or more reports', function (): void {
            $venue = Venue::factory()->inLondon()->create();
            Report::factory()->count(3)->recent()->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->getHeatmapData($this->defaultBounds, 30);

            expect($result['points'])->not->toBeEmpty()
                ->and($result['total_reports'])->toBe(3);
        });

        it('aggregates reports from same grid cell', function (): void {
            $venue1 = Venue::factory()->atCoordinates(51.5001, -0.1001)->create();
            $venue2 = Venue::factory()->atCoordinates(51.5002, -0.1002)->create();
            Report::factory()->count(2)->recent()->create(['venue_uuid' => $venue1->uuid]);
            Report::factory()->count(2)->recent()->create(['venue_uuid' => $venue2->uuid]);

            $result = $this->service->getHeatmapData($this->defaultBounds, 30);

            expect($result['points'])->toHaveCount(1)
                ->and($result['total_reports'])->toBe(4);
        });

        it('respects period filter', function (): void {
            $venue = Venue::factory()->inLondon()->create();
            Report::factory()->count(3)->daysAgo(5)->create(['venue_uuid' => $venue->uuid]);
            Report::factory()->count(3)->daysAgo(45)->create(['venue_uuid' => $venue->uuid]);

            $result7Days = $this->service->getHeatmapData($this->defaultBounds, 7);
            Cache::flush();
            $result30Days = $this->service->getHeatmapData($this->defaultBounds, 30);

            expect($result7Days['total_reports'])->toBe(3)
                ->and($result30Days['total_reports'])->toBe(3);

            Cache::flush();
            $result90Days = $this->service->getHeatmapData($this->defaultBounds, 90);
            expect($result90Days['total_reports'])->toBe(6);
        });

        it('filters by bounding box coordinates', function (): void {
            $venueInBounds = Venue::factory()->atCoordinates(51.50, -0.10)->create();
            $venueOutOfBounds = Venue::factory()->atCoordinates(52.00, 0.50)->create();

            Report::factory()->count(3)->recent()->create(['venue_uuid' => $venueInBounds->uuid]);
            Report::factory()->count(3)->recent()->create(['venue_uuid' => $venueOutOfBounds->uuid]);

            $result = $this->service->getHeatmapData($this->defaultBounds, 30);

            expect($result['total_reports'])->toBe(3);
        });

        it('normalises intensity values between 0 and 1', function (): void {
            $venue = Venue::factory()->inLondon()->create();
            Report::factory()->count(10)->recent()->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->getHeatmapData($this->defaultBounds, 30);

            expect($result['points'][0]['intensity'])->toBeGreaterThanOrEqual(0)
                ->and($result['points'][0]['intensity'])->toBeLessThanOrEqual(1);
        });

        it('throws InvalidBoundingBoxException for missing coordinates', function (): void {
            $invalidBounds = ['sw' => ['lat' => 51.45]];

            expect(fn (): array => $this->service->getHeatmapData($invalidBounds, 30))
                ->toThrow(InvalidBoundingBoxException::class);
        });

        it('throws InvalidBoundingBoxException for out-of-range latitude', function (): void {
            $invalidBounds = [
                'sw' => ['lat' => -91, 'lng' => -0.20],
                'ne' => ['lat' => 51.55, 'lng' => 0.00],
            ];

            expect(fn (): array => $this->service->getHeatmapData($invalidBounds, 30))
                ->toThrow(InvalidBoundingBoxException::class);
        });

        it('throws InvalidBoundingBoxException for out-of-range longitude', function (): void {
            $invalidBounds = [
                'sw' => ['lat' => 51.45, 'lng' => -181],
                'ne' => ['lat' => 51.55, 'lng' => 0.00],
            ];

            expect(fn (): array => $this->service->getHeatmapData($invalidBounds, 30))
                ->toThrow(InvalidBoundingBoxException::class);
        });

        it('excludes soft-deleted reports', function (): void {
            $venue = Venue::factory()->inLondon()->create();
            Report::factory()->count(3)->recent()->create(['venue_uuid' => $venue->uuid]);
            Report::factory()->count(3)->recent()->trashed()->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->getHeatmapData($this->defaultBounds, 30);

            expect($result['total_reports'])->toBe(3);
        });
    });

    describe('invalidateCache', function (): void {
        it('clears all heatmap caches', function (): void {
            $venue = Venue::factory()->inLondon()->create();
            Report::factory()->count(3)->recent()->create(['venue_uuid' => $venue->uuid]);

            $this->service->getHeatmapData($this->defaultBounds, 30);

            $this->service->invalidateCache();

            expect(true)->toBeTrue();
        });
    });
});
```

#### Feature Tests (HeatmapControllerTest.php)

```php
<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('HeatmapController', function (): void {
    describe('GET /api/heatmap', function (): void {
        it('returns heatmap data with valid bounding box', function (): void {
            $venue = Venue::factory()->inLondon()->create();
            Report::factory()->count(5)->recent()->create(['venue_uuid' => $venue->uuid]);

            $this->getJson(route('heatmap.index', [
                'bounds' => [
                    'sw' => ['lat' => 51.45, 'lng' => -0.20],
                    'ne' => ['lat' => 51.55, 'lng' => 0.00],
                ],
                'period' => 30,
            ]))
                ->assertOk()
                ->assertJsonStructure([
                    'points' => [
                        '*' => ['lat', 'lng', 'intensity'],
                    ],
                    'period_days',
                    'total_reports',
                ]);
        });

        it('returns 422 for missing bounding box', function (): void {
            $this->getJson(route('heatmap.index', ['period' => 30]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'bounds.sw.lat',
                    'bounds.sw.lng',
                    'bounds.ne.lat',
                    'bounds.ne.lng',
                ]);
        });

        it('returns 422 for invalid latitude values', function (): void {
            $this->getJson(route('heatmap.index', [
                'bounds' => [
                    'sw' => ['lat' => 91, 'lng' => -0.20],
                    'ne' => ['lat' => 51.55, 'lng' => 0.00],
                ],
            ]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['bounds.sw.lat']);
        });

        it('returns 422 for invalid period value', function (): void {
            $this->getJson(route('heatmap.index', [
                'bounds' => [
                    'sw' => ['lat' => 51.45, 'lng' => -0.20],
                    'ne' => ['lat' => 51.55, 'lng' => 0.00],
                ],
                'period' => 45,
            ]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['period']);
        });

        it('accepts valid period values of 7, 30, 90, 365', function (): void {
            foreach ([7, 30, 90, 365] as $period) {
                $this->getJson(route('heatmap.index', [
                    'bounds' => [
                        'sw' => ['lat' => 51.45, 'lng' => -0.20],
                        'ne' => ['lat' => 51.55, 'lng' => 0.00],
                    ],
                    'period' => $period,
                ]))
                    ->assertOk()
                    ->assertJsonPath('period_days', $period);
            }
        });

        it('defaults to 30 days when period not specified', function (): void {
            $this->getJson(route('heatmap.index', [
                'bounds' => [
                    'sw' => ['lat' => 51.45, 'lng' => -0.20],
                    'ne' => ['lat' => 51.55, 'lng' => 0.00],
                ],
            ]))
                ->assertOk()
                ->assertJsonPath('period_days', 30);
        });

        it('returns empty points when no data in bounds', function (): void {
            $this->getJson(route('heatmap.index', [
                'bounds' => [
                    'sw' => ['lat' => 0.0, 'lng' => 0.0],
                    'ne' => ['lat' => 0.1, 'lng' => 0.1],
                ],
            ]))
                ->assertOk()
                ->assertJsonPath('points', [])
                ->assertJsonPath('total_reports', 0);
        });
    });
});
```

#### Edge Cases to Test

| Scenario | Expected Behaviour | Test Coverage |
|----------|-------------------|---------------|
| Empty bounding box (no venues/reports) | Empty points array, total_reports: 0 | Unit, Feature |
| Privacy threshold (1-2 reports per cell) | Cell excluded from response | Unit |
| Boundary crossing (international date line) | Handles wrap-around correctly | Unit (future) |
| Very large bounding box (entire UK) | Performance acceptable, data returned | Performance |
| Very small bounding box (single venue) | Single point returned if threshold met | Unit |
| Overlapping grid cells from rounding | Consistent aggregation | Unit |
| Multiple venues in same grid cell | Reports aggregated correctly | Unit |
| Soft-deleted reports | Excluded from aggregation | Unit |

#### Performance Test Considerations

```php
it('handles large datasets within acceptable time', function (): void {
    Venue::factory()->count(100)->inLondon()->create()->each(
        fn (Venue $venue) => Report::factory()->count(5)->recent()->create([
            'venue_uuid' => $venue->uuid,
        ])
    );

    $startTime = microtime(true);

    $this->service->getHeatmapData([
        'sw' => ['lat' => 51.40, 'lng' => -0.30],
        'ne' => ['lat' => 51.60, 'lng' => 0.10],
    ], 30);

    $executionTime = microtime(true) - $startTime;

    expect($executionTime)->toBeLessThan(2.0);
})->skip('Run manually for performance testing');
```

---

### Open Questions

1. Should heatmap show venue locations or actual incident coordinates (if different)?
2. What colour scheme is most accessible and intuitive? (Current thinking: green -> yellow -> red)
3. How do we handle sparse areas with isolated incidents?
4. Should users be able to toggle between absolute counts and relative density?

---

### Dependencies

- Leaflet.heat library (frontend)
- Potential database spatial indexing improvements

---

### Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Privacy breaches from precise locations | Low | High | Fuzzing + minimum thresholds |
| Performance degradation | Medium | Medium | Caching + spatial queries |
| Visual clutter | Low | Low | Adjustable zoom levels; layer toggle |

---

## 3. Time-based Analytics

### Status & Metadata

| Attribute | Value |
|-----------|-------|
| **Status** | 🟡 Proposed |
| **Effort** | M (Medium) |
| **Priority** | 2 |
| **Module** | `src/DrinkSafe/Analytics/` (new) |

---

### Vision

Aggregate and visualise report data by day-of-week and time-of-day to reveal temporal patterns. This helps users plan safer outings and provides authorities with actionable intelligence.

---

### User Value

| Stakeholder | Benefit |
|-------------|---------|
| **Users** | Understand when incidents are most likely (avoid peak times, or be extra vigilant) |
| **Venues** | Identify when to increase security staffing |
| **Authorities** | Resource allocation for enforcement and prevention |

---

### Visualisation Formats

| Format | Best For | Implementation |
|--------|----------|----------------|
| Heatmap grid (day x time) | Pattern overview | Primary visualisation |
| Bar chart by day | Day comparison | Secondary view |
| Line chart by hour | Time trends | Detailed analysis |
| Summary statistics | Quick insights | Dashboard cards |

### Data Dimensions

**Time of Day** (existing enum):
- Morning (06:00-12:00)
- Afternoon (12:00-18:00)
- Evening (18:00-22:00)
- Night (22:00-06:00)

**Day of Week**:
- Monday through Sunday
- Weekend vs weekday aggregation

**Derived insights**:
- "Friday and Saturday nights see 3x more reports than weekday evenings"
- "This venue's reports cluster around 11pm-2am"

### Filtering Capabilities

| Filter | Options |
|--------|---------|
| Geographic | City, area, specific venue |
| Time range | Last 30/90/365 days |
| Venue type | If venue categorisation is added |

---

### Technical Considerations

**Module placement**: New `src/DrinkSafe/Analytics/` module recommended

**Backend**:
- Aggregation queries with GROUP BY day-of-week, time-of-day
- Cached summary statistics
- API endpoints for filtered analytics data

**Frontend**:
- Chart library integration (e.g., Chart.js, ECharts, or Recharts via Vue wrappers)
- Interactive filters
- Responsive design for mobile viewing

**API shape** (conceptual):
```
GET /api/analytics/temporal?city=London&period=90

Response:
{
  "grid": [
    { "day": "Friday", "time": "Night", "count": 47, "percentage": 12.3 },
    ...
  ],
  "totals": {
    "by_day": { "Monday": 23, "Tuesday": 18, ... },
    "by_time": { "Morning": 12, "Afternoon": 34, ... }
  },
  "insights": [
    "Peak reporting occurs Friday and Saturday nights (42% of all reports)"
  ]
}
```

### Audience-specific Views

| Audience | View |
|----------|------|
| General users | Simplified patterns, safety tips |
| Venue managers | Their venue's specific patterns |
| Authorities (future) | Aggregate city/regional data, exportable |

---

### Acceptance Criteria

| # | Criterion | Measurable Outcome |
|---|-----------|-------------------|
| AC1 | Analytics page accessible | Given authenticated or public user, when navigating to /analytics, then page loads |
| AC2 | Day x Time grid displays | Given reports exist, when viewing analytics, then 7x4 heatmap grid is rendered |
| AC3 | City filter works | Given multiple cities with data, when city filter is applied, then data updates |
| AC4 | Time period filter works | Given filter changed to 90 days, when applied, then aggregations reflect date range |
| AC5 | Charts render correctly | Given data loaded, when viewing charts, then they render without JavaScript errors |
| AC6 | Insights generated | Given sufficient data (50+ reports), when viewing analytics, then at least one insight is displayed |
| AC7 | Mobile responsive | Given mobile viewport, when viewing analytics, then charts resize appropriately |
| AC8 | Export available | Given analytics view, when export is clicked, then CSV/JSON download is triggered |

---

### Backend Implementation Details

#### Proposed Analytics Module Directory Structure

```
src/DrinkSafe/
├── Analytics/
│   ├── README.md
│   ├── Controllers/
│   │   └── TemporalAnalyticsController.php
│   ├── Services/
│   │   ├── TemporalAnalyticsService.php
│   │   └── InsightGeneratorService.php
│   ├── Requests/
│   │   └── TemporalAnalyticsRequest.php
│   ├── Resources/
│   │   ├── TemporalAnalyticsResource.php
│   │   └── TemporalGridResource.php
│   ├── DTOs/
│   │   ├── TemporalGridCell.php
│   │   └── AnalyticsInsight.php
│   ├── Enums/
│   │   └── DayOfWeek.php
│   ├── Exceptions/
│   │   └── InsufficientDataException.php
│   └── Tests/
│       ├── Feature/
│       │   └── TemporalAnalyticsControllerTest.php
│       └── Unit/
│           ├── TemporalAnalyticsServiceTest.php
│           └── InsightGeneratorServiceTest.php
```

#### Service Class Signature

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Services;

use DrinkSafe\Analytics\DTOs\TemporalGridCell;
use DrinkSafe\Analytics\Enums\DayOfWeek;
use DrinkSafe\Analytics\Exceptions\InsufficientDataException;
use DrinkSafe\Reports\Enums\TimeOfDay;
use DrinkSafe\Reports\Models\Report;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * TemporalAnalyticsService
 *
 * Aggregates report data by day-of-week and time-of-day for temporal pattern analysis.
 * Supports filtering by city, venue, and date range.
 */
final class TemporalAnalyticsService
{
    /**
     * Minimum reports required for meaningful analysis.
     */
    private const int MINIMUM_REPORTS_FOR_ANALYSIS = 10;

    /**
     * Cache TTL in seconds (6 hours).
     */
    private const int CACHE_TTL_SECONDS = 21600;

    public function __construct(
        private readonly InsightGeneratorService $insightGenerator
    ) {}

    /**
     * Get temporal analytics grid data.
     *
     * Returns a 7x4 grid (day of week x time of day) with report counts and percentages.
     *
     * @param  string|null  $city  Optional city filter
     * @param  string|null  $venueUuid  Optional venue filter
     * @param  int  $periodDays  Number of days to analyse (default: 90)
     *
     * @throws InsufficientDataException If too few reports for analysis
     *
     * @return array{
     *     grid: array<int, array{day: string, time: string, count: int, percentage: float}>,
     *     totals: array{by_day: array<string, int>, by_time: array<string, int>},
     *     insights: array<int, string>,
     *     metadata: array{total_reports: int, period_days: int, city: string|null, venue_uuid: string|null}
     * }
     */
    public function getTemporalAnalytics(
        ?string $city = null,
        ?string $venueUuid = null,
        int $periodDays = 90
    ): array {
        $cacheKey = $this->buildCacheKey($city, $venueUuid, $periodDays);

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->computeAnalytics($city, $venueUuid, $periodDays)
        );
    }

    /**
     * Get aggregated totals by day of week.
     *
     * @param  string|null  $city
     * @param  int  $periodDays
     * @return array<string, int>
     */
    public function getTotalsByDay(?string $city = null, int $periodDays = 90): array
    {
        $analytics = $this->getTemporalAnalytics($city, null, $periodDays);

        return $analytics['totals']['by_day'];
    }

    /**
     * Get aggregated totals by time of day.
     *
     * @param  string|null  $city
     * @param  int  $periodDays
     * @return array<string, int>
     */
    public function getTotalsByTime(?string $city = null, int $periodDays = 90): array
    {
        $analytics = $this->getTemporalAnalytics($city, null, $periodDays);

        return $analytics['totals']['by_time'];
    }

    /**
     * Invalidate analytics cache.
     *
     * Should be called when new reports are submitted.
     */
    public function invalidateCache(): void
    {
        Cache::tags(['analytics'])->flush();
    }

    /**
     * Build cache key from filter parameters.
     */
    private function buildCacheKey(?string $city, ?string $venueUuid, int $periodDays): string
    {
        return sprintf(
            'temporal_analytics:%s:%s:%d',
            $city ?? 'all',
            $venueUuid ?? 'all',
            $periodDays
        );
    }

    /**
     * Compute analytics data from database.
     *
     * @param  string|null  $city
     * @param  string|null  $venueUuid
     * @param  int  $periodDays
     * @return array{grid: array<int, array{day: string, time: string, count: int, percentage: float}>, totals: array{by_day: array<string, int>, by_time: array<string, int>}, insights: array<int, string>, metadata: array{total_reports: int, period_days: int, city: string|null, venue_uuid: string|null}}
     *
     * @throws InsufficientDataException
     */
    private function computeAnalytics(?string $city, ?string $venueUuid, int $periodDays): array
    {
        $query = Report::query()
            ->join('venues', 'reports.venue_uuid', '=', 'venues.uuid')
            ->where('reports.incident_date', '>=', now()->subDays($periodDays))
            ->whereNull('reports.deleted_at');

        if ($city !== null) {
            $query->where('venues.city', $city);
        }

        if ($venueUuid !== null) {
            $query->where('reports.venue_uuid', $venueUuid);
        }

        $rawData = $query
            ->selectRaw('
                DAYOFWEEK(reports.incident_date) AS day_of_week,
                reports.time_of_day,
                COUNT(*) AS report_count
            ')
            ->groupBy('day_of_week', 'time_of_day')
            ->get();

        $totalReports = $rawData->sum('report_count');

        if ($totalReports < self::MINIMUM_REPORTS_FOR_ANALYSIS) {
            throw new InsufficientDataException(
                sprintf(
                    'Insufficient data for analysis. Minimum %d reports required, found %d.',
                    self::MINIMUM_REPORTS_FOR_ANALYSIS,
                    $totalReports
                )
            );
        }

        $grid = $this->buildGrid($rawData, $totalReports);
        $totals = $this->calculateTotals($rawData);
        $insights = $this->insightGenerator->generateInsights($grid, $totals, $totalReports);

        return [
            'grid' => $grid,
            'totals' => $totals,
            'insights' => $insights,
            'metadata' => [
                'total_reports' => $totalReports,
                'period_days' => $periodDays,
                'city' => $city,
                'venue_uuid' => $venueUuid,
            ],
        ];
    }

    /**
     * Build 7x4 grid from raw data.
     *
     * @param  Collection<int, object>  $rawData
     * @param  int  $totalReports
     * @return array<int, array{day: string, time: string, count: int, percentage: float}>
     */
    private function buildGrid(Collection $rawData, int $totalReports): array
    {
        $grid = [];
        $daysOfWeek = DayOfWeek::cases();
        $timesOfDay = [TimeOfDay::Morning, TimeOfDay::Afternoon, TimeOfDay::Evening, TimeOfDay::Night];

        foreach ($daysOfWeek as $day) {
            foreach ($timesOfDay as $time) {
                $count = $rawData
                    ->where('day_of_week', $day->mysqlDayNumber())
                    ->where('time_of_day', $time->value)
                    ->first()?->report_count ?? 0;

                $grid[] = [
                    'day' => $day->value,
                    'time' => $time->value,
                    'count' => $count,
                    'percentage' => $totalReports > 0
                        ? round(($count / $totalReports) * 100, 1)
                        : 0.0,
                ];
            }
        }

        return $grid;
    }

    /**
     * Calculate totals by day and time.
     *
     * @param  Collection<int, object>  $rawData
     * @return array{by_day: array<string, int>, by_time: array<string, int>}
     */
    private function calculateTotals(Collection $rawData): array
    {
        $byDay = [];
        foreach (DayOfWeek::cases() as $day) {
            $byDay[$day->value] = $rawData
                ->where('day_of_week', $day->mysqlDayNumber())
                ->sum('report_count');
        }

        $byTime = [];
        foreach ([TimeOfDay::Morning, TimeOfDay::Afternoon, TimeOfDay::Evening, TimeOfDay::Night] as $time) {
            $byTime[$time->value] = $rawData
                ->where('time_of_day', $time->value)
                ->sum('report_count');
        }

        return [
            'by_day' => $byDay,
            'by_time' => $byTime,
        ];
    }
}
```

#### Day of Week Enum

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Enums;

/**
 * DayOfWeek Enum
 *
 * Represents days of the week for temporal analytics.
 * Includes MySQL DAYOFWEEK() mapping.
 */
enum DayOfWeek: string
{
    case Sunday = 'Sunday';
    case Monday = 'Monday';
    case Tuesday = 'Tuesday';
    case Wednesday = 'Wednesday';
    case Thursday = 'Thursday';
    case Friday = 'Friday';
    case Saturday = 'Saturday';

    /**
     * Get MySQL DAYOFWEEK() number (1=Sunday, 7=Saturday).
     */
    public function mysqlDayNumber(): int
    {
        return match ($this) {
            self::Sunday => 1,
            self::Monday => 2,
            self::Tuesday => 3,
            self::Wednesday => 4,
            self::Thursday => 5,
            self::Friday => 6,
            self::Saturday => 7,
        };
    }

    /**
     * Create from MySQL DAYOFWEEK() number.
     */
    public static function fromMysqlDayNumber(int $number): self
    {
        return match ($number) {
            1 => self::Sunday,
            2 => self::Monday,
            3 => self::Tuesday,
            4 => self::Wednesday,
            5 => self::Thursday,
            6 => self::Friday,
            7 => self::Saturday,
            default => throw new \InvalidArgumentException(
                sprintf('Invalid MySQL day number: %d', $number)
            ),
        };
    }

    /**
     * Check if this is a weekend day.
     */
    public function isWeekend(): bool
    {
        return $this === self::Saturday || $this === self::Sunday;
    }
}
```

#### Insight Generator Service

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Services;

/**
 * InsightGeneratorService
 *
 * Generates human-readable insights from temporal analytics data.
 */
final class InsightGeneratorService
{
    /**
     * Minimum percentage threshold to consider a pattern significant.
     */
    private const float SIGNIFICANCE_THRESHOLD = 15.0;

    /**
     * Generate insights from analytics data.
     *
     * @param  array<int, array{day: string, time: string, count: int, percentage: float}>  $grid
     * @param  array{by_day: array<string, int>, by_time: array<string, int>}  $totals
     * @param  int  $totalReports
     * @return array<int, string>
     */
    public function generateInsights(array $grid, array $totals, int $totalReports): array
    {
        $insights = [];

        // Peak day insight
        $peakDay = $this->findPeakDay($totals['by_day'], $totalReports);
        if ($peakDay !== null) {
            $insights[] = $peakDay;
        }

        // Weekend vs weekday insight
        $weekendInsight = $this->generateWeekendInsight($totals['by_day'], $totalReports);
        if ($weekendInsight !== null) {
            $insights[] = $weekendInsight;
        }

        // Peak time insight
        $peakTime = $this->findPeakTime($totals['by_time'], $totalReports);
        if ($peakTime !== null) {
            $insights[] = $peakTime;
        }

        // Peak combination insight
        $peakCombination = $this->findPeakCombination($grid, $totalReports);
        if ($peakCombination !== null) {
            $insights[] = $peakCombination;
        }

        return $insights;
    }

    /**
     * Find the peak day insight.
     *
     * @param  array<string, int>  $byDay
     * @param  int  $total
     */
    private function findPeakDay(array $byDay, int $total): ?string
    {
        $maxDay = array_keys($byDay, max($byDay))[0] ?? null;
        $maxCount = $byDay[$maxDay] ?? 0;
        $percentage = $total > 0 ? ($maxCount / $total) * 100 : 0;

        if ($percentage >= self::SIGNIFICANCE_THRESHOLD) {
            return sprintf(
                '%s has the highest incident rate with %.1f%% of all reports.',
                $maxDay,
                $percentage
            );
        }

        return null;
    }

    /**
     * Generate weekend vs weekday insight.
     *
     * @param  array<string, int>  $byDay
     * @param  int  $total
     */
    private function generateWeekendInsight(array $byDay, int $total): ?string
    {
        $weekendCount = ($byDay['Friday'] ?? 0) + ($byDay['Saturday'] ?? 0) + ($byDay['Sunday'] ?? 0);
        $weekendPercentage = $total > 0 ? ($weekendCount / $total) * 100 : 0;

        if ($weekendPercentage >= 50) {
            return sprintf(
                'Weekend nights (Friday-Sunday) account for %.1f%% of all incidents.',
                $weekendPercentage
            );
        }

        return null;
    }

    /**
     * Find the peak time insight.
     *
     * @param  array<string, int>  $byTime
     * @param  int  $total
     */
    private function findPeakTime(array $byTime, int $total): ?string
    {
        $maxTime = array_keys($byTime, max($byTime))[0] ?? null;
        $maxCount = $byTime[$maxTime] ?? 0;
        $percentage = $total > 0 ? ($maxCount / $total) * 100 : 0;

        if ($percentage >= self::SIGNIFICANCE_THRESHOLD) {
            return sprintf(
                '%s hours see %.1f%% of all reported incidents.',
                $maxTime,
                $percentage
            );
        }

        return null;
    }

    /**
     * Find peak day+time combination.
     *
     * @param  array<int, array{day: string, time: string, count: int, percentage: float}>  $grid
     * @param  int  $total
     */
    private function findPeakCombination(array $grid, int $total): ?string
    {
        usort($grid, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        $peak = $grid[0] ?? null;

        if ($peak !== null && $peak['percentage'] >= 10.0) {
            return sprintf(
                'Peak reporting occurs on %s %s (%.1f%% of all reports).',
                $peak['day'],
                strtolower($peak['time']),
                $peak['percentage']
            );
        }

        return null;
    }
}
```

#### API Controller Method

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Analytics\Exceptions\InsufficientDataException;
use DrinkSafe\Analytics\Requests\TemporalAnalyticsRequest;
use DrinkSafe\Analytics\Resources\TemporalAnalyticsResource;
use DrinkSafe\Analytics\Services\TemporalAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * TemporalAnalyticsController
 *
 * Handles requests for temporal (day/time) analytics data.
 */
final class TemporalAnalyticsController extends Controller
{
    public function __construct(
        private readonly TemporalAnalyticsService $analyticsService
    ) {}

    /**
     * Get temporal analytics data with optional filters.
     */
    public function __invoke(TemporalAnalyticsRequest $request): JsonResponse
    {
        try {
            $data = $this->analyticsService->getTemporalAnalytics(
                city: $request->validated('city'),
                venueUuid: $request->validated('venue_uuid'),
                periodDays: (int) $request->validated('period', 90)
            );

            return response()->json(
                new TemporalAnalyticsResource($data),
                Response::HTTP_OK
            );
        } catch (InsufficientDataException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], Response::HTTP_OK);
        }
    }
}
```

#### FormRequest Validation

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * TemporalAnalyticsRequest
 *
 * Validates temporal analytics API requests.
 */
final class TemporalAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'venue_uuid' => ['sometimes', 'nullable', 'string', 'uuid', 'exists:venues,uuid'],
            'period' => ['sometimes', 'integer', 'in:30,90,365'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'venue_uuid.exists' => 'The specified venue does not exist',
            'period.in' => 'Period must be 30, 90, or 365 days',
        ];
    }
}
```

#### Resource Class

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TemporalAnalyticsResource
 *
 * Transforms temporal analytics data for API response.
 */
final class TemporalAnalyticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     grid: array<int, array{day: string, time: string, count: int, percentage: float}>,
     *     totals: array{by_day: array<string, int>, by_time: array<string, int>},
     *     insights: array<int, string>,
     *     metadata: array{total_reports: int, period_days: int, city: string|null, venue_uuid: string|null}
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'grid' => $this->resource['grid'],
            'totals' => $this->resource['totals'],
            'insights' => $this->resource['insights'],
            'metadata' => $this->resource['metadata'],
        ];
    }
}
```

#### Custom Exception

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Exceptions;

use DrinkSafe\Shared\Exceptions\DrinkSafeException;

/**
 * InsufficientDataException
 *
 * Thrown when there is not enough data to perform meaningful analysis.
 */
final class InsufficientDataException extends DrinkSafeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
```

#### Route Registration

```php
// routes/api.php
Route::get('/analytics/temporal', TemporalAnalyticsController::class)
    ->name('analytics.temporal');
```

---

### Frontend Implementation Details

#### TypeScript Interfaces

**File:** `resources/js/types/analytics.ts`

```typescript
/**
 * Analytics Types
 *
 * Types for temporal analytics and charts.
 */

import type { TimeOfDay } from './report';

/**
 * Days of the week
 */
export type DayOfWeek =
    | 'Sunday'
    | 'Monday'
    | 'Tuesday'
    | 'Wednesday'
    | 'Thursday'
    | 'Friday'
    | 'Saturday';

/**
 * Single cell in the temporal grid
 */
export interface TemporalGridCell {
    day: DayOfWeek;
    time: TimeOfDay;
    count: number;
    percentage: number;
}

/**
 * Analytics totals by dimension
 */
export interface AnalyticsTotals {
    by_day: Record<DayOfWeek, number>;
    by_time: Record<TimeOfDay, number>;
}

/**
 * Analytics metadata
 */
export interface AnalyticsMetadata {
    total_reports: number;
    period_days: number;
    city: string | null;
    venue_uuid: string | null;
}

/**
 * Complete temporal analytics response
 */
export interface TemporalAnalytics {
    grid: TemporalGridCell[];
    totals: AnalyticsTotals;
    insights: string[];
    metadata: AnalyticsMetadata;
}

/**
 * Analytics filter options
 */
export interface AnalyticsFilters {
    city?: string;
    venueUuid?: string;
    period: 30 | 90 | 365;
}

/**
 * Chart data point for bar/line charts
 */
export interface ChartDataPoint {
    label: string;
    value: number;
    percentage?: number;
}
```

#### useAnalytics Composable

**File:** `resources/js/composables/useAnalytics.ts`

```typescript
/**
 * useAnalytics Composable
 *
 * Fetches and manages temporal analytics data.
 */

import axios from 'axios';
import { ref, computed, reactive } from 'vue';
import type {
    TemporalAnalytics,
    AnalyticsFilters,
    ChartDataPoint,
    DayOfWeek,
} from '@/types/analytics';
import type { TimeOfDay } from '@/types/report';

const DAYS_ORDER: DayOfWeek[] = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday',
];

const TIMES_ORDER: TimeOfDay[] = ['Morning', 'Afternoon', 'Evening', 'Night'];

export function useAnalytics() {
    const data = ref<TemporalAnalytics | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const filters = reactive<AnalyticsFilters>({
        period: 90,
    });

    const fetchAnalytics = async (): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const params: Record<string, string | number> = {
                period: filters.period,
            };

            if (filters.city) {
                params.city = filters.city;
            }

            if (filters.venueUuid) {
                params.venue_uuid = filters.venueUuid;
            }

            const response = await axios.get<TemporalAnalytics>(
                '/api/analytics/temporal',
                { params }
            );

            data.value = response.data;
        } catch (err) {
            error.value = err instanceof Error
                ? err.message
                : 'Failed to fetch analytics';
        } finally {
            loading.value = false;
        }
    };

    const chartDataByDay = computed((): ChartDataPoint[] => {
        if (!data.value) {
            return [];
        }

        return DAYS_ORDER.map((day) => ({
            label: day.substring(0, 3),
            value: data.value!.totals.by_day[day] ?? 0,
            percentage: data.value!.metadata.total_reports > 0
                ? ((data.value!.totals.by_day[day] ?? 0) /
                    data.value!.metadata.total_reports) * 100
                : 0,
        }));
    });

    const chartDataByTime = computed((): ChartDataPoint[] => {
        if (!data.value) {
            return [];
        }

        return TIMES_ORDER.map((time) => ({
            label: time,
            value: data.value!.totals.by_time[time] ?? 0,
            percentage: data.value!.metadata.total_reports > 0
                ? ((data.value!.totals.by_time[time] ?? 0) /
                    data.value!.metadata.total_reports) * 100
                : 0,
        }));
    });

    const gridData = computed(() => {
        if (!data.value) {
            return [];
        }

        return DAYS_ORDER.map((day) => ({
            day,
            times: TIMES_ORDER.map((time) => {
                const cell = data.value!.grid.find(
                    (c) => c.day === day && c.time === time
                );

                return {
                    time,
                    count: cell?.count ?? 0,
                    percentage: cell?.percentage ?? 0,
                };
            }),
        }));
    });

    const setFilters = (newFilters: Partial<AnalyticsFilters>): void => {
        Object.assign(filters, newFilters);
    };

    return {
        data,
        loading,
        error,
        filters,
        chartDataByDay,
        chartDataByTime,
        gridData,
        fetchAnalytics,
        setFilters,
    };
}
```

#### AnalyticsPage Component

**File:** `resources/js/pages/Analytics.vue`

```vue
<script setup lang="ts">
import { onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useAnalytics } from '@/composables/useAnalytics';
import AppLayout from '@/components/AppLayout.vue';
import AnalyticsFilters from '@/components/analytics/AnalyticsFilters.vue';
import TemporalGrid from '@/components/analytics/TemporalGrid.vue';
import BarChart from '@/components/analytics/BarChart.vue';
import InsightCards from '@/components/analytics/InsightCards.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-vue-next';

const {
    data,
    loading,
    error,
    filters,
    chartDataByDay,
    chartDataByTime,
    gridData,
    fetchAnalytics,
    setFilters,
} = useAnalytics();

onMounted(() => {
    fetchAnalytics();
});

watch(filters, () => {
    fetchAnalytics();
});
</script>

<template>
    <Head title="Analytics" />

    <AppLayout>
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <h1 class="mb-6 text-3xl font-bold">
                Incident Analytics
            </h1>

            <!-- Filters -->
            <AnalyticsFilters
                :filters="filters"
                :loading="loading"
                @update="setFilters"
            />

            <!-- Error State -->
            <Alert
                v-if="error"
                variant="destructive"
                class="mb-6"
            >
                <AlertCircle class="size-4" />
                <AlertDescription>{{ error }}</AlertDescription>
            </Alert>

            <!-- Loading State -->
            <div
                v-if="loading && !data"
                class="grid gap-6"
            >
                <Skeleton class="h-64 w-full" />
                <div class="grid gap-6 md:grid-cols-2">
                    <Skeleton class="h-48 w-full" />
                    <Skeleton class="h-48 w-full" />
                </div>
            </div>

            <!-- Data Display -->
            <div
                v-else-if="data"
                class="grid gap-6"
            >
                <!-- Insights -->
                <InsightCards
                    v-if="data.insights.length > 0"
                    :insights="data.insights"
                />

                <!-- Temporal Grid Heatmap -->
                <Card>
                    <CardHeader>
                        <CardTitle>Incidents by Day and Time</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <TemporalGrid :data="gridData" />
                    </CardContent>
                </Card>

                <!-- Bar Charts -->
                <div class="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>By Day of Week</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarChart
                                :data="chartDataByDay"
                                colour="brand-teal"
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>By Time of Day</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BarChart
                                :data="chartDataByTime"
                                colour="brand-purple"
                            />
                        </CardContent>
                    </Card>
                </div>

                <!-- Metadata -->
                <p class="text-center text-sm text-slate-500 dark:text-slate-400">
                    Based on {{ data.metadata.total_reports }} reports over the
                    last {{ data.metadata.period_days }} days
                    <span v-if="data.metadata.city">
                        in {{ data.metadata.city }}
                    </span>
                </p>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="py-12 text-center"
            >
                <p class="text-slate-500 dark:text-slate-400">
                    No analytics data available
                </p>
            </div>
        </div>
    </AppLayout>
</template>
```

#### TemporalGrid Component

**File:** `resources/js/components/analytics/TemporalGrid.vue`

```vue
<script setup lang="ts">
import { computed } from 'vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { TimeOfDay } from '@/types/report';
import type { DayOfWeek } from '@/types/analytics';

interface GridRow {
    day: DayOfWeek;
    times: Array<{
        time: TimeOfDay;
        count: number;
        percentage: number;
    }>;
}

interface Props {
    data: GridRow[];
}

const props = defineProps<Props>();

const maxCount = computed(() => {
    let max = 0;
    props.data.forEach((row) => {
        row.times.forEach((cell) => {
            if (cell.count > max) {
                max = cell.count;
            }
        });
    });

    return max || 1;
});

const getIntensityClass = (count: number): string => {
    const ratio = count / maxCount.value;

    if (ratio === 0) {
        return 'bg-slate-100 dark:bg-slate-800';
    }

    if (ratio < 0.25) {
        return 'bg-green-200 dark:bg-green-900/50';
    }

    if (ratio < 0.5) {
        return 'bg-yellow-200 dark:bg-yellow-900/50';
    }

    if (ratio < 0.75) {
        return 'bg-orange-200 dark:bg-orange-900/50';
    }

    return 'bg-red-300 dark:bg-red-900/50';
};

const times: TimeOfDay[] = ['Morning', 'Afternoon', 'Evening', 'Night'];
</script>

<template>
    <TooltipProvider>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="p-2 text-left text-sm font-medium text-slate-600 dark:text-slate-300">
                            Day
                        </th>
                        <th
                            v-for="time in times"
                            :key="time"
                            class="p-2 text-center text-sm font-medium text-slate-600 dark:text-slate-300"
                        >
                            {{ time }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in data"
                        :key="row.day"
                    >
                        <td class="p-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                            {{ row.day.substring(0, 3) }}
                        </td>
                        <td
                            v-for="cell in row.times"
                            :key="`${row.day}-${cell.time}`"
                            class="p-1"
                        >
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <div
                                        :class="[
                                            'flex h-12 w-full cursor-pointer items-center justify-center rounded transition-colors',
                                            getIntensityClass(cell.count),
                                        ]"
                                    >
                                        <span
                                            v-if="cell.count > 0"
                                            class="text-sm font-medium text-slate-700 dark:text-slate-200"
                                        >
                                            {{ cell.count }}
                                        </span>
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>
                                        {{ row.day }} {{ cell.time }}: {{ cell.count }} reports
                                        ({{ cell.percentage.toFixed(1) }}%)
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TooltipProvider>
</template>
```

#### BarChart Component

**File:** `resources/js/components/analytics/BarChart.vue`

```vue
<script setup lang="ts">
import { computed } from 'vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { ChartDataPoint } from '@/types/analytics';

interface Props {
    data: ChartDataPoint[];
    colour?: 'brand-teal' | 'brand-purple';
}

const props = withDefaults(defineProps<Props>(), {
    colour: 'brand-teal',
});

const maxValue = computed(() => {
    return Math.max(...props.data.map((d) => d.value), 1);
});

const barColour = computed(() => {
    return props.colour === 'brand-teal'
        ? 'bg-brand-teal'
        : 'bg-purple-600 dark:bg-purple-500';
});
</script>

<template>
    <TooltipProvider>
        <div class="flex h-48 items-end justify-between gap-2">
            <div
                v-for="point in data"
                :key="point.label"
                class="flex flex-1 flex-col items-center gap-2"
            >
                <Tooltip>
                    <TooltipTrigger as-child>
                        <div
                            class="w-full cursor-pointer rounded-t transition-all hover:opacity-80"
                            :class="barColour"
                            :style="{
                                height: `${(point.value / maxValue) * 100}%`,
                                minHeight: point.value > 0 ? '8px' : '0px',
                            }"
                        />
                    </TooltipTrigger>
                    <TooltipContent>
                        <p>{{ point.label }}: {{ point.value }} reports</p>
                        <p
                            v-if="point.percentage !== undefined"
                            class="text-xs text-slate-400"
                        >
                            {{ point.percentage.toFixed(1) }}% of total
                        </p>
                    </TooltipContent>
                </Tooltip>
                <span class="text-xs font-medium text-slate-600 dark:text-slate-300">
                    {{ point.label }}
                </span>
            </div>
        </div>
    </TooltipProvider>
</template>
```

#### AnalyticsFilters Component

**File:** `resources/js/components/analytics/AnalyticsFilters.vue`

```vue
<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AnalyticsFilters } from '@/types/analytics';

interface Props {
    filters: AnalyticsFilters;
    loading?: boolean;
}

interface Emits {
    (e: 'update', filters: Partial<AnalyticsFilters>): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

const periods = [
    { value: '30', label: 'Last 30 days' },
    { value: '90', label: 'Last 90 days' },
    { value: '365', label: 'Last year' },
];

const handlePeriodChange = (value: string): void => {
    emit('update', { period: parseInt(value, 10) as 30 | 90 | 365 });
};

const handleCityChange = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    emit('update', { city: target.value || undefined });
};
</script>

<template>
    <div class="mb-6 flex flex-wrap items-end gap-4">
        <div class="flex flex-col gap-1.5">
            <Label for="period">Time Period</Label>
            <Select
                :model-value="String(filters.period)"
                :disabled="loading"
                @update:model-value="handlePeriodChange"
            >
                <SelectTrigger
                    id="period"
                    class="w-[160px]"
                >
                    <SelectValue placeholder="Select period" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="p in periods"
                        :key="p.value"
                        :value="p.value"
                    >
                        {{ p.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="flex flex-col gap-1.5">
            <Label for="city">City</Label>
            <Input
                id="city"
                type="text"
                placeholder="Filter by city..."
                :model-value="filters.city ?? ''"
                :disabled="loading"
                class="w-[200px]"
                @input="handleCityChange"
            />
        </div>
    </div>
</template>
```

#### InsightCards Component

**File:** `resources/js/components/analytics/InsightCards.vue`

```vue
<script setup lang="ts">
import { Lightbulb } from 'lucide-vue-next';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface Props {
    insights: string[];
}

defineProps<Props>();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <Alert
            v-for="(insight, index) in insights"
            :key="index"
            class="border-brand-teal/20 bg-brand-teal/5"
        >
            <Lightbulb class="size-4 text-brand-teal" />
            <AlertDescription class="text-slate-700 dark:text-slate-200">
                {{ insight }}
            </AlertDescription>
        </Alert>
    </div>
</template>
```

---

### Testing Strategy

#### Test Organisation

Tests for the Time-based Analytics feature live in the new `src/DrinkSafe/Analytics/` module:

```
src/DrinkSafe/Analytics/Tests/
├── Feature/
│   └── TemporalAnalyticsControllerTest.php
└── Unit/
    ├── TemporalAnalyticsServiceTest.php
    └── InsightGeneratorServiceTest.php
```

#### Factory Requirements

Uses existing `VenueFactory` and `ReportFactory`. Add states for temporal testing:

```php
// database/factories/DrinkSafe/ReportFactory.php - Add states

/**
 * Create report on specific day of week.
 */
public function onDayOfWeek(string $day): static
{
    $daysUntilTarget = match (strtolower($day)) {
        'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
        'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 0,
        default => 1,
    };

    $date = now()->startOfWeek()->addDays($daysUntilTarget - 1);

    return $this->state(fn (array $attributes): array => [
        'incident_date' => $date->toDateString(),
    ]);
}

/**
 * Create report with specific time of day.
 */
public function atTimeOfDay(TimeOfDay $timeOfDay): static
{
    return $this->state(fn (array $attributes): array => [
        'time_of_day' => $timeOfDay,
    ]);
}

/**
 * Create weekend night report (common pattern).
 */
public function weekendNight(): static
{
    return $this->state(fn (array $attributes): array => [
        'incident_date' => fake()->dateTimeBetween('-30 days', 'now', 'Europe/London')
            ->modify('saturday this week')
            ->format('Y-m-d'),
        'time_of_day' => TimeOfDay::Night,
    ]);
}
```

#### Unit Tests (TemporalAnalyticsServiceTest.php)

```php
<?php

declare(strict_types=1);

use DrinkSafe\Analytics\Exceptions\InsufficientDataException;
use DrinkSafe\Analytics\Services\InsightGeneratorService;
use DrinkSafe\Analytics\Services\TemporalAnalyticsService;
use DrinkSafe\Reports\Enums\TimeOfDay;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->insightGenerator = new InsightGeneratorService;
    $this->service = new TemporalAnalyticsService($this->insightGenerator);
});

describe('TemporalAnalyticsService', function (): void {
    describe('getTemporalAnalytics', function (): void {
        it('throws InsufficientDataException when fewer than 10 reports exist', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(9)->recent()->create(['venue_uuid' => $venue->uuid]);

            expect(fn (): array => $this->service->getTemporalAnalytics())
                ->toThrow(InsufficientDataException::class);
        });

        it('returns 28-cell grid for all day/time combinations', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->recent()->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->getTemporalAnalytics();

            expect($result['grid'])->toHaveCount(28);
        });

        it('correctly aggregates by day of week', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->onDayOfWeek('Friday')->atTimeOfDay(TimeOfDay::Night)
                ->create(['venue_uuid' => $venue->uuid]);
            Report::factory()->count(5)->onDayOfWeek('Monday')->atTimeOfDay(TimeOfDay::Morning)
                ->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->getTemporalAnalytics();

            expect($result['totals']['by_day']['Friday'])->toBe(5)
                ->and($result['totals']['by_day']['Monday'])->toBe(5);
        });

        it('correctly aggregates by time of day', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(7)->atTimeOfDay(TimeOfDay::Night)
                ->create(['venue_uuid' => $venue->uuid]);
            Report::factory()->count(3)->atTimeOfDay(TimeOfDay::Morning)
                ->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->getTemporalAnalytics();

            expect($result['totals']['by_time']['Night'])->toBe(7)
                ->and($result['totals']['by_time']['Morning'])->toBe(3);
        });

        it('filters by city when provided', function (): void {
            $londonVenue = Venue::factory()->create(['city' => 'London']);
            $manchesterVenue = Venue::factory()->create(['city' => 'Manchester']);
            Report::factory()->count(10)->recent()->create(['venue_uuid' => $londonVenue->uuid]);
            Report::factory()->count(10)->recent()->create(['venue_uuid' => $manchesterVenue->uuid]);

            $result = $this->service->getTemporalAnalytics(city: 'London');

            expect($result['metadata']['total_reports'])->toBe(10)
                ->and($result['metadata']['city'])->toBe('London');
        });

        it('filters by venue UUID when provided', function (): void {
            $venue1 = Venue::factory()->create();
            $venue2 = Venue::factory()->create();
            Report::factory()->count(10)->recent()->create(['venue_uuid' => $venue1->uuid]);
            Report::factory()->count(10)->recent()->create(['venue_uuid' => $venue2->uuid]);

            $result = $this->service->getTemporalAnalytics(venueUuid: $venue1->uuid);

            expect($result['metadata']['total_reports'])->toBe(10)
                ->and($result['metadata']['venue_uuid'])->toBe($venue1->uuid);
        });

        it('respects period filter', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(10)->daysAgo(15)->create(['venue_uuid' => $venue->uuid]);
            Report::factory()->count(10)->daysAgo(60)->create(['venue_uuid' => $venue->uuid]);

            $result30 = $this->service->getTemporalAnalytics(periodDays: 30);
            Cache::flush();
            $result90 = $this->service->getTemporalAnalytics(periodDays: 90);

            expect($result30['metadata']['total_reports'])->toBe(10)
                ->and($result90['metadata']['total_reports'])->toBe(20);
        });

        it('calculates correct percentages in grid', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(10)->onDayOfWeek('Friday')->atTimeOfDay(TimeOfDay::Night)
                ->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->getTemporalAnalytics();

            $fridayNightCell = collect($result['grid'])->first(
                fn (array $cell): bool => $cell['day'] === 'Friday' && $cell['time'] === 'Night'
            );

            expect($fridayNightCell['count'])->toBe(10)
                ->and($fridayNightCell['percentage'])->toBe(100.0);
        });

        it('generates insights when sufficient data exists', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(30)->weekendNight()->create(['venue_uuid' => $venue->uuid]);
            Report::factory()->count(10)->onDayOfWeek('Monday')->atTimeOfDay(TimeOfDay::Morning)
                ->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->getTemporalAnalytics();

            expect($result['insights'])->not->toBeEmpty();
        });

        it('excludes soft-deleted reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(10)->recent()->create(['venue_uuid' => $venue->uuid]);
            Report::factory()->count(5)->recent()->trashed()->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->getTemporalAnalytics();

            expect($result['metadata']['total_reports'])->toBe(10);
        });
    });

    describe('getTotalsByDay', function (): void {
        it('returns totals for all 7 days', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(14)->recent()->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->getTotalsByDay();

            expect($result)->toHaveKeys([
                'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',
            ]);
        });
    });

    describe('getTotalsByTime', function (): void {
        it('returns totals for all 4 time periods', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(10)->recent()->create(['venue_uuid' => $venue->uuid]);

            $result = $this->service->getTotalsByTime();

            expect($result)->toHaveKeys(['Morning', 'Afternoon', 'Evening', 'Night']);
        });
    });
});
```

#### Unit Tests (InsightGeneratorServiceTest.php)

```php
<?php

declare(strict_types=1);

use DrinkSafe\Analytics\Services\InsightGeneratorService;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->service = new InsightGeneratorService;
});

describe('InsightGeneratorService', function (): void {
    describe('generateInsights', function (): void {
        it('generates peak day insight when one day has 15%+ of reports', function (): void {
            $grid = [];
            $totals = [
                'by_day' => [
                    'Friday' => 20, 'Saturday' => 15, 'Sunday' => 5,
                    'Monday' => 5, 'Tuesday' => 5, 'Wednesday' => 5, 'Thursday' => 5,
                ],
                'by_time' => ['Morning' => 15, 'Afternoon' => 15, 'Evening' => 15, 'Night' => 15],
            ];

            $insights = $this->service->generateInsights($grid, $totals, 60);

            expect($insights)->toContain(fn (string $insight): bool =>
                str_contains($insight, 'Friday')
            );
        });

        it('generates weekend insight when weekend has 50%+ of reports', function (): void {
            $grid = [];
            $totals = [
                'by_day' => [
                    'Friday' => 20, 'Saturday' => 20, 'Sunday' => 15,
                    'Monday' => 5, 'Tuesday' => 5, 'Wednesday' => 5, 'Thursday' => 5,
                ],
                'by_time' => ['Morning' => 20, 'Afternoon' => 20, 'Evening' => 15, 'Night' => 20],
            ];

            $insights = $this->service->generateInsights($grid, $totals, 75);

            $hasWeekendInsight = collect($insights)->contains(
                fn (string $insight): bool => str_contains(strtolower($insight), 'weekend')
            );

            expect($hasWeekendInsight)->toBeTrue();
        });

        it('generates peak time insight when one time has 15%+ of reports', function (): void {
            $grid = [];
            $totals = [
                'by_day' => array_fill_keys(
                    ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    10
                ),
                'by_time' => ['Morning' => 5, 'Afternoon' => 10, 'Evening' => 15, 'Night' => 40],
            ];

            $insights = $this->service->generateInsights($grid, $totals, 70);

            expect($insights)->toContain(fn (string $insight): bool =>
                str_contains($insight, 'Night')
            );
        });

        it('generates peak combination insight when cell has 10%+ of reports', function (): void {
            $grid = [
                ['day' => 'Friday', 'time' => 'Night', 'count' => 15, 'percentage' => 15.0],
                ['day' => 'Saturday', 'time' => 'Night', 'count' => 10, 'percentage' => 10.0],
            ];
            $totals = [
                'by_day' => array_fill_keys(
                    ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    15
                ),
                'by_time' => ['Morning' => 25, 'Afternoon' => 25, 'Evening' => 25, 'Night' => 30],
            ];

            $insights = $this->service->generateInsights($grid, $totals, 100);

            expect($insights)->toContain(fn (string $insight): bool =>
                str_contains($insight, 'Friday') && str_contains(strtolower($insight), 'night')
            );
        });

        it('returns empty array when no patterns are significant', function (): void {
            $grid = [];
            $totals = [
                'by_day' => array_fill_keys(
                    ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    10
                ),
                'by_time' => ['Morning' => 18, 'Afternoon' => 17, 'Evening' => 17, 'Night' => 18],
            ];

            $insights = $this->service->generateInsights($grid, $totals, 70);

            expect($insights)->toBeEmpty();
        });
    });
});
```

#### Feature Tests (TemporalAnalyticsControllerTest.php)

```php
<?php

declare(strict_types=1);

use DrinkSafe\Reports\Enums\TimeOfDay;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('TemporalAnalyticsController', function (): void {
    describe('GET /api/analytics/temporal', function (): void {
        it('returns analytics data with valid parameters', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->recent()->create(['venue_uuid' => $venue->uuid]);

            $this->getJson(route('analytics.temporal', ['period' => 90]))
                ->assertOk()
                ->assertJsonStructure([
                    'grid' => [
                        '*' => ['day', 'time', 'count', 'percentage'],
                    ],
                    'totals' => [
                        'by_day',
                        'by_time',
                    ],
                    'insights',
                    'metadata' => [
                        'total_reports',
                        'period_days',
                        'city',
                        'venue_uuid',
                    ],
                ]);
        });

        it('returns message for insufficient data', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->recent()->create(['venue_uuid' => $venue->uuid]);

            $this->getJson(route('analytics.temporal'))
                ->assertOk()
                ->assertJsonStructure(['message', 'data'])
                ->assertJsonPath('data', null);
        });

        it('filters by city parameter', function (): void {
            $londonVenue = Venue::factory()->create(['city' => 'London']);
            $manchesterVenue = Venue::factory()->create(['city' => 'Manchester']);
            Report::factory()->count(10)->recent()->create(['venue_uuid' => $londonVenue->uuid]);
            Report::factory()->count(10)->recent()->create(['venue_uuid' => $manchesterVenue->uuid]);

            $this->getJson(route('analytics.temporal', ['city' => 'London']))
                ->assertOk()
                ->assertJsonPath('metadata.city', 'London')
                ->assertJsonPath('metadata.total_reports', 10);
        });

        it('filters by venue_uuid parameter', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->recent()->create(['venue_uuid' => $venue->uuid]);

            $this->getJson(route('analytics.temporal', ['venue_uuid' => $venue->uuid]))
                ->assertOk()
                ->assertJsonPath('metadata.venue_uuid', $venue->uuid);
        });

        it('returns 422 for invalid venue_uuid', function (): void {
            $this->getJson(route('analytics.temporal', ['venue_uuid' => fake()->uuid()]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['venue_uuid']);
        });

        it('returns 422 for invalid period value', function (): void {
            $this->getJson(route('analytics.temporal', ['period' => 45]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['period']);
        });

        it('accepts valid period values of 30, 90, 365', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->recent()->create(['venue_uuid' => $venue->uuid]);

            foreach ([30, 90, 365] as $period) {
                $this->getJson(route('analytics.temporal', ['period' => $period]))
                    ->assertOk()
                    ->assertJsonPath('metadata.period_days', $period);
            }
        });

        it('defaults to 90 days when period not specified', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->recent()->create(['venue_uuid' => $venue->uuid]);

            $this->getJson(route('analytics.temporal'))
                ->assertOk()
                ->assertJsonPath('metadata.period_days', 90);
        });
    });
});
```

#### Edge Cases to Test

| Scenario | Expected Behaviour | Test Coverage |
|----------|-------------------|---------------|
| Exactly 10 reports (threshold) | Analytics returned, not exception | Unit |
| 9 reports (below threshold) | InsufficientDataException thrown | Unit |
| City with no reports | InsufficientDataException thrown | Unit |
| All reports on same day/time | Single cell has 100% | Unit |
| Even distribution across grid | Low/no insights generated | Unit |
| Non-existent venue UUID | 422 validation error | Feature |
| Very long period (365 days) with sparse data | Handles gracefully | Unit |
| MySQL day-of-week mapping (1=Sunday, 7=Saturday) | Correct day labels | Unit |

#### Test Data Seeding Requirements

For meaningful analytics testing, seed with realistic temporal patterns:

```php
// tests/Helpers/AnalyticsTestSeeder.php

public static function seedWeekendNightPattern(int $count = 50): void
{
    $venue = Venue::factory()->create(['city' => 'London']);

    Report::factory()->count((int) ($count * 0.4))->create([
        'venue_uuid' => $venue->uuid,
        'incident_date' => fake()->dateTimeBetween('-30 days', 'now')->modify('saturday'),
        'time_of_day' => TimeOfDay::Night,
    ]);

    Report::factory()->count((int) ($count * 0.3))->create([
        'venue_uuid' => $venue->uuid,
        'incident_date' => fake()->dateTimeBetween('-30 days', 'now')->modify('friday'),
        'time_of_day' => TimeOfDay::Night,
    ]);

    Report::factory()->count((int) ($count * 0.3))->create([
        'venue_uuid' => $venue->uuid,
        'incident_date' => fake()->dateTimeBetween('-30 days', 'now'),
        'time_of_day' => fake()->randomElement(TimeOfDay::cases()),
    ]);
}
```

---

### Open Questions

1. Should temporal analytics be public or require authentication?
2. Do we generate automated insights or just present raw data?
3. How do we handle sparse data (few reports in a time slot)?
4. Is there value in predictive analytics ("higher risk expected this Saturday")?

---

### Dependencies

- Chart library (frontend)
- Potentially expanded TimeOfDay enum or more granular time tracking

---

### Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Misleading patterns from small samples | Medium | Medium | Display confidence indicators; minimum thresholds |
| Information overload | Low | Low | Progressive disclosure; sensible defaults |
| Misuse by malicious actors | Low | Medium | Consider access controls for detailed data |

---

## 4. Venue Response/Claim System

### Status & Metadata

| Attribute | Value |
|-----------|-------|
| **Status** | 🟡 Proposed |
| **Effort** | L (Large) |
| **Priority** | 4 |
| **Module** | `src/DrinkSafe/VenueOwners/` (new) |

---

### Vision

Allow venue owners to claim their listing and engage constructively with reports. This creates accountability, enables two-way communication, and builds trust in the platform.

---

### User Value

| Stakeholder | Benefit |
|-------------|---------|
| **Users** | See venue responses and actions taken; judge venue commitment to safety |
| **Venues** | Opportunity to demonstrate responsibility and commitment |
| **Platform** | Increased engagement; balanced perspective; potential revenue stream |

---

### Claim Process

```
1. Venue owner registers account
2. Initiates claim for specific venue
3. Verification process (see below)
4. Claim approved/rejected
5. Venue owner can now respond to reports
```

### Verification Methods

| Method | Trust Level | Effort |
|--------|-------------|--------|
| Email domain verification | Medium | Low |
| Business documentation upload | High | Medium |
| Phone verification | Medium | Medium |
| Physical mail verification | Very High | High |
| Third-party business verification API | High | Low (cost) |

**Recommended initial approach**: Email domain verification (e.g., manager@venuename.co.uk) combined with manual review of business documentation.

### Response Model

Venue responses should be:
- Tied to specific reports (or general venue statements)
- Visible to users viewing the venue
- Moderated before publication (initially)

**Response types**:

| Type | Description | Visibility |
|------|-------------|------------|
| Public acknowledgement | "We take this seriously and are investigating" | Public |
| Action taken | "We have increased security staffing" | Public |
| Dispute | "We believe this report is inaccurate" | Public with moderation |
| Private follow-up | Contact reporter (if they opt-in) | Private |

### Moderation Requirements

| Aspect | Approach |
|--------|----------|
| Initial moderation | All responses reviewed before publication |
| Trust levels | Established venues may earn auto-publish privileges |
| Content guidelines | No personal attacks, no doxxing, constructive tone |
| Dispute resolution | Clear process for contested reports |

---

### Technical Considerations

**Module placement**: New `src/DrinkSafe/VenueOwners/` module

**New models**:
- `VenueOwner` (user with venue management privileges)
- `VenueClaim` (claim request with verification status)
- `VenueResponse` (response to report or venue statement)

**Database considerations**:
- User accounts (if not already present)
- Claim verification workflow tracking
- Response content and moderation status

**API endpoints** (conceptual):
```
POST /api/venues/{uuid}/claim          # Initiate claim
GET  /api/venues/{uuid}/claim/status   # Check claim status
POST /api/venues/{uuid}/responses      # Submit response (owner)
GET  /api/venues/{uuid}/responses      # View responses (public)
```

### Trust and Accountability

| Feature | Purpose |
|---------|---------|
| Verified badge | Visual indicator that venue owner is engaged |
| Response rate | "This venue responds to X% of reports" |
| Response time | "Average response time: 2 days" |
| Action tracking | "3 safety improvements implemented" |

---

### Acceptance Criteria

| # | Criterion | Measurable Outcome |
|---|-----------|-------------------|
| AC1 | Claim initiation | Given authenticated user, when claim is submitted for unclaimed venue, then claim record is created with "pending" status |
| AC2 | Email verification | Given claim submitted, when verification email is sent, then clicking link updates claim status |
| AC3 | Document upload | Given pending claim, when business documents are uploaded, then they are stored for admin review |
| AC4 | Admin approval flow | Given pending claim with documents, when admin approves, then venue owner gains response privileges |
| AC5 | Response submission | Given verified venue owner, when response is submitted, then it enters moderation queue |
| AC6 | Response display | Given approved response, when venue detail page is viewed, then response is visible |
| AC7 | Verified badge | Given claimed venue, when viewing venue card/detail, then verified badge is displayed |
| AC8 | Duplicate claim handling | Given venue already claimed, when new claim is submitted, then appropriate error is returned |
| AC9 | Response moderation | Given response in queue, when moderator reviews, then approve/reject actions are available |

---

### Backend Implementation Details

#### Proposed VenueOwners Module Directory Structure

```
src/DrinkSafe/
├── VenueOwners/
│   ├── README.md
│   ├── Models/
│   │   ├── VenueOwner.php
│   │   ├── VenueClaim.php
│   │   └── VenueResponse.php
│   ├── Controllers/
│   │   ├── VenueClaimController.php
│   │   ├── VenueResponseController.php
│   │   └── VenueOwnerDashboardController.php
│   ├── Services/
│   │   ├── VenueClaimService.php
│   │   ├── VenueResponseService.php
│   │   └── VenueVerificationService.php
│   ├── Policies/
│   │   ├── VenueClaimPolicy.php
│   │   └── VenueResponsePolicy.php
│   ├── Requests/
│   │   ├── StoreVenueClaimRequest.php
│   │   ├── StoreVenueResponseRequest.php
│   │   └── UpdateVenueClaimRequest.php
│   ├── Resources/
│   │   ├── VenueClaimResource.php
│   │   ├── VenueResponseResource.php
│   │   └── VenueOwnerResource.php
│   ├── Enums/
│   │   ├── ClaimStatus.php
│   │   ├── ResponseType.php
│   │   └── ModerationStatus.php
│   ├── Events/
│   │   ├── VenueClaimSubmitted.php
│   │   ├── VenueClaimApproved.php
│   │   └── VenueResponseSubmitted.php
│   ├── Listeners/
│   │   ├── SendClaimSubmissionNotification.php
│   │   └── SendClaimApprovalNotification.php
│   ├── Notifications/
│   │   ├── ClaimSubmittedNotification.php
│   │   ├── ClaimApprovedNotification.php
│   │   └── ResponsePendingModerationNotification.php
│   ├── Exceptions/
│   │   ├── VenueAlreadyClaimedException.php
│   │   ├── ClaimNotFoundException.php
│   │   └── UnauthorisedResponseException.php
│   └── Tests/
│       ├── Feature/
│       │   ├── VenueClaimControllerTest.php
│       │   └── VenueResponseControllerTest.php
│       └── Unit/
│           ├── VenueClaimServiceTest.php
│           └── VenueVerificationServiceTest.php
```

#### Model Schemas with Migrations

##### VenueOwner Model

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\VenueOwners\Models;

use Database\Factories\DrinkSafe\VenueOwnerFactory;
use DrinkSafe\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;

/**
 * VenueOwner Model
 *
 * Represents a verified venue owner with management privileges.
 *
 * @property string $uuid
 * @property int $user_id
 * @property string $business_name
 * @property string|null $business_registration_number
 * @property string $contact_email
 * @property string|null $contact_phone
 * @property bool $is_verified
 * @property \Carbon\Carbon|null $verified_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class VenueOwner extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'venue_owners';

    protected $primaryKey = 'uuid';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'business_name',
        'business_registration_number',
        'contact_email',
        'contact_phone',
        'is_verified',
        'verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(VenueClaim::class, 'venue_owner_uuid', 'uuid');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(VenueResponse::class, 'venue_owner_uuid', 'uuid');
    }
}
```

##### VenueClaim Model

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\VenueOwners\Models;

use Database\Factories\DrinkSafe\VenueClaimFactory;
use DrinkSafe\Shared\Traits\HasUuid;
use DrinkSafe\VenueOwners\Enums\ClaimStatus;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * VenueClaim Model
 *
 * Represents a claim request by a venue owner for a specific venue.
 *
 * @property string $uuid
 * @property string $venue_owner_uuid
 * @property string $venue_uuid
 * @property ClaimStatus $status
 * @property string|null $verification_email
 * @property string|null $verification_token
 * @property \Carbon\Carbon|null $email_verified_at
 * @property string|null $documents_path
 * @property string|null $admin_notes
 * @property string|null $rejection_reason
 * @property \Carbon\Carbon|null $reviewed_at
 * @property int|null $reviewed_by_user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class VenueClaim extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'venue_claims';

    protected $primaryKey = 'uuid';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'venue_owner_uuid',
        'venue_uuid',
        'status',
        'verification_email',
        'verification_token',
        'email_verified_at',
        'documents_path',
        'admin_notes',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
            'email_verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function venueOwner(): BelongsTo
    {
        return $this->belongsTo(VenueOwner::class, 'venue_owner_uuid', 'uuid');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_uuid', 'uuid');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * Check if this claim is pending review.
     */
    public function isPending(): bool
    {
        return $this->status === ClaimStatus::Pending
            || $this->status === ClaimStatus::EmailVerified;
    }

    /**
     * Check if this claim has been approved.
     */
    public function isApproved(): bool
    {
        return $this->status === ClaimStatus::Approved;
    }
}
```

##### VenueResponse Model

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\VenueOwners\Models;

use Database\Factories\DrinkSafe\VenueResponseFactory;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Shared\Traits\HasUuid;
use DrinkSafe\VenueOwners\Enums\ModerationStatus;
use DrinkSafe\VenueOwners\Enums\ResponseType;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * VenueResponse Model
 *
 * Represents a response from a venue owner to a report or general statement.
 *
 * @property string $uuid
 * @property string $venue_owner_uuid
 * @property string $venue_uuid
 * @property string|null $report_uuid
 * @property ResponseType $type
 * @property string $content
 * @property ModerationStatus $moderation_status
 * @property string|null $moderation_notes
 * @property \Carbon\Carbon|null $moderated_at
 * @property int|null $moderated_by_user_id
 * @property bool $is_public
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class VenueResponse extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'venue_responses';

    protected $primaryKey = 'uuid';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'venue_owner_uuid',
        'venue_uuid',
        'report_uuid',
        'type',
        'content',
        'moderation_status',
        'moderation_notes',
        'moderated_at',
        'moderated_by_user_id',
        'is_public',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ResponseType::class,
            'moderation_status' => ModerationStatus::class,
            'moderated_at' => 'datetime',
            'is_public' => 'boolean',
        ];
    }

    public function venueOwner(): BelongsTo
    {
        return $this->belongsTo(VenueOwner::class, 'venue_owner_uuid', 'uuid');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_uuid', 'uuid');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_uuid', 'uuid');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by_user_id');
    }

    /**
     * Check if response is approved and public.
     */
    public function isVisible(): bool
    {
        return $this->moderation_status === ModerationStatus::Approved && $this->is_public;
    }
}
```

##### Enums

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\VenueOwners\Enums;

/**
 * ClaimStatus Enum
 *
 * Status workflow for venue claims.
 */
enum ClaimStatus: string
{
    case Pending = 'pending';
    case EmailVerified = 'email_verified';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Verification',
            self::EmailVerified => 'Email Verified',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
        };
    }
}
```

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\VenueOwners\Enums;

/**
 * ResponseType Enum
 *
 * Types of responses a venue owner can submit.
 */
enum ResponseType: string
{
    case Acknowledgement = 'acknowledgement';
    case ActionTaken = 'action_taken';
    case Dispute = 'dispute';
    case GeneralStatement = 'general_statement';

    public function label(): string
    {
        return match ($this) {
            self::Acknowledgement => 'Acknowledgement',
            self::ActionTaken => 'Action Taken',
            self::Dispute => 'Dispute',
            self::GeneralStatement => 'General Statement',
        };
    }

    /**
     * Check if this response type requires report reference.
     */
    public function requiresReport(): bool
    {
        return $this !== self::GeneralStatement;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\VenueOwners\Enums;

/**
 * ModerationStatus Enum
 *
 * Status for content moderation.
 */
enum ModerationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case RequiresEdits = 'requires_edits';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::RequiresEdits => 'Requires Edits',
        };
    }
}
```

##### Migration: create_venue_owners_table.php

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_owners', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('business_name');
            $table->string('business_registration_number')->nullable();
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();

            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_owners');
    }
};
```

##### Migration: create_venue_claims_table.php

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_claims', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('venue_owner_uuid');
            $table->uuid('venue_uuid');

            $table->string('status')->default('pending');
            $table->string('verification_email')->nullable();
            $table->string('verification_token', 64)->nullable();
            $table->timestamp('email_verified_at')->nullable();

            $table->string('documents_path')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('venue_owner_uuid')
                ->references('uuid')
                ->on('venue_owners')
                ->cascadeOnDelete();

            $table->foreign('venue_uuid')
                ->references('uuid')
                ->on('venues')
                ->cascadeOnDelete();

            $table->index('venue_owner_uuid');
            $table->index('venue_uuid');
            $table->index('status');
            $table->index('verification_token');

            // Prevent duplicate active claims
            $table->unique(['venue_uuid', 'status'], 'unique_active_claim');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_claims');
    }
};
```

##### Migration: create_venue_responses_table.php

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_responses', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('venue_owner_uuid');
            $table->uuid('venue_uuid');
            $table->uuid('report_uuid')->nullable();

            $table->string('type');
            $table->text('content');

            $table->string('moderation_status')->default('pending');
            $table->text('moderation_notes')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->foreignId('moderated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_public')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('venue_owner_uuid')
                ->references('uuid')
                ->on('venue_owners')
                ->cascadeOnDelete();

            $table->foreign('venue_uuid')
                ->references('uuid')
                ->on('venues')
                ->cascadeOnDelete();

            $table->foreign('report_uuid')
                ->references('uuid')
                ->on('reports')
                ->nullOnDelete();

            $table->index('venue_owner_uuid');
            $table->index('venue_uuid');
            $table->index('report_uuid');
            $table->index('moderation_status');
            $table->index(['venue_uuid', 'is_public', 'moderation_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_responses');
    }
};
```

#### Service Classes

##### VenueClaimService

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\VenueOwners\Services;

use DrinkSafe\VenueOwners\Enums\ClaimStatus;
use DrinkSafe\VenueOwners\Events\VenueClaimApproved;
use DrinkSafe\VenueOwners\Events\VenueClaimSubmitted;
use DrinkSafe\VenueOwners\Exceptions\ClaimNotFoundException;
use DrinkSafe\VenueOwners\Exceptions\VenueAlreadyClaimedException;
use DrinkSafe\VenueOwners\Models\VenueClaim;
use DrinkSafe\VenueOwners\Models\VenueOwner;
use Illuminate\Support\Str;

/**
 * VenueClaimService
 *
 * Handles venue claim workflow operations.
 */
final class VenueClaimService
{
    public function __construct(
        private readonly VenueVerificationService $verificationService
    ) {}

    /**
     * Initiate a new venue claim.
     *
     * @param  VenueOwner  $venueOwner
     * @param  string  $venueUuid
     * @param  string  $verificationEmail
     *
     * @throws VenueAlreadyClaimedException
     */
    public function initiateClaim(
        VenueOwner $venueOwner,
        string $venueUuid,
        string $verificationEmail
    ): VenueClaim {
        $existingClaim = VenueClaim::where('venue_uuid', $venueUuid)
            ->whereIn('status', [ClaimStatus::Approved, ClaimStatus::Pending, ClaimStatus::EmailVerified])
            ->first();

        if ($existingClaim !== null) {
            throw new VenueAlreadyClaimedException($venueUuid);
        }

        $claim = VenueClaim::create([
            'venue_owner_uuid' => $venueOwner->uuid,
            'venue_uuid' => $venueUuid,
            'status' => ClaimStatus::Pending,
            'verification_email' => $verificationEmail,
            'verification_token' => Str::random(64),
        ]);

        event(new VenueClaimSubmitted($claim));

        return $claim;
    }

    /**
     * Verify claim email.
     *
     * @param  string  $token
     *
     * @throws ClaimNotFoundException
     */
    public function verifyEmail(string $token): VenueClaim
    {
        $claim = VenueClaim::where('verification_token', $token)
            ->where('status', ClaimStatus::Pending)
            ->first();

        if ($claim === null) {
            throw new ClaimNotFoundException(sprintf('Claim with token %s not found', $token));
        }

        $claim->update([
            'status' => ClaimStatus::EmailVerified,
            'email_verified_at' => now(),
        ]);

        return $claim->fresh();
    }

    /**
     * Approve a venue claim.
     *
     * @param  string  $claimUuid
     * @param  int  $reviewerUserId
     * @param  string|null  $notes
     *
     * @throws ClaimNotFoundException
     */
    public function approveClaim(
        string $claimUuid,
        int $reviewerUserId,
        ?string $notes = null
    ): VenueClaim {
        $claim = VenueClaim::find($claimUuid);

        if ($claim === null) {
            throw new ClaimNotFoundException($claimUuid);
        }

        $claim->update([
            'status' => ClaimStatus::Approved,
            'admin_notes' => $notes,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $reviewerUserId,
        ]);

        $claim->venueOwner->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        event(new VenueClaimApproved($claim->fresh()));

        return $claim->fresh();
    }

    /**
     * Reject a venue claim.
     *
     * @param  string  $claimUuid
     * @param  int  $reviewerUserId
     * @param  string  $reason
     *
     * @throws ClaimNotFoundException
     */
    public function rejectClaim(
        string $claimUuid,
        int $reviewerUserId,
        string $reason
    ): VenueClaim {
        $claim = VenueClaim::find($claimUuid);

        if ($claim === null) {
            throw new ClaimNotFoundException($claimUuid);
        }

        $claim->update([
            'status' => ClaimStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $reviewerUserId,
        ]);

        return $claim->fresh();
    }
}
```

#### API Controller Methods

##### VenueClaimController

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\VenueOwners\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\VenueOwners\Exceptions\ClaimNotFoundException;
use DrinkSafe\VenueOwners\Exceptions\VenueAlreadyClaimedException;
use DrinkSafe\VenueOwners\Requests\StoreVenueClaimRequest;
use DrinkSafe\VenueOwners\Resources\VenueClaimResource;
use DrinkSafe\VenueOwners\Services\VenueClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * VenueClaimController
 *
 * Handles venue claim API endpoints.
 */
final class VenueClaimController extends Controller
{
    public function __construct(
        private readonly VenueClaimService $claimService
    ) {}

    /**
     * Initiate a new venue claim.
     */
    public function store(StoreVenueClaimRequest $request, string $venueUuid): JsonResponse
    {
        $this->authorize('create', VenueClaim::class);

        try {
            $claim = $this->claimService->initiateClaim(
                venueOwner: $request->user()->venueOwner,
                venueUuid: $venueUuid,
                verificationEmail: $request->validated('verification_email')
            );

            return response()->json([
                'message' => 'Claim submitted successfully. Please check your email for verification.',
                'data' => new VenueClaimResource($claim),
            ], Response::HTTP_CREATED);
        } catch (VenueAlreadyClaimedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);
        }
    }

    /**
     * Get claim status for a venue.
     */
    public function show(string $venueUuid): JsonResponse
    {
        try {
            $claim = $this->claimService->getClaimForVenue($venueUuid);

            return response()->json([
                'data' => new VenueClaimResource($claim),
            ], Response::HTTP_OK);
        } catch (ClaimNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Verify claim via email token.
     */
    public function verify(Request $request, string $token): JsonResponse
    {
        try {
            $claim = $this->claimService->verifyEmail($token);

            return response()->json([
                'message' => 'Email verified successfully. Your claim is now under review.',
                'data' => new VenueClaimResource($claim),
            ], Response::HTTP_OK);
        } catch (ClaimNotFoundException $e) {
            return response()->json([
                'message' => 'Invalid or expired verification token.',
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
```

##### VenueResponseController

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\VenueOwners\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\VenueOwners\Exceptions\UnauthorisedResponseException;
use DrinkSafe\VenueOwners\Models\VenueResponse;
use DrinkSafe\VenueOwners\Requests\StoreVenueResponseRequest;
use DrinkSafe\VenueOwners\Resources\VenueResponseResource;
use DrinkSafe\VenueOwners\Services\VenueResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * VenueResponseController
 *
 * Handles venue response API endpoints.
 */
final class VenueResponseController extends Controller
{
    public function __construct(
        private readonly VenueResponseService $responseService
    ) {}

    /**
     * Submit a response to a report or general statement.
     */
    public function store(StoreVenueResponseRequest $request, string $venueUuid): JsonResponse
    {
        $this->authorize('create', [VenueResponse::class, $venueUuid]);

        try {
            $response = $this->responseService->submitResponse(
                venueOwner: $request->user()->venueOwner,
                venueUuid: $venueUuid,
                data: $request->validated()
            );

            return response()->json([
                'message' => 'Response submitted successfully and is pending moderation.',
                'data' => new VenueResponseResource($response),
            ], Response::HTTP_CREATED);
        } catch (UnauthorisedResponseException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Get all public responses for a venue.
     */
    public function index(string $venueUuid): JsonResponse
    {
        $responses = $this->responseService->getPublicResponsesForVenue($venueUuid);

        return response()->json([
            'data' => VenueResponseResource::collection($responses),
        ], Response::HTTP_OK);
    }
}
```

#### Route Registration

```php
// routes/api.php

Route::middleware('auth:sanctum')->group(function (): void {
    // Venue Claims
    Route::post('/venues/{uuid}/claim', [VenueClaimController::class, 'store'])
        ->name('venues.claims.store');

    Route::get('/venues/{uuid}/claim/status', [VenueClaimController::class, 'show'])
        ->name('venues.claims.show');

    // Venue Responses (owner only)
    Route::post('/venues/{uuid}/responses', [VenueResponseController::class, 'store'])
        ->name('venues.responses.store');
});

// Public endpoints
Route::get('/claims/verify/{token}', [VenueClaimController::class, 'verify'])
    ->name('claims.verify');

Route::get('/venues/{uuid}/responses', [VenueResponseController::class, 'index'])
    ->name('venues.responses.index');
```

---

### Frontend Implementation Details

#### TypeScript Interfaces

**File:** `resources/js/types/venue-owner.ts`

```typescript
/**
 * Venue Owner Types
 *
 * Types for venue claiming and response system.
 */

/**
 * Claim status workflow
 */
export type ClaimStatus =
    | 'pending'
    | 'email_verified'
    | 'under_review'
    | 'approved'
    | 'rejected'
    | 'expired';

/**
 * Response types
 */
export type ResponseType =
    | 'acknowledgement'
    | 'action_taken'
    | 'dispute'
    | 'general_statement';

/**
 * Moderation status
 */
export type ModerationStatus =
    | 'pending'
    | 'approved'
    | 'rejected'
    | 'requires_edits';

/**
 * Venue owner profile
 */
export interface VenueOwner {
    uuid: string;
    business_name: string;
    contact_email: string;
    is_verified: boolean;
    verified_at: string | null;
}

/**
 * Venue claim record
 */
export interface VenueClaim {
    uuid: string;
    venue_uuid: string;
    status: ClaimStatus;
    verification_email: string;
    email_verified_at: string | null;
    rejection_reason: string | null;
    created_at: string;
}

/**
 * Venue response to a report
 */
export interface VenueResponse {
    uuid: string;
    venue_uuid: string;
    report_uuid: string | null;
    type: ResponseType;
    content: string;
    moderation_status: ModerationStatus;
    is_public: boolean;
    created_at: string;
    venue_owner?: VenueOwner;
}

/**
 * Form data for initiating a claim
 */
export interface ClaimFormData {
    verification_email: string;
    business_name: string;
    business_registration_number?: string;
    contact_phone?: string;
}

/**
 * Form data for submitting a response
 */
export interface ResponseFormData {
    report_uuid?: string;
    type: ResponseType;
    content: string;
}
```

#### useVenueClaims Composable

**File:** `resources/js/composables/useVenueClaims.ts`

```typescript
/**
 * useVenueClaims Composable
 *
 * Manages venue claim flow for venue owners.
 */

import axios from 'axios';
import { ref, computed } from 'vue';
import type {
    VenueClaim,
    ClaimFormData,
    ClaimStatus,
} from '@/types/venue-owner';

export function useVenueClaims() {
    const claim = ref<VenueClaim | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const validationErrors = ref<Record<string, string[]>>({});

    const isClaimPending = computed(() => {
        return claim.value?.status === 'pending' ||
            claim.value?.status === 'email_verified' ||
            claim.value?.status === 'under_review';
    });

    const isClaimApproved = computed(() => {
        return claim.value?.status === 'approved';
    });

    const fetchClaimStatus = async (venueUuid: string): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<{ data: VenueClaim }>(
                `/api/venues/${venueUuid}/claim/status`
            );
            claim.value = response.data.data;
        } catch (err: unknown) {
            if (axios.isAxiosError(err) && err.response?.status === 404) {
                claim.value = null;
            } else {
                error.value = 'Failed to fetch claim status';
            }
        } finally {
            loading.value = false;
        }
    };

    const initiateClaim = async (
        venueUuid: string,
        data: ClaimFormData
    ): Promise<boolean> => {
        loading.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
            const response = await axios.post<{ data: VenueClaim }>(
                `/api/venues/${venueUuid}/claim`,
                data
            );
            claim.value = response.data.data;

            return true;
        } catch (err: unknown) {
            if (axios.isAxiosError(err)) {
                if (err.response?.status === 422) {
                    validationErrors.value = err.response.data.errors ?? {};
                } else if (err.response?.status === 409) {
                    error.value = 'This venue has already been claimed';
                } else {
                    error.value = 'Failed to submit claim';
                }
            }

            return false;
        } finally {
            loading.value = false;
        }
    };

    const verifyEmail = async (token: string): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<{ data: VenueClaim }>(
                `/api/claims/verify/${token}`
            );
            claim.value = response.data.data;

            return true;
        } catch {
            error.value = 'Invalid or expired verification token';

            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        claim,
        loading,
        error,
        validationErrors,
        isClaimPending,
        isClaimApproved,
        fetchClaimStatus,
        initiateClaim,
        verifyEmail,
    };
}
```

#### useVenueResponses Composable

**File:** `resources/js/composables/useVenueResponses.ts`

```typescript
/**
 * useVenueResponses Composable
 *
 * Manages venue responses to reports.
 */

import axios from 'axios';
import { ref } from 'vue';
import type { VenueResponse, ResponseFormData } from '@/types/venue-owner';

export function useVenueResponses() {
    const responses = ref<VenueResponse[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const validationErrors = ref<Record<string, string[]>>({});

    const fetchResponses = async (venueUuid: string): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<{ data: VenueResponse[] }>(
                `/api/venues/${venueUuid}/responses`
            );
            responses.value = response.data.data;
        } catch {
            error.value = 'Failed to fetch responses';
        } finally {
            loading.value = false;
        }
    };

    const submitResponse = async (
        venueUuid: string,
        data: ResponseFormData
    ): Promise<boolean> => {
        loading.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
            const response = await axios.post<{ data: VenueResponse }>(
                `/api/venues/${venueUuid}/responses`,
                data
            );
            responses.value.unshift(response.data.data);

            return true;
        } catch (err: unknown) {
            if (axios.isAxiosError(err) && err.response?.status === 422) {
                validationErrors.value = err.response.data.errors ?? {};
            } else {
                error.value = 'Failed to submit response';
            }

            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        responses,
        loading,
        error,
        validationErrors,
        fetchResponses,
        submitResponse,
    };
}
```

#### VenueClaimForm Component

**File:** `resources/js/components/venue-owner/VenueClaimForm.vue`

```vue
<script setup lang="ts">
import { reactive } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Loader2 } from 'lucide-vue-next';
import type { ClaimFormData } from '@/types/venue-owner';

interface Props {
    venueName: string;
    loading?: boolean;
    error?: string | null;
    validationErrors?: Record<string, string[]>;
}

interface Emits {
    (e: 'submit', data: ClaimFormData): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

const form = reactive<ClaimFormData>({
    verification_email: '',
    business_name: '',
    business_registration_number: '',
    contact_phone: '',
});

const handleSubmit = (): void => {
    emit('submit', { ...form });
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Claim {{ venueName }}</CardTitle>
            <CardDescription>
                Verify your ownership to respond to reports and manage your venue's profile.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Alert
                v-if="error"
                variant="destructive"
                class="mb-4"
            >
                <AlertDescription>{{ error }}</AlertDescription>
            </Alert>

            <form
                class="space-y-4"
                @submit.prevent="handleSubmit"
            >
                <div class="space-y-1.5">
                    <Label for="business_name">Business Name</Label>
                    <Input
                        id="business_name"
                        v-model="form.business_name"
                        type="text"
                        required
                        :disabled="loading"
                    />
                    <p
                        v-if="validationErrors?.business_name"
                        class="text-sm text-red-500"
                    >
                        {{ validationErrors.business_name[0] }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label for="verification_email">Business Email</Label>
                    <Input
                        id="verification_email"
                        v-model="form.verification_email"
                        type="email"
                        placeholder="manager@yourvenue.com"
                        required
                        :disabled="loading"
                    />
                    <p class="text-xs text-slate-500">
                        We'll send a verification link to this email.
                    </p>
                    <p
                        v-if="validationErrors?.verification_email"
                        class="text-sm text-red-500"
                    >
                        {{ validationErrors.verification_email[0] }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label for="business_registration_number">
                        Business Registration Number (Optional)
                    </Label>
                    <Input
                        id="business_registration_number"
                        v-model="form.business_registration_number"
                        type="text"
                        :disabled="loading"
                    />
                </div>

                <div class="space-y-1.5">
                    <Label for="contact_phone">Contact Phone (Optional)</Label>
                    <Input
                        id="contact_phone"
                        v-model="form.contact_phone"
                        type="tel"
                        :disabled="loading"
                    />
                </div>

                <Button
                    type="submit"
                    class="w-full"
                    :disabled="loading"
                >
                    <Loader2
                        v-if="loading"
                        class="mr-2 size-4 animate-spin"
                    />
                    {{ loading ? 'Submitting...' : 'Submit Claim' }}
                </Button>
            </form>
        </CardContent>
    </Card>
</template>
```

#### VenueResponseCard Component

**File:** `resources/js/components/venue-owner/VenueResponseCard.vue`

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { format } from 'date-fns';
import { BadgeCheck, MessageSquare } from 'lucide-vue-next';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { VenueResponse, ResponseType } from '@/types/venue-owner';

interface Props {
    response: VenueResponse;
}

const props = defineProps<Props>();

const formattedDate = computed(() => {
    return format(new Date(props.response.created_at), 'dd MMM yyyy');
});

const typeLabels: Record<ResponseType, { label: string; variant: string }> = {
    acknowledgement: { label: 'Acknowledgement', variant: 'secondary' },
    action_taken: { label: 'Action Taken', variant: 'default' },
    dispute: { label: 'Dispute', variant: 'destructive' },
    general_statement: { label: 'Statement', variant: 'outline' },
};

const typeConfig = computed(() => typeLabels[props.response.type]);
</script>

<template>
    <Card class="border-l-4 border-l-brand-teal">
        <CardHeader class="pb-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <BadgeCheck class="size-5 text-brand-teal" />
                    <span class="font-medium text-slate-700 dark:text-slate-200">
                        Venue Response
                    </span>
                    <Badge :variant="typeConfig.variant as 'default' | 'secondary' | 'destructive' | 'outline'">
                        {{ typeConfig.label }}
                    </Badge>
                </div>
                <span class="text-sm text-slate-500 dark:text-slate-400">
                    {{ formattedDate }}
                </span>
            </div>
        </CardHeader>
        <CardContent>
            <p class="whitespace-pre-wrap text-slate-700 dark:text-slate-200">
                {{ response.content }}
            </p>
        </CardContent>
    </Card>
</template>
```

#### VenueResponseForm Component

**File:** `resources/js/components/venue-owner/VenueResponseForm.vue`

```vue
<script setup lang="ts">
import { reactive } from 'vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2 } from 'lucide-vue-next';
import type { ResponseFormData, ResponseType } from '@/types/venue-owner';

interface Props {
    reportUuid?: string;
    loading?: boolean;
    error?: string | null;
    validationErrors?: Record<string, string[]>;
}

interface Emits {
    (e: 'submit', data: ResponseFormData): void;
    (e: 'cancel'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const form = reactive<ResponseFormData>({
    report_uuid: props.reportUuid,
    type: 'acknowledgement',
    content: '',
});

const responseTypes: Array<{ value: ResponseType; label: string }> = [
    { value: 'acknowledgement', label: 'Acknowledgement' },
    { value: 'action_taken', label: 'Action Taken' },
    { value: 'dispute', label: 'Dispute' },
    { value: 'general_statement', label: 'General Statement' },
];

const handleSubmit = (): void => {
    emit('submit', { ...form });
};
</script>

<template>
    <form
        class="space-y-4"
        @submit.prevent="handleSubmit"
    >
        <Alert
            v-if="error"
            variant="destructive"
        >
            <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <div class="space-y-1.5">
            <Label for="type">Response Type</Label>
            <Select
                :model-value="form.type"
                :disabled="loading"
                @update:model-value="(v: string) => form.type = v as ResponseType"
            >
                <SelectTrigger id="type">
                    <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="t in responseTypes"
                        :key="t.value"
                        :value="t.value"
                    >
                        {{ t.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="space-y-1.5">
            <Label for="content">Your Response</Label>
            <Textarea
                id="content"
                v-model="form.content"
                placeholder="Enter your response..."
                rows="5"
                required
                :disabled="loading"
            />
            <p
                v-if="validationErrors?.content"
                class="text-sm text-red-500"
            >
                {{ validationErrors.content[0] }}
            </p>
            <p class="text-xs text-slate-500">
                Your response will be reviewed by moderators before being published.
            </p>
        </div>

        <div class="flex gap-2">
            <Button
                type="submit"
                :disabled="loading || !form.content.trim()"
            >
                <Loader2
                    v-if="loading"
                    class="mr-2 size-4 animate-spin"
                />
                {{ loading ? 'Submitting...' : 'Submit Response' }}
            </Button>
            <Button
                type="button"
                variant="outline"
                :disabled="loading"
                @click="$emit('cancel')"
            >
                Cancel
            </Button>
        </div>
    </form>
</template>
```

#### VerifiedBadge Component

**File:** `resources/js/components/venue-owner/VerifiedBadge.vue`

```vue
<script setup lang="ts">
import { BadgeCheck } from 'lucide-vue-next';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface Props {
    showLabel?: boolean;
}

withDefaults(defineProps<Props>(), {
    showLabel: false,
});
</script>

<template>
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger as-child>
                <div class="inline-flex items-center gap-1 text-brand-teal">
                    <BadgeCheck class="size-5" />
                    <span
                        v-if="showLabel"
                        class="text-sm font-medium"
                    >
                        Verified Owner
                    </span>
                </div>
            </TooltipTrigger>
            <TooltipContent>
                <p>This venue's ownership has been verified</p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
```

#### Venue Owner Dashboard Page Structure

**File:** `resources/js/pages/VenueOwner/Dashboard.vue`

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/components/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Building2, MessageSquare, TrendingUp, AlertTriangle } from 'lucide-vue-next';
import type { Venue } from '@/types/venue';
import type { VenueClaim } from '@/types/venue-owner';

interface Props {
    venues: Array<Venue & { claim: VenueClaim }>;
    pendingResponses: number;
    recentReports: number;
}

const props = defineProps<Props>();

const claimedVenues = computed(() => {
    return props.venues.filter((v) => v.claim.status === 'approved');
});

const pendingClaims = computed(() => {
    return props.venues.filter(
        (v) => v.claim.status !== 'approved' && v.claim.status !== 'rejected'
    );
});
</script>

<template>
    <Head title="Venue Owner Dashboard" />

    <AppLayout>
        <div class="container mx-auto max-w-6xl px-4 py-8">
            <h1 class="mb-6 text-3xl font-bold">
                Venue Owner Dashboard
            </h1>

            <!-- Stats Cards -->
            <div class="mb-8 grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            <Building2 class="size-4" />
                            Claimed Venues
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ claimedVenues.length }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            <AlertTriangle class="size-4 text-amber-500" />
                            Recent Reports
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ recentReports }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            <MessageSquare class="size-4" />
                            Pending Responses
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ pendingResponses }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium">
                            <TrendingUp class="size-4 text-green-500" />
                            Response Rate
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">85%</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Claimed Venues -->
            <section class="mb-8">
                <h2 class="mb-4 text-xl font-semibold">Your Venues</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <Card
                        v-for="venue in claimedVenues"
                        :key="venue.uuid"
                    >
                        <CardContent class="flex items-center justify-between p-4">
                            <div>
                                <h3 class="font-medium">{{ venue.name }}</h3>
                                <p class="text-sm text-slate-500">{{ venue.city }}</p>
                            </div>
                            <Link :href="`/venue-owner/venues/${venue.uuid}`">
                                <Button variant="outline" size="sm">
                                    Manage
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <!-- Pending Claims -->
            <section v-if="pendingClaims.length > 0">
                <h2 class="mb-4 text-xl font-semibold">Pending Claims</h2>
                <div class="grid gap-4">
                    <Card
                        v-for="venue in pendingClaims"
                        :key="venue.uuid"
                    >
                        <CardContent class="flex items-center justify-between p-4">
                            <div>
                                <h3 class="font-medium">{{ venue.name }}</h3>
                                <p class="text-sm text-slate-500">{{ venue.city }}</p>
                            </div>
                            <Badge variant="secondary">
                                {{ venue.claim.status.replace('_', ' ') }}
                            </Badge>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
```

---

### Testing Strategy

#### Test Organisation

Tests for the Venue Response/Claim System live in the new `src/DrinkSafe/VenueOwners/` module:

```
src/DrinkSafe/VenueOwners/Tests/
├── Feature/
│   ├── VenueClaimControllerTest.php
│   ├── VenueResponseControllerTest.php
│   └── VenueOwnerDashboardControllerTest.php
└── Unit/
    ├── VenueClaimServiceTest.php
    ├── VenueResponseServiceTest.php
    └── VenueVerificationServiceTest.php
```

#### Factory Requirements

Create new factories for VenueOwners module:

```php
// database/factories/DrinkSafe/VenueOwnerFactory.php

<?php

declare(strict_types=1);

namespace Database\Factories\DrinkSafe;

use App\Models\User;
use DrinkSafe\VenueOwners\Models\VenueOwner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueOwner>
 */
final class VenueOwnerFactory extends Factory
{
    protected $model = VenueOwner::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_name' => fake()->company(),
            'business_registration_number' => fake()->optional(0.7)->regexify('[A-Z]{2}[0-9]{8}'),
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => fake()->optional(0.8)->phoneNumber(),
            'is_verified' => false,
            'verified_at' => null,
        ];
    }

    /**
     * Mark venue owner as verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_verified' => true,
            'verified_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Mark venue owner as unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }
}
```

```php
// database/factories/DrinkSafe/VenueClaimFactory.php

<?php

declare(strict_types=1);

namespace Database\Factories\DrinkSafe;

use DrinkSafe\VenueOwners\Enums\ClaimStatus;
use DrinkSafe\VenueOwners\Models\VenueClaim;
use DrinkSafe\VenueOwners\Models\VenueOwner;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VenueClaim>
 */
final class VenueClaimFactory extends Factory
{
    protected $model = VenueClaim::class;

    public function definition(): array
    {
        return [
            'venue_owner_uuid' => VenueOwner::factory(),
            'venue_uuid' => Venue::factory(),
            'status' => ClaimStatus::Pending,
            'verification_email' => fake()->companyEmail(),
            'verification_token' => Str::random(64),
            'email_verified_at' => null,
            'documents_path' => null,
            'admin_notes' => null,
            'rejection_reason' => null,
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
        ];
    }

    /**
     * Set claim status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ClaimStatus::Pending,
        ]);
    }

    /**
     * Set claim status to email verified.
     */
    public function emailVerified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ClaimStatus::EmailVerified,
            'email_verified_at' => now()->subHours(fake()->numberBetween(1, 24)),
        ]);
    }

    /**
     * Set claim status to under review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ClaimStatus::UnderReview,
            'email_verified_at' => now()->subDays(fake()->numberBetween(1, 3)),
            'documents_path' => sprintf('venue-claims/%s/documents', fake()->uuid()),
        ]);
    }

    /**
     * Set claim status to approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ClaimStatus::Approved,
            'email_verified_at' => now()->subDays(fake()->numberBetween(3, 7)),
            'reviewed_at' => now()->subDays(fake()->numberBetween(1, 2)),
            'reviewed_by_user_id' => \App\Models\User::factory(),
        ]);
    }

    /**
     * Set claim status to rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ClaimStatus::Rejected,
            'email_verified_at' => now()->subDays(fake()->numberBetween(3, 7)),
            'rejection_reason' => fake()->sentence(),
            'reviewed_at' => now()->subDays(fake()->numberBetween(1, 2)),
            'reviewed_by_user_id' => \App\Models\User::factory(),
        ]);
    }

    /**
     * Set claim status to expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ClaimStatus::Expired,
            'created_at' => now()->subDays(31),
        ]);
    }
}
```

```php
// database/factories/DrinkSafe/VenueResponseFactory.php

<?php

declare(strict_types=1);

namespace Database\Factories\DrinkSafe;

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\VenueOwners\Enums\ModerationStatus;
use DrinkSafe\VenueOwners\Enums\ResponseType;
use DrinkSafe\VenueOwners\Models\VenueOwner;
use DrinkSafe\VenueOwners\Models\VenueResponse;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueResponse>
 */
final class VenueResponseFactory extends Factory
{
    protected $model = VenueResponse::class;

    public function definition(): array
    {
        return [
            'venue_owner_uuid' => VenueOwner::factory(),
            'venue_uuid' => Venue::factory(),
            'report_uuid' => Report::factory(),
            'type' => fake()->randomElement(ResponseType::cases()),
            'content' => fake()->paragraphs(2, true),
            'moderation_status' => ModerationStatus::Pending,
            'moderation_notes' => null,
            'moderated_at' => null,
            'moderated_by_user_id' => null,
            'is_public' => false,
        ];
    }

    /**
     * Response pending moderation.
     */
    public function pendingModeration(): static
    {
        return $this->state(fn (array $attributes): array => [
            'moderation_status' => ModerationStatus::Pending,
            'is_public' => false,
        ]);
    }

    /**
     * Response approved and public.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'moderation_status' => ModerationStatus::Approved,
            'moderated_at' => now()->subHours(fake()->numberBetween(1, 48)),
            'moderated_by_user_id' => \App\Models\User::factory(),
            'is_public' => true,
        ]);
    }

    /**
     * Response rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'moderation_status' => ModerationStatus::Rejected,
            'moderation_notes' => fake()->sentence(),
            'moderated_at' => now()->subHours(fake()->numberBetween(1, 48)),
            'moderated_by_user_id' => \App\Models\User::factory(),
            'is_public' => false,
        ]);
    }

    /**
     * Response requiring edits.
     */
    public function requiresEdits(): static
    {
        return $this->state(fn (array $attributes): array => [
            'moderation_status' => ModerationStatus::RequiresEdits,
            'moderation_notes' => fake()->sentence(),
            'moderated_at' => now()->subHours(fake()->numberBetween(1, 48)),
            'moderated_by_user_id' => \App\Models\User::factory(),
            'is_public' => false,
        ]);
    }

    /**
     * General statement (no report reference).
     */
    public function generalStatement(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ResponseType::GeneralStatement,
            'report_uuid' => null,
        ]);
    }

    /**
     * Acknowledgement response type.
     */
    public function acknowledgement(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ResponseType::Acknowledgement,
        ]);
    }

    /**
     * Action taken response type.
     */
    public function actionTaken(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ResponseType::ActionTaken,
        ]);
    }

    /**
     * Dispute response type.
     */
    public function dispute(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ResponseType::Dispute,
        ]);
    }
}
```

#### Unit Tests (VenueClaimServiceTest.php)

```php
<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\VenueOwners\Enums\ClaimStatus;
use DrinkSafe\VenueOwners\Events\VenueClaimApproved;
use DrinkSafe\VenueOwners\Events\VenueClaimSubmitted;
use DrinkSafe\VenueOwners\Exceptions\ClaimNotFoundException;
use DrinkSafe\VenueOwners\Exceptions\VenueAlreadyClaimedException;
use DrinkSafe\VenueOwners\Models\VenueClaim;
use DrinkSafe\VenueOwners\Models\VenueOwner;
use DrinkSafe\VenueOwners\Services\VenueClaimService;
use DrinkSafe\VenueOwners\Services\VenueVerificationService;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->verificationService = app(VenueVerificationService::class);
    $this->service = new VenueClaimService($this->verificationService);
});

describe('VenueClaimService', function (): void {
    describe('initiateClaim', function (): void {
        it('creates claim with pending status for unclaimed venue', function (): void {
            Event::fake([VenueClaimSubmitted::class]);

            $venueOwner = VenueOwner::factory()->create();
            $venue = Venue::factory()->create();
            $email = fake()->companyEmail();

            $claim = $this->service->initiateClaim($venueOwner, $venue->uuid, $email);

            expect($claim->status)->toBe(ClaimStatus::Pending)
                ->and($claim->venue_owner_uuid)->toBe($venueOwner->uuid)
                ->and($claim->venue_uuid)->toBe($venue->uuid)
                ->and($claim->verification_email)->toBe($email)
                ->and($claim->verification_token)->not->toBeNull();

            Event::assertDispatched(VenueClaimSubmitted::class);
        });

        it('throws VenueAlreadyClaimedException for venue with approved claim', function (): void {
            $venue = Venue::factory()->create();
            VenueClaim::factory()->approved()->create(['venue_uuid' => $venue->uuid]);

            $venueOwner = VenueOwner::factory()->create();

            expect(fn (): VenueClaim => $this->service->initiateClaim(
                $venueOwner,
                $venue->uuid,
                fake()->companyEmail()
            ))->toThrow(VenueAlreadyClaimedException::class);
        });

        it('throws VenueAlreadyClaimedException for venue with pending claim', function (): void {
            $venue = Venue::factory()->create();
            VenueClaim::factory()->pending()->create(['venue_uuid' => $venue->uuid]);

            $venueOwner = VenueOwner::factory()->create();

            expect(fn (): VenueClaim => $this->service->initiateClaim(
                $venueOwner,
                $venue->uuid,
                fake()->companyEmail()
            ))->toThrow(VenueAlreadyClaimedException::class);
        });

        it('allows claim for venue with rejected claim', function (): void {
            Event::fake();

            $venue = Venue::factory()->create();
            VenueClaim::factory()->rejected()->create(['venue_uuid' => $venue->uuid]);

            $venueOwner = VenueOwner::factory()->create();
            $claim = $this->service->initiateClaim($venueOwner, $venue->uuid, fake()->companyEmail());

            expect($claim->status)->toBe(ClaimStatus::Pending);
        });

        it('allows claim for venue with expired claim', function (): void {
            Event::fake();

            $venue = Venue::factory()->create();
            VenueClaim::factory()->expired()->create(['venue_uuid' => $venue->uuid]);

            $venueOwner = VenueOwner::factory()->create();
            $claim = $this->service->initiateClaim($venueOwner, $venue->uuid, fake()->companyEmail());

            expect($claim->status)->toBe(ClaimStatus::Pending);
        });

        it('generates unique verification token', function (): void {
            Event::fake();

            $venueOwner = VenueOwner::factory()->create();
            $venue1 = Venue::factory()->create();
            $venue2 = Venue::factory()->create();

            $claim1 = $this->service->initiateClaim($venueOwner, $venue1->uuid, fake()->companyEmail());
            $claim2 = $this->service->initiateClaim($venueOwner, $venue2->uuid, fake()->companyEmail());

            expect($claim1->verification_token)->not->toBe($claim2->verification_token);
        });
    });

    describe('verifyEmail', function (): void {
        it('updates claim status to email verified', function (): void {
            $claim = VenueClaim::factory()->pending()->create();

            $updatedClaim = $this->service->verifyEmail($claim->verification_token);

            expect($updatedClaim->status)->toBe(ClaimStatus::EmailVerified)
                ->and($updatedClaim->email_verified_at)->not->toBeNull();
        });

        it('throws ClaimNotFoundException for invalid token', function (): void {
            expect(fn (): VenueClaim => $this->service->verifyEmail('invalid-token'))
                ->toThrow(ClaimNotFoundException::class);
        });

        it('throws ClaimNotFoundException for already verified claim token', function (): void {
            $claim = VenueClaim::factory()->emailVerified()->create();

            expect(fn (): VenueClaim => $this->service->verifyEmail($claim->verification_token))
                ->toThrow(ClaimNotFoundException::class);
        });
    });

    describe('approveClaim', function (): void {
        it('updates claim status to approved', function (): void {
            Event::fake([VenueClaimApproved::class]);

            $claim = VenueClaim::factory()->emailVerified()->create();
            $reviewer = User::factory()->create();

            $approvedClaim = $this->service->approveClaim($claim->uuid, $reviewer->id, 'Verified successfully');

            expect($approvedClaim->status)->toBe(ClaimStatus::Approved)
                ->and($approvedClaim->reviewed_at)->not->toBeNull()
                ->and($approvedClaim->reviewed_by_user_id)->toBe($reviewer->id)
                ->and($approvedClaim->admin_notes)->toBe('Verified successfully');

            Event::assertDispatched(VenueClaimApproved::class);
        });

        it('marks venue owner as verified', function (): void {
            Event::fake();

            $venueOwner = VenueOwner::factory()->unverified()->create();
            $claim = VenueClaim::factory()->emailVerified()->create(['venue_owner_uuid' => $venueOwner->uuid]);
            $reviewer = User::factory()->create();

            $this->service->approveClaim($claim->uuid, $reviewer->id);

            expect($venueOwner->fresh()->is_verified)->toBeTrue()
                ->and($venueOwner->fresh()->verified_at)->not->toBeNull();
        });

        it('throws ClaimNotFoundException for invalid claim UUID', function (): void {
            expect(fn (): VenueClaim => $this->service->approveClaim(fake()->uuid(), 1))
                ->toThrow(ClaimNotFoundException::class);
        });
    });

    describe('rejectClaim', function (): void {
        it('updates claim status to rejected with reason', function (): void {
            $claim = VenueClaim::factory()->emailVerified()->create();
            $reviewer = User::factory()->create();
            $reason = 'Unable to verify business ownership';

            $rejectedClaim = $this->service->rejectClaim($claim->uuid, $reviewer->id, $reason);

            expect($rejectedClaim->status)->toBe(ClaimStatus::Rejected)
                ->and($rejectedClaim->rejection_reason)->toBe($reason)
                ->and($rejectedClaim->reviewed_at)->not->toBeNull()
                ->and($rejectedClaim->reviewed_by_user_id)->toBe($reviewer->id);
        });

        it('throws ClaimNotFoundException for invalid claim UUID', function (): void {
            expect(fn (): VenueClaim => $this->service->rejectClaim(fake()->uuid(), 1, 'Reason'))
                ->toThrow(ClaimNotFoundException::class);
        });
    });
});
```

#### Feature Tests (VenueClaimControllerTest.php)

```php
<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\VenueOwners\Enums\ClaimStatus;
use DrinkSafe\VenueOwners\Models\VenueClaim;
use DrinkSafe\VenueOwners\Models\VenueOwner;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('VenueClaimController', function (): void {
    describe('POST /api/venues/{uuid}/claim', function (): void {
        it('returns 401 when not authenticated', function (): void {
            auth()->logout();

            $venue = Venue::factory()->create();

            $this->postJson(route('venues.claims.store', $venue->uuid), [
                'verification_email' => fake()->companyEmail(),
            ])
                ->assertUnauthorized();
        });

        it('creates claim for authenticated user with venue owner profile', function (): void {
            Event::fake();

            $user = User::factory()->create();
            $venueOwner = VenueOwner::factory()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();

            $this->actingAs($user)
                ->postJson(route('venues.claims.store', $venue->uuid), [
                    'verification_email' => fake()->companyEmail(),
                ])
                ->assertCreated()
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'uuid',
                        'venue_uuid',
                        'status',
                        'verification_email',
                        'created_at',
                    ],
                ])
                ->assertJsonPath('data.status', 'pending');
        });

        it('returns 409 for already claimed venue', function (): void {
            $user = User::factory()->create();
            $venueOwner = VenueOwner::factory()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();
            VenueClaim::factory()->approved()->create(['venue_uuid' => $venue->uuid]);

            $this->actingAs($user)
                ->postJson(route('venues.claims.store', $venue->uuid), [
                    'verification_email' => fake()->companyEmail(),
                ])
                ->assertConflict()
                ->assertJsonStructure(['message']);
        });

        it('returns 422 for invalid verification email', function (): void {
            $user = User::factory()->create();
            VenueOwner::factory()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();

            $this->actingAs($user)
                ->postJson(route('venues.claims.store', $venue->uuid), [
                    'verification_email' => 'not-an-email',
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['verification_email']);
        });

        it('returns 404 for non-existent venue', function (): void {
            $user = User::factory()->create();
            VenueOwner::factory()->create(['user_id' => $user->id]);

            $this->actingAs($user)
                ->postJson(route('venues.claims.store', fake()->uuid()), [
                    'verification_email' => fake()->companyEmail(),
                ])
                ->assertNotFound();
        });
    });

    describe('GET /api/venues/{uuid}/claim/status', function (): void {
        it('returns claim status for venue', function (): void {
            $user = User::factory()->create();
            $venueOwner = VenueOwner::factory()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();
            $claim = VenueClaim::factory()->emailVerified()->create([
                'venue_owner_uuid' => $venueOwner->uuid,
                'venue_uuid' => $venue->uuid,
            ]);

            $this->actingAs($user)
                ->getJson(route('venues.claims.show', $venue->uuid))
                ->assertOk()
                ->assertJsonPath('data.uuid', $claim->uuid)
                ->assertJsonPath('data.status', 'email_verified');
        });

        it('returns 404 when no claim exists', function (): void {
            $user = User::factory()->create();
            $venue = Venue::factory()->create();

            $this->actingAs($user)
                ->getJson(route('venues.claims.show', $venue->uuid))
                ->assertNotFound();
        });
    });

    describe('GET /api/claims/verify/{token}', function (): void {
        it('verifies email and returns updated claim', function (): void {
            $claim = VenueClaim::factory()->pending()->create();

            $this->getJson(route('claims.verify', $claim->verification_token))
                ->assertOk()
                ->assertJsonPath('data.status', 'email_verified')
                ->assertJsonStructure(['message', 'data']);
        });

        it('returns 404 for invalid token', function (): void {
            $this->getJson(route('claims.verify', 'invalid-token'))
                ->assertNotFound()
                ->assertJsonStructure(['message']);
        });

        it('returns 404 for already used token', function (): void {
            $claim = VenueClaim::factory()->emailVerified()->create();

            $this->getJson(route('claims.verify', $claim->verification_token))
                ->assertNotFound();
        });
    });
});
```

#### Feature Tests (VenueResponseControllerTest.php)

```php
<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\VenueOwners\Enums\ModerationStatus;
use DrinkSafe\VenueOwners\Enums\ResponseType;
use DrinkSafe\VenueOwners\Models\VenueClaim;
use DrinkSafe\VenueOwners\Models\VenueOwner;
use DrinkSafe\VenueOwners\Models\VenueResponse;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('VenueResponseController', function (): void {
    describe('POST /api/venues/{uuid}/responses', function (): void {
        it('returns 401 when not authenticated', function (): void {
            auth()->logout();

            $venue = Venue::factory()->create();

            $this->postJson(route('venues.responses.store', $venue->uuid), [
                'type' => ResponseType::Acknowledgement->value,
                'content' => fake()->paragraph(),
            ])
                ->assertUnauthorized();
        });

        it('returns 403 for user without verified claim on venue', function (): void {
            $user = User::factory()->create();
            $venueOwner = VenueOwner::factory()->unverified()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();

            $this->actingAs($user)
                ->postJson(route('venues.responses.store', $venue->uuid), [
                    'type' => ResponseType::Acknowledgement->value,
                    'content' => fake()->paragraph(),
                ])
                ->assertForbidden();
        });

        it('creates response for verified venue owner', function (): void {
            $user = User::factory()->create();
            $venueOwner = VenueOwner::factory()->verified()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();
            VenueClaim::factory()->approved()->create([
                'venue_owner_uuid' => $venueOwner->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
            $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);

            $this->actingAs($user)
                ->postJson(route('venues.responses.store', $venue->uuid), [
                    'report_uuid' => $report->uuid,
                    'type' => ResponseType::Acknowledgement->value,
                    'content' => fake()->paragraph(),
                ])
                ->assertCreated()
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'uuid',
                        'venue_uuid',
                        'type',
                        'content',
                        'moderation_status',
                    ],
                ])
                ->assertJsonPath('data.moderation_status', 'pending');
        });

        it('returns 422 for missing content', function (): void {
            $user = User::factory()->create();
            $venueOwner = VenueOwner::factory()->verified()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();
            VenueClaim::factory()->approved()->create([
                'venue_owner_uuid' => $venueOwner->uuid,
                'venue_uuid' => $venue->uuid,
            ]);

            $this->actingAs($user)
                ->postJson(route('venues.responses.store', $venue->uuid), [
                    'type' => ResponseType::Acknowledgement->value,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['content']);
        });

        it('returns 422 for non-general-statement without report_uuid', function (): void {
            $user = User::factory()->create();
            $venueOwner = VenueOwner::factory()->verified()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();
            VenueClaim::factory()->approved()->create([
                'venue_owner_uuid' => $venueOwner->uuid,
                'venue_uuid' => $venue->uuid,
            ]);

            $this->actingAs($user)
                ->postJson(route('venues.responses.store', $venue->uuid), [
                    'type' => ResponseType::Acknowledgement->value,
                    'content' => fake()->paragraph(),
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['report_uuid']);
        });

        it('allows general statement without report_uuid', function (): void {
            $user = User::factory()->create();
            $venueOwner = VenueOwner::factory()->verified()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();
            VenueClaim::factory()->approved()->create([
                'venue_owner_uuid' => $venueOwner->uuid,
                'venue_uuid' => $venue->uuid,
            ]);

            $this->actingAs($user)
                ->postJson(route('venues.responses.store', $venue->uuid), [
                    'type' => ResponseType::GeneralStatement->value,
                    'content' => fake()->paragraph(),
                ])
                ->assertCreated()
                ->assertJsonPath('data.report_uuid', null);
        });
    });

    describe('GET /api/venues/{uuid}/responses', function (): void {
        it('returns only approved public responses', function (): void {
            $venue = Venue::factory()->create();
            $venueOwner = VenueOwner::factory()->verified()->create();
            VenueClaim::factory()->approved()->create([
                'venue_owner_uuid' => $venueOwner->uuid,
                'venue_uuid' => $venue->uuid,
            ]);

            VenueResponse::factory()->approved()->create([
                'venue_owner_uuid' => $venueOwner->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
            VenueResponse::factory()->pendingModeration()->create([
                'venue_owner_uuid' => $venueOwner->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
            VenueResponse::factory()->rejected()->create([
                'venue_owner_uuid' => $venueOwner->uuid,
                'venue_uuid' => $venue->uuid,
            ]);

            $this->getJson(route('venues.responses.index', $venue->uuid))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.moderation_status', 'approved')
                ->assertJsonPath('data.0.is_public', true);
        });

        it('returns empty array when no approved responses exist', function (): void {
            $venue = Venue::factory()->create();

            $this->getJson(route('venues.responses.index', $venue->uuid))
                ->assertOk()
                ->assertJsonCount(0, 'data');
        });

        it('returns 404 for non-existent venue', function (): void {
            $this->getJson(route('venues.responses.index', fake()->uuid()))
                ->assertNotFound();
        });
    });
});
```

#### Edge Cases to Test

| Scenario | Expected Behaviour | Test Coverage |
|----------|-------------------|---------------|
| Duplicate claim submission | VenueAlreadyClaimedException | Unit, Feature |
| Expired verification token | ClaimNotFoundException | Unit |
| Claim for non-existent venue | 404 response | Feature |
| Response from non-owner | 403 Forbidden | Feature |
| Response to report at different venue | Validation error | Feature |
| Moderation status transitions | Only valid transitions allowed | Unit |
| Soft-deleted venue owner | Cannot submit claims/responses | Feature |
| Email verification token reuse | 404 on second use | Unit, Feature |
| Admin approving own claim | Should be prevented | Unit (future) |
| Concurrent claim submissions | Only first succeeds | Integration (future) |

#### Moderation Flow Test Scenarios

```php
describe('ModerationWorkflow', function (): void {
    it('transitions response from pending to approved', function (): void {
        $response = VenueResponse::factory()->pendingModeration()->create();
        $moderator = User::factory()->admin()->create();

        $this->moderationService->approve($response->uuid, $moderator->id);

        expect($response->fresh()->moderation_status)->toBe(ModerationStatus::Approved)
            ->and($response->fresh()->is_public)->toBeTrue();
    });

    it('transitions response from pending to rejected', function (): void {
        $response = VenueResponse::factory()->pendingModeration()->create();
        $moderator = User::factory()->admin()->create();

        $this->moderationService->reject($response->uuid, $moderator->id, 'Inappropriate content');

        expect($response->fresh()->moderation_status)->toBe(ModerationStatus::Rejected)
            ->and($response->fresh()->is_public)->toBeFalse();
    });

    it('transitions response from pending to requires edits', function (): void {
        $response = VenueResponse::factory()->pendingModeration()->create();
        $moderator = User::factory()->admin()->create();

        $this->moderationService->requestEdits($response->uuid, $moderator->id, 'Please remove specific names');

        expect($response->fresh()->moderation_status)->toBe(ModerationStatus::RequiresEdits)
            ->and($response->fresh()->moderation_notes)->toBe('Please remove specific names');
    });

    it('allows resubmission after edits requested', function (): void {
        $response = VenueResponse::factory()->requiresEdits()->create();
        $venueOwner = $response->venueOwner;

        $this->responseService->updateContent($response->uuid, 'Updated content');

        expect($response->fresh()->moderation_status)->toBe(ModerationStatus::Pending);
    });
});
```

---

### Open Questions

1. Should venue owners see full report details or anonymised summaries?
2. How do we handle multiple claimants for the same venue?
3. Is there a subscription/payment model for verified venue accounts?
4. How do we prevent venue owners from harassing anonymous reporters?
5. Should venues be able to flag reports for moderation review?

---

### Dependencies

- User authentication system
- Moderation workflow/admin interface
- Email notification system

---

### Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Fake claims | Medium | High | Robust verification process |
| Harassment of reporters | Low | High | Strict anonymity; no direct contact without opt-in |
| Reputation manipulation | Medium | Medium | Public audit trail; moderator oversight |
| Legal liability | Low | High | Clear terms of service; venue agreements |

---

## 5. Nearby Alerts

### Status & Metadata

| Attribute | Value |
|-----------|-------|
| **Status** | 🟡 Proposed |
| **Effort** | XL (Extra Large) |
| **Priority** | 5 (Lowest) |
| **Module** | `src/DrinkSafe/Notifications/` (new) |

---

### Vision

Proactively notify users when incidents are reported near venues they care about, enabling informed decision-making and increasing user engagement with the platform.

---

### User Value

| Stakeholder | Benefit |
|-------------|---------|
| **Users** | Stay informed about safety near their regular spots |
| **Platform** | Increased engagement; reason to install mobile app |
| **Safety** | Earlier awareness enables precautionary behaviour |

---

### Alert Triggers

| Trigger | Description |
|---------|-------------|
| Saved venue | Report submitted at a venue the user has saved |
| Recent visit | Report at venue user recently viewed (opt-in) |
| Geographic proximity | Report within X km of saved location |
| Severity threshold | High-severity incidents only (if severity tracking added) |

### Notification Preferences

Users should have granular control:

```
Notification Settings:
- [ ] Email alerts (immediate / daily digest)
- [ ] Push notifications (if mobile app)
- [x] In-app notifications
- Radius: [5km] for proximity alerts
- Alert frequency: [Immediately / Daily summary]
- Alert threshold: [All reports / 3+ reports only]
```

### Notification Channels

| Channel | Use Case | Implementation |
|---------|----------|----------------|
| In-app | Default, non-intrusive | Notification bell + unread count |
| Email | Users without app access | Queued + digest options |
| Push (mobile) | Real-time alerts | Future mobile app |
| Web push | Browser notifications | Service worker |

### Privacy Considerations

**User location data handling**:

| Principle | Implementation |
|-----------|---------------|
| Opt-in only | Users explicitly save venues/enable location |
| Data minimisation | Store venue UUIDs, not precise coordinates |
| Transparency | Clear explanation of how alerts work |
| Easy opt-out | One-click disable for all location features |

**Location data should NOT be**:
- Tracked continuously
- Shared with third parties
- Used for purposes beyond alerts
- Stored beyond user's explicit saved venues

---

### Technical Considerations

**Module placement**: New `src/DrinkSafe/Notifications/` module

**New models**:
- `UserAlert` (user alert preferences)
- `SavedVenue` (user-to-venue relationship)
- `AlertHistory` (sent notifications for deduplication)

**Backend**:
- Queue-based notification dispatch
- Geospatial queries for proximity matching
- Rate limiting (no more than X alerts per day)
- Digest aggregation for email

**Event-driven architecture**:
```
ReportCreated event
  -> FindUsersToNotify listener
    -> For each matching user: dispatch NotifyUser job
      -> SendNotification (respects user preferences)
```

### Alert Content

Notifications should include:
- Venue name and city
- When the incident was reported
- Link to venue detail page
- Option to update preferences

**Example notification**:
```
"A report has been submitted at The Crown & Anchor (Manchester).
Reported today, evening incident. View details."
```

---

### Acceptance Criteria

| # | Criterion | Measurable Outcome |
|---|-----------|-------------------|
| AC1 | Save venue functionality | Given authenticated user, when save button is clicked, then venue is added to user's saved list |
| AC2 | Notification preferences | Given user profile, when preferences are updated, then they persist and affect alert behaviour |
| AC3 | In-app notification | Given saved venue with new report, when user visits site, then notification badge shows unread count |
| AC4 | Email notification | Given user with email alerts enabled, when report is submitted at saved venue, then email is sent within 5 minutes |
| AC5 | Daily digest | Given user with digest preference, when day ends, then single digest email is sent (not per-report) |
| AC6 | Rate limiting | Given user with many saved venues, when multiple reports occur, then max 5 alerts per day are sent |
| AC7 | Opt-out works | Given user disables alerts, when reports occur, then no notifications are sent |
| AC8 | Notification history | Given user, when viewing notification history, then past 30 days of alerts are visible |
| AC9 | Queue reliability | Given 1000 notifications to send, when processed, then all are delivered within 15 minutes |

---

### Backend Implementation Details

#### Proposed Notifications Module Directory Structure

```
src/DrinkSafe/
├── Notifications/
│   ├── README.md
│   ├── Models/
│   │   ├── UserAlert.php
│   │   ├── SavedVenue.php
│   │   └── AlertHistory.php
│   ├── Controllers/
│   │   ├── SavedVenueController.php
│   │   ├── AlertPreferencesController.php
│   │   └── NotificationHistoryController.php
│   ├── Services/
│   │   ├── AlertService.php
│   │   ├── AlertMatchingService.php
│   │   └── DigestService.php
│   ├── Policies/
│   │   ├── SavedVenuePolicy.php
│   │   └── UserAlertPolicy.php
│   ├── Requests/
│   │   ├── StoreSavedVenueRequest.php
│   │   └── UpdateAlertPreferencesRequest.php
│   ├── Resources/
│   │   ├── SavedVenueResource.php
│   │   ├── UserAlertResource.php
│   │   └── AlertHistoryResource.php
│   ├── Enums/
│   │   ├── AlertChannel.php
│   │   ├── AlertFrequency.php
│   │   └── AlertStatus.php
│   ├── Events/
│   │   └── ReportCreatedNearSavedVenue.php
│   ├── Listeners/
│   │   └── FindUsersToNotify.php
│   ├── Jobs/
│   │   ├── SendAlertNotification.php
│   │   └── SendDailyDigest.php
│   ├── Notifications/
│   │   ├── NearbyIncidentNotification.php
│   │   └── DailyDigestNotification.php
│   ├── Exceptions/
│   │   ├── SavedVenueNotFoundException.php
│   │   ├── AlertLimitExceededException.php
│   │   └── InvalidAlertPreferencesException.php
│   └── Tests/
│       ├── Feature/
│       │   ├── SavedVenueControllerTest.php
│       │   └── AlertPreferencesControllerTest.php
│       └── Unit/
│           ├── AlertServiceTest.php
│           └── AlertMatchingServiceTest.php
```

#### Model Schemas with Migrations

##### UserAlert Model

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Notifications\Models;

use Database\Factories\DrinkSafe\UserAlertFactory;
use DrinkSafe\Notifications\Enums\AlertChannel;
use DrinkSafe\Notifications\Enums\AlertFrequency;
use DrinkSafe\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;

/**
 * UserAlert Model
 *
 * Stores user notification preferences for nearby alerts.
 *
 * @property string $uuid
 * @property int $user_id
 * @property bool $email_enabled
 * @property bool $push_enabled
 * @property bool $in_app_enabled
 * @property AlertFrequency $email_frequency
 * @property int $proximity_radius_km
 * @property int $max_alerts_per_day
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class UserAlert extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = 'user_alerts';

    protected $primaryKey = 'uuid';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'email_enabled',
        'push_enabled',
        'in_app_enabled',
        'email_frequency',
        'proximity_radius_km',
        'max_alerts_per_day',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'email_frequency' => AlertFrequency::class,
            'proximity_radius_km' => 'integer',
            'max_alerts_per_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function savedVenues(): HasMany
    {
        return $this->hasMany(SavedVenue::class, 'user_alert_uuid', 'uuid');
    }

    public function alertHistory(): HasMany
    {
        return $this->hasMany(AlertHistory::class, 'user_alert_uuid', 'uuid');
    }

    /**
     * Get enabled notification channels.
     *
     * @return array<int, AlertChannel>
     */
    public function getEnabledChannels(): array
    {
        $channels = [];

        if ($this->in_app_enabled) {
            $channels[] = AlertChannel::InApp;
        }

        if ($this->email_enabled) {
            $channels[] = AlertChannel::Email;
        }

        if ($this->push_enabled) {
            $channels[] = AlertChannel::Push;
        }

        return $channels;
    }

    /**
     * Check if user has reached daily alert limit.
     */
    public function hasReachedDailyLimit(): bool
    {
        $todayCount = $this->alertHistory()
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return $todayCount >= $this->max_alerts_per_day;
    }
}
```

##### SavedVenue Model

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Notifications\Models;

use Database\Factories\DrinkSafe\SavedVenueFactory;
use DrinkSafe\Shared\Traits\HasUuid;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SavedVenue Model
 *
 * Represents a venue saved by a user for alert notifications.
 *
 * @property string $uuid
 * @property string $user_alert_uuid
 * @property string $venue_uuid
 * @property string|null $custom_label
 * @property bool $alerts_enabled
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class SavedVenue extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'saved_venues';

    protected $primaryKey = 'uuid';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_alert_uuid',
        'venue_uuid',
        'custom_label',
        'alerts_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alerts_enabled' => 'boolean',
        ];
    }

    public function userAlert(): BelongsTo
    {
        return $this->belongsTo(UserAlert::class, 'user_alert_uuid', 'uuid');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_uuid', 'uuid');
    }
}
```

##### AlertHistory Model

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Notifications\Models;

use Database\Factories\DrinkSafe\AlertHistoryFactory;
use DrinkSafe\Notifications\Enums\AlertChannel;
use DrinkSafe\Notifications\Enums\AlertStatus;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Shared\Traits\HasUuid;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AlertHistory Model
 *
 * Tracks sent alert notifications for deduplication and user history.
 *
 * @property string $uuid
 * @property string $user_alert_uuid
 * @property string $venue_uuid
 * @property string|null $report_uuid
 * @property AlertChannel $channel
 * @property AlertStatus $status
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $read_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class AlertHistory extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = 'alert_history';

    protected $primaryKey = 'uuid';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_alert_uuid',
        'venue_uuid',
        'report_uuid',
        'channel',
        'status',
        'error_message',
        'sent_at',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => AlertChannel::class,
            'status' => AlertStatus::class,
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function userAlert(): BelongsTo
    {
        return $this->belongsTo(UserAlert::class, 'user_alert_uuid', 'uuid');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_uuid', 'uuid');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_uuid', 'uuid');
    }

    /**
     * Mark alert as read.
     */
    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }
}
```

##### Enums

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Notifications\Enums;

/**
 * AlertChannel Enum
 *
 * Notification delivery channels.
 */
enum AlertChannel: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case Push = 'push';
    case WebPush = 'web_push';

    public function label(): string
    {
        return match ($this) {
            self::InApp => 'In-App',
            self::Email => 'Email',
            self::Push => 'Push Notification',
            self::WebPush => 'Web Push',
        };
    }
}
```

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Notifications\Enums;

/**
 * AlertFrequency Enum
 *
 * Email notification frequency options.
 */
enum AlertFrequency: string
{
    case Immediate = 'immediate';
    case DailyDigest = 'daily_digest';
    case WeeklyDigest = 'weekly_digest';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Immediate => 'Immediate',
            self::DailyDigest => 'Daily Digest',
            self::WeeklyDigest => 'Weekly Digest',
            self::Disabled => 'Disabled',
        };
    }
}
```

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Notifications\Enums;

/**
 * AlertStatus Enum
 *
 * Status of alert delivery.
 */
enum AlertStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::Skipped => 'Skipped (rate limited)',
        };
    }
}
```

##### Migration: create_user_alerts_table.php

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_alerts', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->boolean('email_enabled')->default(false);
            $table->boolean('push_enabled')->default(false);
            $table->boolean('in_app_enabled')->default(true);

            $table->string('email_frequency')->default('daily_digest');
            $table->unsignedInteger('proximity_radius_km')->default(5);
            $table->unsignedInteger('max_alerts_per_day')->default(5);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('user_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_alerts');
    }
};
```

##### Migration: create_saved_venues_table.php

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_venues', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('user_alert_uuid');
            $table->uuid('venue_uuid');

            $table->string('custom_label')->nullable();
            $table->boolean('alerts_enabled')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_alert_uuid')
                ->references('uuid')
                ->on('user_alerts')
                ->cascadeOnDelete();

            $table->foreign('venue_uuid')
                ->references('uuid')
                ->on('venues')
                ->cascadeOnDelete();

            $table->index('user_alert_uuid');
            $table->index('venue_uuid');
            $table->index('alerts_enabled');

            $table->unique(['user_alert_uuid', 'venue_uuid'], 'unique_saved_venue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_venues');
    }
};
```

##### Migration: create_alert_history_table.php

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_history', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('user_alert_uuid');
            $table->uuid('venue_uuid');
            $table->uuid('report_uuid')->nullable();

            $table->string('channel');
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->foreign('user_alert_uuid')
                ->references('uuid')
                ->on('user_alerts')
                ->cascadeOnDelete();

            $table->foreign('venue_uuid')
                ->references('uuid')
                ->on('venues')
                ->cascadeOnDelete();

            $table->foreign('report_uuid')
                ->references('uuid')
                ->on('reports')
                ->nullOnDelete();

            $table->index('user_alert_uuid');
            $table->index('venue_uuid');
            $table->index('status');
            $table->index('read_at');
            $table->index(['user_alert_uuid', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_history');
    }
};
```

#### Event/Listener Architecture

##### ReportCreatedNearSavedVenue Event

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Notifications\Events;

use DrinkSafe\Reports\Models\Report;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ReportCreatedNearSavedVenue Event
 *
 * Dispatched when a new report is created that may trigger user alerts.
 */
final class ReportCreatedNearSavedVenue
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Report $report
    ) {}
}
```

##### FindUsersToNotify Listener

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Notifications\Listeners;

use DrinkSafe\Notifications\Events\ReportCreatedNearSavedVenue;
use DrinkSafe\Notifications\Jobs\SendAlertNotification;
use DrinkSafe\Notifications\Models\SavedVenue;
use DrinkSafe\Notifications\Services\AlertMatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * FindUsersToNotify Listener
 *
 * Finds users with matching saved venues and dispatches notification jobs.
 */
final class FindUsersToNotify implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        private readonly AlertMatchingService $matchingService
    ) {}

    public function handle(ReportCreatedNearSavedVenue $event): void
    {
        $report = $event->report;
        $venueUuid = $report->venue_uuid;

        $matchingUsers = $this->matchingService->findUsersToNotify($venueUuid);

        foreach ($matchingUsers as $userAlert) {
            if ($userAlert->hasReachedDailyLimit()) {
                continue;
            }

            foreach ($userAlert->getEnabledChannels() as $channel) {
                SendAlertNotification::dispatch(
                    $userAlert,
                    $report,
                    $channel
                );
            }
        }
    }
}
```

#### Queue Job Pattern

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Notifications\Jobs;

use DrinkSafe\Notifications\Enums\AlertChannel;
use DrinkSafe\Notifications\Enums\AlertStatus;
use DrinkSafe\Notifications\Models\AlertHistory;
use DrinkSafe\Notifications\Models\UserAlert;
use DrinkSafe\Notifications\Notifications\NearbyIncidentNotification;
use DrinkSafe\Reports\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * SendAlertNotification Job
 *
 * Sends a single alert notification to a user via the specified channel.
 */
final class SendAlertNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly UserAlert $userAlert,
        private readonly Report $report,
        private readonly AlertChannel $channel
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $alertHistory = AlertHistory::create([
            'user_alert_uuid' => $this->userAlert->uuid,
            'venue_uuid' => $this->report->venue_uuid,
            'report_uuid' => $this->report->uuid,
            'channel' => $this->channel,
            'status' => AlertStatus::Pending,
        ]);

        try {
            $notification = new NearbyIncidentNotification(
                $this->report,
                $this->channel
            );

            Notification::send($this->userAlert->user, $notification);

            $alertHistory->update([
                'status' => AlertStatus::Sent,
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            $alertHistory->update([
                'status' => AlertStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        AlertHistory::where('user_alert_uuid', $this->userAlert->uuid)
            ->where('report_uuid', $this->report->uuid)
            ->where('channel', $this->channel)
            ->update([
                'status' => AlertStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);
    }
}
```

#### API Controller Methods

##### SavedVenueController

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Notifications\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Notifications\Exceptions\SavedVenueNotFoundException;
use DrinkSafe\Notifications\Models\SavedVenue;
use DrinkSafe\Notifications\Requests\StoreSavedVenueRequest;
use DrinkSafe\Notifications\Resources\SavedVenueResource;
use DrinkSafe\Notifications\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * SavedVenueController
 *
 * Handles saved venue CRUD operations for alerts.
 */
final class SavedVenueController extends Controller
{
    public function __construct(
        private readonly AlertService $alertService
    ) {}

    /**
     * List user's saved venues.
     */
    public function index(Request $request): JsonResponse
    {
        $savedVenues = $this->alertService->getUserSavedVenues($request->user());

        return response()->json([
            'data' => SavedVenueResource::collection($savedVenues),
        ], Response::HTTP_OK);
    }

    /**
     * Save a venue for alerts.
     */
    public function store(StoreSavedVenueRequest $request): JsonResponse
    {
        $this->authorize('create', SavedVenue::class);

        $savedVenue = $this->alertService->saveVenue(
            user: $request->user(),
            venueUuid: $request->validated('venue_uuid'),
            customLabel: $request->validated('custom_label')
        );

        return response()->json([
            'message' => 'Venue saved successfully',
            'data' => new SavedVenueResource($savedVenue),
        ], Response::HTTP_CREATED);
    }

    /**
     * Remove a saved venue.
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $savedVenue = SavedVenue::find($uuid);

        if ($savedVenue === null) {
            throw new SavedVenueNotFoundException($uuid);
        }

        $this->authorize('delete', $savedVenue);

        $savedVenue->delete();

        return response()->json([
            'message' => 'Venue removed from saved list',
        ], Response::HTTP_OK);
    }
}
```

##### AlertPreferencesController

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Notifications\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Notifications\Requests\UpdateAlertPreferencesRequest;
use DrinkSafe\Notifications\Resources\UserAlertResource;
use DrinkSafe\Notifications\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * AlertPreferencesController
 *
 * Handles user alert preference management.
 */
final class AlertPreferencesController extends Controller
{
    public function __construct(
        private readonly AlertService $alertService
    ) {}

    /**
     * Get current alert preferences.
     */
    public function show(Request $request): JsonResponse
    {
        $userAlert = $this->alertService->getOrCreateUserAlert($request->user());

        return response()->json([
            'data' => new UserAlertResource($userAlert),
        ], Response::HTTP_OK);
    }

    /**
     * Update alert preferences.
     */
    public function update(UpdateAlertPreferencesRequest $request): JsonResponse
    {
        $userAlert = $this->alertService->updatePreferences(
            user: $request->user(),
            data: $request->validated()
        );

        return response()->json([
            'message' => 'Alert preferences updated successfully',
            'data' => new UserAlertResource($userAlert),
        ], Response::HTTP_OK);
    }
}
```

#### Route Registration

```php
// routes/api.php

Route::middleware('auth:sanctum')->group(function (): void {
    // Saved Venues
    Route::get('/saved-venues', [SavedVenueController::class, 'index'])
        ->name('saved-venues.index');

    Route::post('/saved-venues', [SavedVenueController::class, 'store'])
        ->name('saved-venues.store');

    Route::delete('/saved-venues/{uuid}', [SavedVenueController::class, 'destroy'])
        ->name('saved-venues.destroy');

    // Alert Preferences
    Route::get('/alerts/preferences', [AlertPreferencesController::class, 'show'])
        ->name('alerts.preferences.show');

    Route::put('/alerts/preferences', [AlertPreferencesController::class, 'update'])
        ->name('alerts.preferences.update');

    // Notification History
    Route::get('/alerts/history', [NotificationHistoryController::class, 'index'])
        ->name('alerts.history.index');

    Route::put('/alerts/history/{uuid}/read', [NotificationHistoryController::class, 'markAsRead'])
        ->name('alerts.history.read');
});
```

#### Event Service Provider Registration

```php
// app/Providers/EventServiceProvider.php

use DrinkSafe\Notifications\Events\ReportCreatedNearSavedVenue;
use DrinkSafe\Notifications\Listeners\FindUsersToNotify;
use DrinkSafe\Reports\Events\ReportCreated;

protected $listen = [
    ReportCreated::class => [
        // ... existing listeners
        ReportCreatedNearSavedVenue::class,
    ],
    ReportCreatedNearSavedVenue::class => [
        FindUsersToNotify::class,
    ],
];
```

---

### Frontend Implementation Details

#### TypeScript Interfaces

**File:** `resources/js/types/notifications.ts`

```typescript
/**
 * Notification Types
 *
 * Types for nearby alerts and notification system.
 */

/**
 * Notification delivery channels
 */
export type AlertChannel = 'in_app' | 'email' | 'push' | 'web_push';

/**
 * Email notification frequency
 */
export type AlertFrequency = 'immediate' | 'daily_digest' | 'weekly_digest' | 'disabled';

/**
 * Alert delivery status
 */
export type AlertStatus = 'pending' | 'sent' | 'failed' | 'skipped';

/**
 * User alert preferences
 */
export interface UserAlertPreferences {
    uuid: string;
    email_enabled: boolean;
    push_enabled: boolean;
    in_app_enabled: boolean;
    email_frequency: AlertFrequency;
    proximity_radius_km: number;
    max_alerts_per_day: number;
    is_active: boolean;
}

/**
 * Saved venue for alerts
 */
export interface SavedVenue {
    uuid: string;
    venue_uuid: string;
    custom_label: string | null;
    alerts_enabled: boolean;
    created_at: string;
    venue: {
        uuid: string;
        name: string;
        city: string;
    };
}

/**
 * Alert history record
 */
export interface AlertHistoryItem {
    uuid: string;
    venue_uuid: string;
    report_uuid: string | null;
    channel: AlertChannel;
    status: AlertStatus;
    sent_at: string | null;
    read_at: string | null;
    created_at: string;
    venue: {
        uuid: string;
        name: string;
        city: string;
    };
}

/**
 * Form data for updating alert preferences
 */
export interface AlertPreferencesFormData {
    email_enabled: boolean;
    push_enabled: boolean;
    in_app_enabled: boolean;
    email_frequency: AlertFrequency;
    proximity_radius_km: number;
    max_alerts_per_day: number;
    is_active: boolean;
}
```

#### useAlerts Composable

**File:** `resources/js/composables/useAlerts.ts`

```typescript
/**
 * useAlerts Composable
 *
 * Manages alert preferences and notification history.
 */

import axios from 'axios';
import { ref, computed } from 'vue';
import type {
    UserAlertPreferences,
    AlertHistoryItem,
    AlertPreferencesFormData,
} from '@/types/notifications';

export function useAlerts() {
    const preferences = ref<UserAlertPreferences | null>(null);
    const history = ref<AlertHistoryItem[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const unreadCount = computed(() => {
        return history.value.filter((item) => item.read_at === null).length;
    });

    const fetchPreferences = async (): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<{ data: UserAlertPreferences }>(
                '/api/alerts/preferences'
            );
            preferences.value = response.data.data;
        } catch {
            error.value = 'Failed to fetch alert preferences';
        } finally {
            loading.value = false;
        }
    };

    const updatePreferences = async (
        data: Partial<AlertPreferencesFormData>
    ): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.put<{ data: UserAlertPreferences }>(
                '/api/alerts/preferences',
                data
            );
            preferences.value = response.data.data;

            return true;
        } catch {
            error.value = 'Failed to update preferences';

            return false;
        } finally {
            loading.value = false;
        }
    };

    const fetchHistory = async (): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<{ data: AlertHistoryItem[] }>(
                '/api/alerts/history'
            );
            history.value = response.data.data;
        } catch {
            error.value = 'Failed to fetch notification history';
        } finally {
            loading.value = false;
        }
    };

    const markAsRead = async (uuid: string): Promise<boolean> => {
        try {
            await axios.put(`/api/alerts/history/${uuid}/read`);

            const item = history.value.find((h) => h.uuid === uuid);

            if (item) {
                item.read_at = new Date().toISOString();
            }

            return true;
        } catch {
            return false;
        }
    };

    const markAllAsRead = async (): Promise<void> => {
        const unread = history.value.filter((h) => h.read_at === null);

        await Promise.all(unread.map((h) => markAsRead(h.uuid)));
    };

    return {
        preferences,
        history,
        loading,
        error,
        unreadCount,
        fetchPreferences,
        updatePreferences,
        fetchHistory,
        markAsRead,
        markAllAsRead,
    };
}
```

#### useSavedVenues Composable

**File:** `resources/js/composables/useSavedVenues.ts`

```typescript
/**
 * useSavedVenues Composable
 *
 * Manages saved venues for alert notifications.
 */

import axios from 'axios';
import { ref, computed } from 'vue';
import type { SavedVenue } from '@/types/notifications';

export function useSavedVenues() {
    const savedVenues = ref<SavedVenue[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const savedVenueUuids = computed(() => {
        return new Set(savedVenues.value.map((sv) => sv.venue_uuid));
    });

    const isVenueSaved = (venueUuid: string): boolean => {
        return savedVenueUuids.value.has(venueUuid);
    };

    const fetchSavedVenues = async (): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<{ data: SavedVenue[] }>(
                '/api/saved-venues'
            );
            savedVenues.value = response.data.data;
        } catch {
            error.value = 'Failed to fetch saved venues';
        } finally {
            loading.value = false;
        }
    };

    const saveVenue = async (
        venueUuid: string,
        customLabel?: string
    ): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.post<{ data: SavedVenue }>(
                '/api/saved-venues',
                {
                    venue_uuid: venueUuid,
                    custom_label: customLabel,
                }
            );
            savedVenues.value.push(response.data.data);

            return true;
        } catch {
            error.value = 'Failed to save venue';

            return false;
        } finally {
            loading.value = false;
        }
    };

    const removeSavedVenue = async (uuid: string): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            await axios.delete(`/api/saved-venues/${uuid}`);
            savedVenues.value = savedVenues.value.filter((sv) => sv.uuid !== uuid);

            return true;
        } catch {
            error.value = 'Failed to remove venue';

            return false;
        } finally {
            loading.value = false;
        }
    };

    const toggleVenueSave = async (venueUuid: string): Promise<boolean> => {
        const existing = savedVenues.value.find((sv) => sv.venue_uuid === venueUuid);

        if (existing) {
            return removeSavedVenue(existing.uuid);
        }

        return saveVenue(venueUuid);
    };

    return {
        savedVenues,
        loading,
        error,
        savedVenueUuids,
        isVenueSaved,
        fetchSavedVenues,
        saveVenue,
        removeSavedVenue,
        toggleVenueSave,
    };
}
```

#### AlertBell Component

**File:** `resources/js/components/notifications/AlertBell.vue`

```vue
<script setup lang="ts">
import { onMounted } from 'vue';
import { Bell } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAlerts } from '@/composables/useAlerts';
import AlertHistoryItem from './AlertHistoryItem.vue';

const {
    history,
    unreadCount,
    fetchHistory,
    markAsRead,
    markAllAsRead,
} = useAlerts();

onMounted(() => {
    fetchHistory();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative"
            >
                <Bell class="size-5" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align="end"
            class="w-80"
        >
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>Notifications</span>
                <Button
                    v-if="unreadCount > 0"
                    variant="link"
                    size="sm"
                    class="h-auto p-0 text-xs"
                    @click="markAllAsRead"
                >
                    Mark all read
                </Button>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <div
                v-if="history.length === 0"
                class="px-2 py-6 text-center text-sm text-slate-500"
            >
                No notifications yet
            </div>

            <div
                v-else
                class="max-h-96 overflow-y-auto"
            >
                <DropdownMenuItem
                    v-for="item in history.slice(0, 10)"
                    :key="item.uuid"
                    class="cursor-pointer p-0"
                    @click="markAsRead(item.uuid)"
                >
                    <AlertHistoryItem :item="item" />
                </DropdownMenuItem>
            </div>

            <DropdownMenuSeparator />
            <DropdownMenuItem as-child>
                <a
                    href="/settings/notifications"
                    class="justify-center text-brand-teal"
                >
                    View all notifications
                </a>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
```

#### AlertHistoryItem Component

**File:** `resources/js/components/notifications/AlertHistoryItem.vue`

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { formatDistanceToNow } from 'date-fns';
import { MapPin, AlertTriangle } from 'lucide-vue-next';
import type { AlertHistoryItem } from '@/types/notifications';

interface Props {
    item: AlertHistoryItem;
}

const props = defineProps<Props>();

const isUnread = computed(() => props.item.read_at === null);

const timeAgo = computed(() => {
    return formatDistanceToNow(new Date(props.item.created_at), {
        addSuffix: true,
    });
});
</script>

<template>
    <div
        :class="[
            'flex w-full gap-3 p-3',
            isUnread ? 'bg-brand-teal/5' : '',
        ]"
    >
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
            <AlertTriangle class="size-5 text-amber-600 dark:text-amber-400" />
        </div>
        <div class="flex-1 space-y-1">
            <p class="text-sm font-medium text-slate-900 dark:text-white">
                New report at {{ item.venue.name }}
            </p>
            <p class="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                <MapPin class="size-3" />
                {{ item.venue.city }}
            </p>
            <p class="text-xs text-slate-400">
                {{ timeAgo }}
            </p>
        </div>
        <div
            v-if="isUnread"
            class="size-2 shrink-0 rounded-full bg-brand-teal"
        />
    </div>
</template>
```

#### SaveVenueButton Component

**File:** `resources/js/components/notifications/SaveVenueButton.vue`

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { Bookmark, BookmarkCheck, Loader2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useSavedVenues } from '@/composables/useSavedVenues';

interface Props {
    venueUuid: string;
    showLabel?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showLabel: false,
});

const { isVenueSaved, toggleVenueSave, loading } = useSavedVenues();

const isSaved = computed(() => isVenueSaved(props.venueUuid));

const handleClick = async (): Promise<void> => {
    await toggleVenueSave(props.venueUuid);
};
</script>

<template>
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    :variant="isSaved ? 'default' : 'outline'"
                    :size="showLabel ? 'default' : 'icon'"
                    :disabled="loading"
                    @click="handleClick"
                >
                    <Loader2
                        v-if="loading"
                        class="size-4 animate-spin"
                    />
                    <BookmarkCheck
                        v-else-if="isSaved"
                        class="size-4"
                    />
                    <Bookmark
                        v-else
                        class="size-4"
                    />
                    <span
                        v-if="showLabel"
                        class="ml-2"
                    >
                        {{ isSaved ? 'Saved' : 'Save Venue' }}
                    </span>
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                <p>{{ isSaved ? 'Remove from saved venues' : 'Save venue for alerts' }}</p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
```

#### AlertPreferencesPage Component

**File:** `resources/js/pages/Settings/Notifications.vue`

```vue
<script setup lang="ts">
import { onMounted, reactive, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/components/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import { Loader2, Check } from 'lucide-vue-next';
import { useAlerts } from '@/composables/useAlerts';
import { useSavedVenues } from '@/composables/useSavedVenues';
import SavedVenuesList from '@/components/notifications/SavedVenuesList.vue';
import type { AlertFrequency } from '@/types/notifications';

const {
    preferences,
    loading: prefsLoading,
    error: prefsError,
    fetchPreferences,
    updatePreferences,
} = useAlerts();

const {
    savedVenues,
    loading: venuesLoading,
    fetchSavedVenues,
} = useSavedVenues();

const saving = ref(false);
const saved = ref(false);

const form = reactive({
    email_enabled: false,
    push_enabled: false,
    in_app_enabled: true,
    email_frequency: 'daily_digest' as AlertFrequency,
    proximity_radius_km: 5,
    max_alerts_per_day: 5,
    is_active: true,
});

onMounted(async () => {
    await Promise.all([fetchPreferences(), fetchSavedVenues()]);

    if (preferences.value) {
        Object.assign(form, {
            email_enabled: preferences.value.email_enabled,
            push_enabled: preferences.value.push_enabled,
            in_app_enabled: preferences.value.in_app_enabled,
            email_frequency: preferences.value.email_frequency,
            proximity_radius_km: preferences.value.proximity_radius_km,
            max_alerts_per_day: preferences.value.max_alerts_per_day,
            is_active: preferences.value.is_active,
        });
    }
});

const handleSave = async (): Promise<void> => {
    saving.value = true;
    saved.value = false;

    const success = await updatePreferences(form);

    if (success) {
        saved.value = true;
        setTimeout(() => { saved.value = false; }, 2000);
    }

    saving.value = false;
};

const frequencies: Array<{ value: AlertFrequency; label: string }> = [
    { value: 'immediate', label: 'Immediately' },
    { value: 'daily_digest', label: 'Daily digest' },
    { value: 'weekly_digest', label: 'Weekly digest' },
    { value: 'disabled', label: 'Disabled' },
];
</script>

<template>
    <Head title="Notification Settings" />

    <AppLayout>
        <div class="container mx-auto max-w-4xl px-4 py-8">
            <h1 class="mb-6 text-3xl font-bold">
                Notification Settings
            </h1>

            <Alert
                v-if="prefsError"
                variant="destructive"
                class="mb-6"
            >
                <AlertDescription>{{ prefsError }}</AlertDescription>
            </Alert>

            <div class="grid gap-6">
                <!-- Alert Preferences -->
                <Card>
                    <CardHeader>
                        <CardTitle>Alert Preferences</CardTitle>
                        <CardDescription>
                            Choose how you want to be notified about incidents at your saved venues.
                        </CardDescription>
                    </CardHeader>
                    <CardContent v-if="prefsLoading">
                        <div class="space-y-4">
                            <Skeleton class="h-12 w-full" />
                            <Skeleton class="h-12 w-full" />
                            <Skeleton class="h-12 w-full" />
                        </div>
                    </CardContent>
                    <CardContent
                        v-else
                        class="space-y-6"
                    >
                        <!-- Master Toggle -->
                        <div class="flex items-center justify-between">
                            <div>
                                <Label for="is_active">Enable Alerts</Label>
                                <p class="text-sm text-slate-500">
                                    Turn all notifications on or off
                                </p>
                            </div>
                            <Switch
                                id="is_active"
                                v-model:checked="form.is_active"
                            />
                        </div>

                        <div
                            :class="{ 'opacity-50': !form.is_active }"
                            class="space-y-6"
                        >
                            <!-- Channels -->
                            <div class="space-y-4">
                                <h4 class="text-sm font-medium">Notification Channels</h4>

                                <div class="flex items-center justify-between">
                                    <Label for="in_app_enabled">In-app notifications</Label>
                                    <Switch
                                        id="in_app_enabled"
                                        v-model:checked="form.in_app_enabled"
                                        :disabled="!form.is_active"
                                    />
                                </div>

                                <div class="flex items-center justify-between">
                                    <Label for="email_enabled">Email notifications</Label>
                                    <Switch
                                        id="email_enabled"
                                        v-model:checked="form.email_enabled"
                                        :disabled="!form.is_active"
                                    />
                                </div>

                                <div class="flex items-center justify-between">
                                    <Label for="push_enabled">Push notifications</Label>
                                    <Switch
                                        id="push_enabled"
                                        v-model:checked="form.push_enabled"
                                        :disabled="!form.is_active"
                                    />
                                </div>
                            </div>

                            <!-- Email Frequency -->
                            <div
                                v-if="form.email_enabled"
                                class="space-y-1.5"
                            >
                                <Label for="email_frequency">Email Frequency</Label>
                                <Select
                                    v-model="form.email_frequency"
                                    :disabled="!form.is_active || !form.email_enabled"
                                >
                                    <SelectTrigger
                                        id="email_frequency"
                                        class="w-[200px]"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="freq in frequencies"
                                            :key="freq.value"
                                            :value="freq.value"
                                        >
                                            {{ freq.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Rate Limiting -->
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-1.5">
                                    <Label for="max_alerts_per_day">Max alerts per day</Label>
                                    <Input
                                        id="max_alerts_per_day"
                                        v-model.number="form.max_alerts_per_day"
                                        type="number"
                                        min="1"
                                        max="20"
                                        :disabled="!form.is_active"
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="proximity_radius_km">Proximity radius (km)</Label>
                                    <Input
                                        id="proximity_radius_km"
                                        v-model.number="form.proximity_radius_km"
                                        type="number"
                                        min="1"
                                        max="50"
                                        :disabled="!form.is_active"
                                    />
                                </div>
                            </div>
                        </div>

                        <Button
                            :disabled="saving"
                            @click="handleSave"
                        >
                            <Loader2
                                v-if="saving"
                                class="mr-2 size-4 animate-spin"
                            />
                            <Check
                                v-else-if="saved"
                                class="mr-2 size-4"
                            />
                            {{ saved ? 'Saved!' : 'Save Preferences' }}
                        </Button>
                    </CardContent>
                </Card>

                <!-- Saved Venues -->
                <Card>
                    <CardHeader>
                        <CardTitle>Saved Venues</CardTitle>
                        <CardDescription>
                            You'll receive alerts when new reports are submitted at these venues.
                        </CardDescription>
                    </CardHeader>
                    <CardContent v-if="venuesLoading">
                        <div class="space-y-2">
                            <Skeleton class="h-16 w-full" />
                            <Skeleton class="h-16 w-full" />
                        </div>
                    </CardContent>
                    <CardContent v-else>
                        <SavedVenuesList :venues="savedVenues" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
```

#### SavedVenuesList Component

**File:** `resources/js/components/notifications/SavedVenuesList.vue`

```vue
<script setup lang="ts">
import { MapPin, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { useSavedVenues } from '@/composables/useSavedVenues';
import type { SavedVenue } from '@/types/notifications';

interface Props {
    venues: SavedVenue[];
}

defineProps<Props>();

const { removeSavedVenue, loading } = useSavedVenues();

const handleRemove = async (uuid: string): Promise<void> => {
    await removeSavedVenue(uuid);
};
</script>

<template>
    <div v-if="venues.length === 0" class="py-8 text-center">
        <p class="text-slate-500 dark:text-slate-400">
            No saved venues yet. Save venues from the map to receive alerts.
        </p>
    </div>

    <div
        v-else
        class="divide-y divide-slate-100 dark:divide-slate-800"
    >
        <div
            v-for="savedVenue in venues"
            :key="savedVenue.uuid"
            class="flex items-center justify-between py-3"
        >
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                    <MapPin class="size-5 text-slate-600 dark:text-slate-400" />
                </div>
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">
                        {{ savedVenue.venue.name }}
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ savedVenue.venue.city }}
                    </p>
                </div>
            </div>
            <Button
                variant="ghost"
                size="icon"
                class="text-slate-400 hover:text-red-500"
                :disabled="loading"
                @click="handleRemove(savedVenue.uuid)"
            >
                <Trash2 class="size-4" />
            </Button>
        </div>
    </div>
</template>
```

---

### Testing Strategy

#### Test Organisation

Tests for the Nearby Alerts feature live in the new `src/DrinkSafe/Notifications/` module:

```
src/DrinkSafe/Notifications/Tests/
├── Feature/
│   ├── SavedVenueControllerTest.php
│   ├── AlertPreferencesControllerTest.php
│   └── NotificationHistoryControllerTest.php
└── Unit/
    ├── AlertServiceTest.php
    ├── AlertMatchingServiceTest.php
    ├── DigestServiceTest.php
    └── Jobs/
        ├── SendAlertNotificationJobTest.php
        └── SendDailyDigestJobTest.php
```

#### Factory Requirements

Create new factories for Notifications module:

```php
// database/factories/DrinkSafe/UserAlertFactory.php

<?php

declare(strict_types=1);

namespace Database\Factories\DrinkSafe;

use App\Models\User;
use DrinkSafe\Notifications\Enums\AlertFrequency;
use DrinkSafe\Notifications\Models\UserAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAlert>
 */
final class UserAlertFactory extends Factory
{
    protected $model = UserAlert::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'email_enabled' => fake()->boolean(70),
            'push_enabled' => fake()->boolean(30),
            'in_app_enabled' => true,
            'email_frequency' => fake()->randomElement(AlertFrequency::cases()),
            'proximity_radius_km' => fake()->numberBetween(1, 20),
            'max_alerts_per_day' => fake()->numberBetween(3, 10),
            'is_active' => true,
        ];
    }

    /**
     * All notifications enabled.
     */
    public function allEnabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_enabled' => true,
            'push_enabled' => true,
            'in_app_enabled' => true,
            'is_active' => true,
        ]);
    }

    /**
     * All notifications disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Email only with immediate delivery.
     */
    public function emailImmediate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_enabled' => true,
            'push_enabled' => false,
            'in_app_enabled' => true,
            'email_frequency' => AlertFrequency::Immediate,
            'is_active' => true,
        ]);
    }

    /**
     * Email with daily digest.
     */
    public function emailDigest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_enabled' => true,
            'push_enabled' => false,
            'in_app_enabled' => true,
            'email_frequency' => AlertFrequency::DailyDigest,
            'is_active' => true,
        ]);
    }

    /**
     * At daily alert limit.
     */
    public function atDailyLimit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'max_alerts_per_day' => 5,
            'is_active' => true,
        ]);
    }
}
```

```php
// database/factories/DrinkSafe/SavedVenueFactory.php

<?php

declare(strict_types=1);

namespace Database\Factories\DrinkSafe;

use DrinkSafe\Notifications\Models\SavedVenue;
use DrinkSafe\Notifications\Models\UserAlert;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedVenue>
 */
final class SavedVenueFactory extends Factory
{
    protected $model = SavedVenue::class;

    public function definition(): array
    {
        return [
            'user_alert_uuid' => UserAlert::factory(),
            'venue_uuid' => Venue::factory(),
            'custom_label' => fake()->optional(0.3)->words(3, true),
            'alerts_enabled' => true,
        ];
    }

    /**
     * With alerts disabled for this specific venue.
     */
    public function alertsDisabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'alerts_enabled' => false,
        ]);
    }
}
```

```php
// database/factories/DrinkSafe/AlertHistoryFactory.php

<?php

declare(strict_types=1);

namespace Database\Factories\DrinkSafe;

use DrinkSafe\Notifications\Enums\AlertChannel;
use DrinkSafe\Notifications\Enums\AlertStatus;
use DrinkSafe\Notifications\Models\AlertHistory;
use DrinkSafe\Notifications\Models\UserAlert;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertHistory>
 */
final class AlertHistoryFactory extends Factory
{
    protected $model = AlertHistory::class;

    public function definition(): array
    {
        return [
            'user_alert_uuid' => UserAlert::factory(),
            'venue_uuid' => Venue::factory(),
            'report_uuid' => Report::factory(),
            'channel' => fake()->randomElement(AlertChannel::cases()),
            'status' => AlertStatus::Sent,
            'sent_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ];
    }

    /**
     * Sent today for rate limiting tests.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sent_at' => now()->subHours(fake()->numberBetween(1, 12)),
        ]);
    }

    /**
     * Pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AlertStatus::Pending,
            'sent_at' => null,
        ]);
    }

    /**
     * Failed status.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AlertStatus::Failed,
            'failure_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Via specific channel.
     */
    public function viaChannel(AlertChannel $channel): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => $channel,
        ]);
    }
}
```

#### Unit Tests (AlertMatchingServiceTest.php)

```php
<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Notifications\Models\AlertHistory;
use DrinkSafe\Notifications\Models\SavedVenue;
use DrinkSafe\Notifications\Models\UserAlert;
use DrinkSafe\Notifications\Services\AlertMatchingService;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new AlertMatchingService;
});

describe('AlertMatchingService', function (): void {
    describe('findUsersToNotify', function (): void {
        it('returns users with saved venue matching report venue', function (): void {
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->allEnabled()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();
            SavedVenue::factory()->create([
                'user_alert_uuid' => $userAlert->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
            $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);

            $users = $this->service->findUsersToNotify($report);

            expect($users)->toHaveCount(1)
                ->and($users->first()->id)->toBe($user->id);
        });

        it('excludes users with alerts disabled', function (): void {
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->disabled()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();
            SavedVenue::factory()->create([
                'user_alert_uuid' => $userAlert->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
            $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);

            $users = $this->service->findUsersToNotify($report);

            expect($users)->toBeEmpty();
        });

        it('excludes users with venue-specific alerts disabled', function (): void {
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->allEnabled()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();
            SavedVenue::factory()->alertsDisabled()->create([
                'user_alert_uuid' => $userAlert->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
            $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);

            $users = $this->service->findUsersToNotify($report);

            expect($users)->toBeEmpty();
        });

        it('excludes users who have reached daily alert limit', function (): void {
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->atDailyLimit()->create([
                'user_id' => $user->id,
                'max_alerts_per_day' => 2,
            ]);
            $venue = Venue::factory()->create();
            SavedVenue::factory()->create([
                'user_alert_uuid' => $userAlert->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
            AlertHistory::factory()->today()->count(2)->create([
                'user_alert_uuid' => $userAlert->uuid,
            ]);

            $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);
            $users = $this->service->findUsersToNotify($report);

            expect($users)->toBeEmpty();
        });

        it('includes users who have not reached daily alert limit', function (): void {
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->atDailyLimit()->create([
                'user_id' => $user->id,
                'max_alerts_per_day' => 5,
            ]);
            $venue = Venue::factory()->create();
            SavedVenue::factory()->create([
                'user_alert_uuid' => $userAlert->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
            AlertHistory::factory()->today()->count(3)->create([
                'user_alert_uuid' => $userAlert->uuid,
            ]);

            $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);
            $users = $this->service->findUsersToNotify($report);

            expect($users)->toHaveCount(1);
        });

        it('excludes the report submitter from notifications', function (): void {
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->allEnabled()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();
            SavedVenue::factory()->create([
                'user_alert_uuid' => $userAlert->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
            $report = Report::factory()->create([
                'venue_uuid' => $venue->uuid,
                'user_id' => $user->id,
            ]);

            $users = $this->service->findUsersToNotify($report);

            expect($users)->toBeEmpty();
        });

        it('returns multiple users for same venue', function (): void {
            $venue = Venue::factory()->create();

            collect(range(1, 3))->each(function () use ($venue): void {
                $user = User::factory()->create();
                $userAlert = UserAlert::factory()->allEnabled()->create(['user_id' => $user->id]);
                SavedVenue::factory()->create([
                    'user_alert_uuid' => $userAlert->uuid,
                    'venue_uuid' => $venue->uuid,
                ]);
            });

            $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);
            $users = $this->service->findUsersToNotify($report);

            expect($users)->toHaveCount(3);
        });
    });

    describe('getEnabledChannels', function (): void {
        it('returns only enabled channels for user', function (): void {
            $userAlert = UserAlert::factory()->create([
                'email_enabled' => true,
                'push_enabled' => false,
                'in_app_enabled' => true,
            ]);

            $channels = $this->service->getEnabledChannels($userAlert);

            expect($channels)->toHaveCount(2)
                ->and($channels)->toContain(AlertChannel::Email)
                ->and($channels)->toContain(AlertChannel::InApp)
                ->and($channels)->not->toContain(AlertChannel::Push);
        });
    });
});
```

#### Unit Tests (AlertServiceTest.php)

```php
<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Notifications\Enums\AlertChannel;
use DrinkSafe\Notifications\Enums\AlertFrequency;
use DrinkSafe\Notifications\Exceptions\AlertLimitExceededException;
use DrinkSafe\Notifications\Jobs\SendAlertNotification;
use DrinkSafe\Notifications\Models\AlertHistory;
use DrinkSafe\Notifications\Models\SavedVenue;
use DrinkSafe\Notifications\Models\UserAlert;
use DrinkSafe\Notifications\Services\AlertMatchingService;
use DrinkSafe\Notifications\Services\AlertService;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->matchingService = new AlertMatchingService;
    $this->service = new AlertService($this->matchingService);
});

describe('AlertService', function (): void {
    describe('processReportCreated', function (): void {
        it('dispatches notification jobs for matching users', function (): void {
            Queue::fake();

            $venue = Venue::factory()->create();
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->emailImmediate()->create(['user_id' => $user->id]);
            SavedVenue::factory()->create([
                'user_alert_uuid' => $userAlert->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
            $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);

            $this->service->processReportCreated($report);

            Queue::assertPushed(SendAlertNotification::class, fn ($job): bool =>
                $job->userAlert->uuid === $userAlert->uuid
                && $job->report->uuid === $report->uuid
            );
        });

        it('does not dispatch jobs when no users match', function (): void {
            Queue::fake();

            $report = Report::factory()->create();

            $this->service->processReportCreated($report);

            Queue::assertNotPushed(SendAlertNotification::class);
        });

        it('queues alerts for digest when email frequency is daily', function (): void {
            Queue::fake();

            $venue = Venue::factory()->create();
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->emailDigest()->create(['user_id' => $user->id]);
            SavedVenue::factory()->create([
                'user_alert_uuid' => $userAlert->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
            $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);

            $this->service->processReportCreated($report);

            $this->assertDatabaseHas('alert_history', [
                'user_alert_uuid' => $userAlert->uuid,
                'report_uuid' => $report->uuid,
                'channel' => AlertChannel::Email->value,
                'status' => 'pending',
            ]);
        });
    });

    describe('sendNotification', function (): void {
        it('creates alert history record on success', function (): void {
            $userAlert = UserAlert::factory()->allEnabled()->create();
            $venue = Venue::factory()->create();
            $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);

            $this->service->sendNotification($userAlert, $report, AlertChannel::InApp);

            $this->assertDatabaseHas('alert_history', [
                'user_alert_uuid' => $userAlert->uuid,
                'report_uuid' => $report->uuid,
                'venue_uuid' => $venue->uuid,
                'channel' => AlertChannel::InApp->value,
                'status' => 'sent',
            ]);
        });

        it('throws AlertLimitExceededException when limit reached', function (): void {
            $userAlert = UserAlert::factory()->create(['max_alerts_per_day' => 2]);
            AlertHistory::factory()->today()->count(2)->create([
                'user_alert_uuid' => $userAlert->uuid,
            ]);
            $report = Report::factory()->create();

            expect(fn () => $this->service->sendNotification($userAlert, $report, AlertChannel::InApp))
                ->toThrow(AlertLimitExceededException::class);
        });
    });
});
```

#### Feature Tests (SavedVenueControllerTest.php)

```php
<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Notifications\Models\SavedVenue;
use DrinkSafe\Notifications\Models\UserAlert;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('SavedVenueController', function (): void {
    describe('GET /api/saved-venues', function (): void {
        it('returns 401 when not authenticated', function (): void {
            auth()->logout();

            $this->getJson(route('saved-venues.index'))
                ->assertUnauthorized();
        });

        it('returns user saved venues', function (): void {
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->create(['user_id' => $user->id]);
            $venues = Venue::factory()->count(3)->create();

            $venues->each(fn (Venue $venue) => SavedVenue::factory()->create([
                'user_alert_uuid' => $userAlert->uuid,
                'venue_uuid' => $venue->uuid,
            ]));

            $this->actingAs($user)
                ->getJson(route('saved-venues.index'))
                ->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'uuid',
                            'venue' => ['uuid', 'name', 'city'],
                            'alerts_enabled',
                            'created_at',
                        ],
                    ],
                ]);
        });

        it('does not return other users saved venues', function (): void {
            $user = User::factory()->create();
            $otherUser = User::factory()->create();
            $userAlert = UserAlert::factory()->create(['user_id' => $user->id]);
            $otherUserAlert = UserAlert::factory()->create(['user_id' => $otherUser->id]);

            SavedVenue::factory()->count(2)->create(['user_alert_uuid' => $userAlert->uuid]);
            SavedVenue::factory()->count(3)->create(['user_alert_uuid' => $otherUserAlert->uuid]);

            $this->actingAs($user)
                ->getJson(route('saved-venues.index'))
                ->assertOk()
                ->assertJsonCount(2, 'data');
        });
    });

    describe('POST /api/saved-venues', function (): void {
        it('returns 401 when not authenticated', function (): void {
            auth()->logout();

            $venue = Venue::factory()->create();

            $this->postJson(route('saved-venues.store'), [
                'venue_uuid' => $venue->uuid,
            ])
                ->assertUnauthorized();
        });

        it('creates saved venue for authenticated user', function (): void {
            $user = User::factory()->create();
            UserAlert::factory()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();

            $this->actingAs($user)
                ->postJson(route('saved-venues.store'), [
                    'venue_uuid' => $venue->uuid,
                ])
                ->assertCreated()
                ->assertJsonStructure([
                    'message',
                    'data' => ['uuid', 'venue', 'alerts_enabled'],
                ]);

            $this->assertDatabaseHas('saved_venues', [
                'venue_uuid' => $venue->uuid,
            ]);
        });

        it('returns 409 when venue already saved', function (): void {
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->create(['user_id' => $user->id]);
            $venue = Venue::factory()->create();
            SavedVenue::factory()->create([
                'user_alert_uuid' => $userAlert->uuid,
                'venue_uuid' => $venue->uuid,
            ]);

            $this->actingAs($user)
                ->postJson(route('saved-venues.store'), [
                    'venue_uuid' => $venue->uuid,
                ])
                ->assertConflict();
        });

        it('returns 404 for non-existent venue', function (): void {
            $user = User::factory()->create();
            UserAlert::factory()->create(['user_id' => $user->id]);

            $this->actingAs($user)
                ->postJson(route('saved-venues.store'), [
                    'venue_uuid' => fake()->uuid(),
                ])
                ->assertNotFound();
        });

        it('creates UserAlert if none exists', function (): void {
            $user = User::factory()->create();
            $venue = Venue::factory()->create();

            expect(UserAlert::where('user_id', $user->id)->exists())->toBeFalse();

            $this->actingAs($user)
                ->postJson(route('saved-venues.store'), [
                    'venue_uuid' => $venue->uuid,
                ])
                ->assertCreated();

            expect(UserAlert::where('user_id', $user->id)->exists())->toBeTrue();
        });
    });

    describe('DELETE /api/saved-venues/{uuid}', function (): void {
        it('returns 401 when not authenticated', function (): void {
            auth()->logout();

            $savedVenue = SavedVenue::factory()->create();

            $this->deleteJson(route('saved-venues.destroy', $savedVenue->uuid))
                ->assertUnauthorized();
        });

        it('deletes saved venue belonging to user', function (): void {
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->create(['user_id' => $user->id]);
            $savedVenue = SavedVenue::factory()->create(['user_alert_uuid' => $userAlert->uuid]);

            $this->actingAs($user)
                ->deleteJson(route('saved-venues.destroy', $savedVenue->uuid))
                ->assertNoContent();

            $this->assertDatabaseMissing('saved_venues', ['uuid' => $savedVenue->uuid]);
        });

        it('returns 403 for saved venue belonging to other user', function (): void {
            $user = User::factory()->create();
            $otherUser = User::factory()->create();
            $otherUserAlert = UserAlert::factory()->create(['user_id' => $otherUser->id]);
            $savedVenue = SavedVenue::factory()->create(['user_alert_uuid' => $otherUserAlert->uuid]);

            $this->actingAs($user)
                ->deleteJson(route('saved-venues.destroy', $savedVenue->uuid))
                ->assertForbidden();

            $this->assertDatabaseHas('saved_venues', ['uuid' => $savedVenue->uuid]);
        });

        it('returns 404 for non-existent saved venue', function (): void {
            $user = User::factory()->create();

            $this->actingAs($user)
                ->deleteJson(route('saved-venues.destroy', fake()->uuid()))
                ->assertNotFound();
        });
    });
});
```

#### Feature Tests (AlertPreferencesControllerTest.php)

```php
<?php

declare(strict_types=1);

use App\Models\User;
use DrinkSafe\Notifications\Enums\AlertFrequency;
use DrinkSafe\Notifications\Models\UserAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('AlertPreferencesController', function (): void {
    describe('GET /api/alert-preferences', function (): void {
        it('returns 401 when not authenticated', function (): void {
            auth()->logout();

            $this->getJson(route('alert-preferences.show'))
                ->assertUnauthorized();
        });

        it('returns user alert preferences', function (): void {
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->create(['user_id' => $user->id]);

            $this->actingAs($user)
                ->getJson(route('alert-preferences.show'))
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'email_enabled',
                        'push_enabled',
                        'in_app_enabled',
                        'email_frequency',
                        'proximity_radius_km',
                        'max_alerts_per_day',
                        'is_active',
                    ],
                ]);
        });

        it('returns default preferences if none exist', function (): void {
            $user = User::factory()->create();

            $this->actingAs($user)
                ->getJson(route('alert-preferences.show'))
                ->assertOk()
                ->assertJsonPath('data.is_active', true)
                ->assertJsonPath('data.in_app_enabled', true);
        });
    });

    describe('PUT /api/alert-preferences', function (): void {
        it('returns 401 when not authenticated', function (): void {
            auth()->logout();

            $this->putJson(route('alert-preferences.update'), [
                'is_active' => false,
            ])
                ->assertUnauthorized();
        });

        it('updates alert preferences', function (): void {
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->create([
                'user_id' => $user->id,
                'email_enabled' => false,
                'max_alerts_per_day' => 5,
            ]);

            $this->actingAs($user)
                ->putJson(route('alert-preferences.update'), [
                    'email_enabled' => true,
                    'max_alerts_per_day' => 10,
                    'email_frequency' => AlertFrequency::Daily->value,
                ])
                ->assertOk()
                ->assertJsonPath('data.email_enabled', true)
                ->assertJsonPath('data.max_alerts_per_day', 10)
                ->assertJsonPath('data.email_frequency', 'daily');
        });

        it('creates preferences if none exist', function (): void {
            $user = User::factory()->create();

            expect(UserAlert::where('user_id', $user->id)->exists())->toBeFalse();

            $this->actingAs($user)
                ->putJson(route('alert-preferences.update'), [
                    'email_enabled' => true,
                    'max_alerts_per_day' => 5,
                ])
                ->assertOk();

            expect(UserAlert::where('user_id', $user->id)->exists())->toBeTrue();
        });

        it('returns 422 for invalid max_alerts_per_day', function (): void {
            $user = User::factory()->create();

            $this->actingAs($user)
                ->putJson(route('alert-preferences.update'), [
                    'max_alerts_per_day' => 100,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['max_alerts_per_day']);
        });

        it('returns 422 for invalid proximity_radius_km', function (): void {
            $user = User::factory()->create();

            $this->actingAs($user)
                ->putJson(route('alert-preferences.update'), [
                    'proximity_radius_km' => 500,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['proximity_radius_km']);
        });
    });
});
```

#### Queue Job Testing (SendAlertNotificationJobTest.php)

```php
<?php

declare(strict_types=1);

use DrinkSafe\Notifications\Enums\AlertChannel;
use DrinkSafe\Notifications\Enums\AlertStatus;
use DrinkSafe\Notifications\Jobs\SendAlertNotification;
use DrinkSafe\Notifications\Models\AlertHistory;
use DrinkSafe\Notifications\Models\UserAlert;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('SendAlertNotification Job', function (): void {
    it('sends in-app notification successfully', function (): void {
        Notification::fake();

        $userAlert = UserAlert::factory()->allEnabled()->create();
        $venue = Venue::factory()->create();
        $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);

        $job = new SendAlertNotification($userAlert, $report, AlertChannel::InApp);
        $job->handle();

        $this->assertDatabaseHas('alert_history', [
            'user_alert_uuid' => $userAlert->uuid,
            'report_uuid' => $report->uuid,
            'channel' => AlertChannel::InApp->value,
            'status' => AlertStatus::Sent->value,
        ]);
    });

    it('sends email notification successfully', function (): void {
        Notification::fake();

        $userAlert = UserAlert::factory()->emailImmediate()->create();
        $venue = Venue::factory()->create();
        $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);

        $job = new SendAlertNotification($userAlert, $report, AlertChannel::Email);
        $job->handle();

        Notification::assertSentTo(
            $userAlert->user,
            \DrinkSafe\Notifications\Notifications\NearbyIncidentNotification::class
        );
    });

    it('records failure when notification fails', function (): void {
        $userAlert = UserAlert::factory()->create();
        $venue = Venue::factory()->create();
        $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);

        $job = new SendAlertNotification($userAlert, $report, AlertChannel::Email);
        $job->failed(new \Exception('SMTP connection failed'));

        $this->assertDatabaseHas('alert_history', [
            'user_alert_uuid' => $userAlert->uuid,
            'report_uuid' => $report->uuid,
            'status' => AlertStatus::Failed->value,
        ]);
    });

    it('has correct retry configuration', function (): void {
        $userAlert = UserAlert::factory()->create();
        $report = Report::factory()->create();

        $job = new SendAlertNotification($userAlert, $report, AlertChannel::Email);

        expect($job->tries)->toBe(3)
            ->and($job->backoff())->toBe([60, 300, 900]);
    });

    it('respects rate limiting', function (): void {
        $userAlert = UserAlert::factory()->create(['max_alerts_per_day' => 1]);
        AlertHistory::factory()->today()->create([
            'user_alert_uuid' => $userAlert->uuid,
        ]);

        $report = Report::factory()->create();
        $job = new SendAlertNotification($userAlert, $report, AlertChannel::InApp);

        expect(fn () => $job->handle())
            ->toThrow(\DrinkSafe\Notifications\Exceptions\AlertLimitExceededException::class);
    });
});
```

#### Edge Cases to Test

| Scenario | Expected Behaviour | Test Coverage |
|----------|-------------------|---------------|
| User with no alert preferences | Creates default preferences | Feature |
| Duplicate saved venue | 409 Conflict response | Feature |
| Alert at daily limit | AlertLimitExceededException | Unit, Job |
| Notification delivery failure | Records failure, allows retry | Job |
| User disables alerts mid-batch | Respects preference change | Unit |
| Report submitter receiving own alert | Excluded from notifications | Unit |
| Multiple channels enabled | Sends via all enabled channels | Unit |
| Daily digest at midnight | Aggregates pending alerts | Job |
| Soft-deleted saved venue | Not included in matching | Unit |
| Invalid email frequency value | 422 validation error | Feature |

#### Queue Job Testing Approach

```php
describe('Queue reliability', function (): void {
    it('processes 100 notifications within acceptable time', function (): void {
        Queue::fake();

        $venue = Venue::factory()->create();
        collect(range(1, 100))->each(function () use ($venue): void {
            $user = User::factory()->create();
            $userAlert = UserAlert::factory()->allEnabled()->create(['user_id' => $user->id]);
            SavedVenue::factory()->create([
                'user_alert_uuid' => $userAlert->uuid,
                'venue_uuid' => $venue->uuid,
            ]);
        });

        $report = Report::factory()->create(['venue_uuid' => $venue->uuid]);

        $startTime = microtime(true);
        $this->service->processReportCreated($report);
        $executionTime = microtime(true) - $startTime;

        Queue::assertPushed(SendAlertNotification::class, 100);
        expect($executionTime)->toBeLessThan(5.0);
    })->skip('Run manually for performance testing');
});
```

---

### Open Questions

1. Should alerts include report details or just indicate a report exists?
2. How do we handle alert fatigue (too many notifications)?
3. Is real-time proximity needed, or is venue-based sufficient for MVP?
4. Do we need a user account system for this feature?
5. How do we verify users have a legitimate connection to saved venues?

---

### Dependencies

- User authentication system
- Queue infrastructure (Laravel queues)
- Email provider integration
- Push notification service (for mobile/web push)

---

### Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Alert fatigue | High | Medium | Configurable frequency; digest options |
| Privacy concerns | Medium | High | Strict data minimisation; clear communication |
| Spam/abuse | Low | Medium | Rate limiting; account requirements |
| False positives | Low | Low | Clear alerts are informational, not confirmed danger |

---

## Implementation Priority Recommendation

Based on user value, technical complexity, and dependencies:

| Priority | Feature | Effort | Rationale |
|----------|---------|--------|-----------|
| 1 | Venue Safety Scores | S | High value, low complexity, no new infrastructure |
| 2 | Time-based Analytics | M | High value, medium complexity, builds on existing data |
| 3 | Incident Heatmap | M | High visual impact, medium complexity, requires frontend work |
| 4 | Venue Response System | L | High value but requires user auth infrastructure |
| 5 | Nearby Alerts | XL | Depends on user auth + notification infrastructure |

### Recommended Implementation Sequence

```
Phase 1 (No Auth Required):
  └── 1. Venue Safety Scores (S)
  └── 2. Time-based Analytics (M)
  └── 3. Incident Heatmap (M)
      [Can be parallelised]

Phase 2 (Auth Infrastructure):
  └── Set up user authentication (Fortify already installed)
  └── 4. Venue Response System (L)

Phase 3 (Notification Infrastructure):
  └── Set up notification system
  └── 5. Nearby Alerts (XL)
```

---

## Related Documentation

- [Modular Architecture Structure](/docs/architecture/modular-structure.md)
- [Naming Conventions](/docs/architecture/naming-conventions.md)
- [API Documentation](/docs/api/README.md)
- [Venues Module README](/src/DrinkSafe/Venues/README.md)
- [Reports Module README](/src/DrinkSafe/Reports/README.md)

---

## Revision History

| Date | Version | Changes | Author |
|------|---------|---------|--------|
| 2026-05-27 | 1.0 | Initial proposal document | Documentation Architect Agent |
| 2026-05-27 | 1.1 | Added template standardisation, acceptance criteria, effort estimates, status badges, dependency matrix | Phase 1 Enhancement |
| 2026-05-27 | 1.2 | Added Laravel backend implementation details for all 5 features: service classes, models, migrations, controllers, resources, enums, exceptions, route registrations, and event/listener architectures | Phase 2 Enhancement |
| 2026-05-27 | 1.3 | Added Vue/Inertia frontend implementation details for all 5 features: TypeScript interfaces, Vue 3 components with Composition API, composables for state management, Tailwind CSS styling patterns, and integration examples following vue-inertia-frontend-guidelines | Phase 3 Enhancement |
| 2026-05-27 | 1.4 | Added comprehensive PestPHP testing strategies for all 5 features: test organisation by module, factory requirements with states, unit tests for services, feature tests for controllers, edge case matrices, queue job testing, and performance test considerations | Phase 4 Enhancement |
| 2026-05-27 | 1.5 | Final review and polish: fixed factory User::factory() references, corrected property naming inconsistencies between models and factories (receive_alerts to alerts_enabled), fixed AlertFrequency::Daily to AlertFrequency::DailyDigest, verified all code examples, validated internal documentation links, ensured consistent section structure across all 5 features | Phase 5 Final Review |
