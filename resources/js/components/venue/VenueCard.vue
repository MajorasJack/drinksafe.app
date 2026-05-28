<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MapPin } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import Card from '@/components/ui/card/Card.vue';
import SafetyBadge from '@/components/venue/SafetyBadge.vue';
import { useSafetyScore } from '@/composables/useSafetyScore';
import type { Venue } from '@/types/venue';

interface Props {
    venue: Venue;
}

const props = defineProps<Props>();

const { score, loading: safetyLoading, fetchScore } = useSafetyScore();

const reportsText = computed(() => {
    const count = props.venue.reports_count ?? 0;

    return count === 1 ? '1 report' : `${count} reports`;
});

onMounted(() => {
    fetchScore(props.venue.uuid);
});
</script>

<template>
    <Card
        class="group cursor-pointer transition-all hover:border-brand-teal/30 hover:shadow-md"
    >
        <Link :href="`/venues/${venue.slug}`" class="block px-6 py-0">
            <div class="mb-3">
                <div class="flex items-start justify-between gap-2">
                    <h3
                        class="text-lg font-semibold text-slate-900 transition-colors group-hover:text-brand-teal"
                    >
                        {{ venue.name }}
                    </h3>
                    <SafetyBadge
                        :score="score"
                        :loading="safetyLoading"
                        size="sm"
                    />
                </div>
                <div
                    class="mt-1 flex items-center gap-1 text-sm text-slate-500"
                >
                    <MapPin class="size-3.5" />
                    <span>{{ venue.city }}</span>
                </div>
                <p v-if="venue.address" class="mt-1 text-sm text-slate-600">
                    {{ venue.address }}
                </p>
            </div>

            <div
                v-if="venue.reports_count"
                class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600"
            >
                {{ reportsText }}
            </div>

            <div class="mt-4 text-sm font-medium text-brand-teal">
                View Details →
            </div>
        </Link>
    </Card>
</template>
