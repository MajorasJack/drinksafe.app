/**
 * useSafetyScore Composable
 *
 * Provides reactive access to venue safety scores with in-memory caching.
 */

import axios from 'axios';
import { readonly, ref } from 'vue';
import type { SafetyScore, SafetyScoreResponse } from '@/types/safety';
import { transformSafetyScoreResponse } from '@/types/safety';

interface CacheEntry {
    score: SafetyScore;
    timestamp: number;
}

const CACHE_TTL_MS = 5 * 60 * 1000;

const scoreCache = new Map<string, CacheEntry>();

function isCacheValid(entry: CacheEntry): boolean {
    return Date.now() - entry.timestamp < CACHE_TTL_MS;
}

export function useSafetyScore() {
    const score = ref<SafetyScore | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function fetchScore(venueUuid: string): Promise<SafetyScore | null> {
        const cached = scoreCache.get(venueUuid);

        if (cached && isCacheValid(cached)) {
            score.value = cached.score;

            return cached.score;
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<SafetyScoreResponse>(
                `/api/venues/${venueUuid}/safety-score`,
            );

            const transformedScore = transformSafetyScoreResponse(response.data);

            scoreCache.set(venueUuid, {
                score: transformedScore,
                timestamp: Date.now(),
            });

            score.value = transformedScore;

            return transformedScore;
        } catch (err) {
            if (axios.isAxiosError(err) && err.response?.status === 404) {
                score.value = null;

                return null;
            }

            error.value = err instanceof Error ? err.message : 'Failed to fetch safety score';
            console.error('Failed to fetch safety score:', err);

            return null;
        } finally {
            loading.value = false;
        }
    }

    function clearCache(venueUuid?: string): void {
        if (venueUuid) {
            scoreCache.delete(venueUuid);
        } else {
            scoreCache.clear();
        }
    }

    function resetState(): void {
        score.value = null;
        loading.value = false;
        error.value = null;
    }

    return {
        score: readonly(score),
        loading: readonly(loading),
        error: readonly(error),

        fetchScore,
        clearCache,
        resetState,
    };
}
