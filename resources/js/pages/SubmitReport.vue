<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import AppLayout from '@/components/layout/AppLayout.vue';
import ReportForm from '@/components/report/ReportForm.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DisclaimerBanner from '@/components/ui/DisclaimerBanner.vue';
import { useReports } from '@/composables/useReports';
import { ReportSubmissionError } from '@/stores/reportStore';
import type { ReportSubmitData } from '@/types/report';
import type { Venue } from '@/types/venue';

interface Props {
    venue?: Venue;
}

defineProps<Props>();

const { submitReport } = useReports();

const serverErrors = ref<Record<string, string>>({});

const handleSubmit = async (data: ReportSubmitData): Promise<void> => {
    serverErrors.value = {};

    try {
        const report = await submitReport(data);

        toast.success('Report submitted successfully', {
            description: 'Thank you for helping keep our community safe.',
        });

        // Redirect to venue detail page using slug from the response
        if (report.venue?.slug) {
            router.visit(`/venues/${report.venue.slug}`);
        } else {
            router.visit('/');
        }
    } catch (error) {
        if (error instanceof ReportSubmissionError) {
            serverErrors.value = error.fieldErrors;
        }

        toast.error('Failed to submit report', {
            description:
                error instanceof Error
                    ? error.message
                    : 'An unexpected error occurred.',
        });
    }
};
</script>

<template>
    <AppLayout>
        <div class="container mx-auto max-w-3xl px-4 py-12">
            <div class="space-y-8">
                <div class="space-y-4">
                    <h1
                        class="text-4xl font-bold text-slate-900 dark:text-white"
                    >
                        Submit a Report
                    </h1>
                    <p class="text-lg text-slate-600 dark:text-gray-300">
                        Help keep others safe by sharing your experience. All
                        reports are anonymous and will be reviewed before
                        publication.
                    </p>
                </div>

                <DisclaimerBanner />

                <Card>
                    <CardHeader>
                        <CardTitle>Report Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ReportForm
                            :initial-venue="venue"
                            :server-errors="serverErrors"
                            @submit="handleSubmit"
                        />
                    </CardContent>
                </Card>

                <Card
                    class="border-slate-200 bg-slate-50 dark:border-gray-700 dark:bg-gray-800"
                >
                    <CardContent
                        class="space-y-4 pt-6 text-sm text-slate-700 dark:text-gray-300"
                    >
                        <h3
                            class="font-semibold text-slate-900 dark:text-white"
                        >
                            Privacy Notice
                        </h3>
                        <ul class="list-inside list-disc space-y-2">
                            <li>
                                Reports are completely anonymous - we do not
                                collect any personal information.
                            </li>
                            <li>
                                Do not include any information that could
                                identify you or others.
                            </li>
                            <li>
                                Reports are reviewed for personal information
                                before being published.
                            </li>
                            <li>
                                Drink Safe is for informational purposes only -
                                please report crimes to the police.
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
