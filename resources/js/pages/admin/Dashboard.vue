<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ReportModerationController from '@/actions/DrinkSafe/Reports/Controllers/ReportModerationController';
import Heading from '@/components/Heading.vue';
import TextLink from '@/components/TextLink.vue';
import { dashboard } from '@/routes/admin';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Admin dashboard" />

    <div class="flex flex-col gap-4 p-4">
        <Heading
            title="Admin dashboard"
            description="Review new reports before they appear on the site."
        />

        <p class="text-sm">
            <TextLink :href="ReportModerationController.index()">
                Go to the review queue
            </TextLink>
        </p>
    </div>
</template>
