<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminCoverageController from '@/actions/DrinkSafe/Reports/Controllers/AdminCoverageController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type CityCoverage = {
    city: string;
    venues: number;
    published_reports: number;
    pending_reports: number;
};

const props = defineProps<{
    coverage: CityCoverage[];
    cities: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Coverage',
                href: AdminCoverageController(),
            },
        ],
    },
});

const page = usePage();
const errors = computed(
    () => (page.props.errors as Record<string, string> | undefined) ?? {},
);

const cities = ref(props.cities);

const count = (): void =>
    router.get(
        AdminCoverageController.url(),
        cities.value.trim() === '' ? {} : { cities: cities.value },
        { preserveState: true },
    );
</script>

<template>
    <Head title="Coverage" />

    <div class="flex flex-col gap-8 p-4">
        <section class="space-y-4">
            <Heading
                title="Coverage"
                description="Published venues, published reports and pending reports per city."
            />

            <form
                class="flex flex-wrap items-end gap-4 rounded-xl border p-4"
                @submit.prevent="count"
            >
                <div class="grid min-w-0 flex-1 gap-2">
                    <Label for="cities">Cities, separated by commas</Label>
                    <Input
                        id="cities"
                        v-model="cities"
                        placeholder="Leave blank for the outreach cities"
                    />
                    <InputError :message="errors.cities" />
                </div>
                <Button type="submit">Count</Button>
            </form>

            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="border-b text-left">
                        <tr>
                            <th scope="col" class="p-3 font-medium">City</th>
                            <th scope="col" class="p-3 text-right font-medium">
                                Venues
                            </th>
                            <th scope="col" class="p-3 text-right font-medium">
                                Published reports
                            </th>
                            <th scope="col" class="p-3 text-right font-medium">
                                Pending reports
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="row in coverage" :key="row.city">
                            <th scope="row" class="p-3 text-left font-normal">
                                {{ row.city }}
                            </th>
                            <td class="p-3 text-right tabular-nums">
                                {{ row.venues }}
                            </td>
                            <td class="p-3 text-right tabular-nums">
                                {{ row.published_reports }}
                            </td>
                            <td class="p-3 text-right tabular-nums">
                                {{ row.pending_reports }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="text-sm text-muted-foreground">
                Counts use the venue's stored city, so venues in central Salford
                may be stored as Manchester.
            </p>
        </section>
    </div>
</template>
