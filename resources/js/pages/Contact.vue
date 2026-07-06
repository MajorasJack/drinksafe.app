<script setup lang="ts">
import {
    AlertTriangle,
    ChevronDown,
    ExternalLink,
    Mail,
    MessageSquare,
} from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';

const faqs = [
    {
        question: 'Are reports verified?',
        answer: 'Reports are submitted anonymously and are not independently verified. We rely on the community to submit honest reports. The presence of reports indicates community concern but does not confirm incidents occurred.',
    },
    {
        question: 'Can I report on behalf of someone else?',
        answer: 'Yes, you can submit a report if you directly witnessed an incident. However, please only report incidents you have first-hand knowledge of, not rumours or hearsay.',
    },
    {
        question: "I'm a venue owner. Can I dispute a report?",
        answer: 'Venue owners can contact us to discuss reports they believe to be false or malicious. We will review such requests but cannot guarantee removal. We encourage venues to take safety concerns seriously.',
    },
    {
        question: "How can I add a venue that isn't listed?",
        answer: "When submitting a report, you can search for venues. If a venue isn't in our database, it will be added automatically when you submit your report. Alternatively, contact us with venue details.",
    },
    {
        question: 'Is my report truly anonymous?',
        answer: 'Yes. We do not collect any personal information, require accounts, or store IP addresses linked to reports. There is no way for us or anyone else to identify who submitted a report.',
    },
];
</script>

<template>
    <AppLayout>
        <div class="container mx-auto max-w-4xl px-4 py-10 sm:py-12">
            <div class="space-y-8 md:space-y-12">
                <div class="space-y-3 text-center">
                    <h1
                        class="text-3xl font-bold text-slate-900 sm:text-4xl dark:text-white"
                    >
                        Contact Us
                    </h1>
                    <p
                        class="mx-auto max-w-2xl text-lg text-slate-600 sm:text-xl dark:text-gray-300"
                    >
                        Have questions, feedback, or concerns? We'd love to hear
                        from you.
                    </p>
                </div>

                <Card
                    class="border-amber-200 bg-amber-50 dark:border-amber-800/50 dark:bg-amber-900/20"
                >
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <AlertTriangle
                                class="size-6 shrink-0 text-amber-600 dark:text-amber-400"
                            />
                            <CardTitle
                                class="text-amber-900 dark:text-amber-200"
                            >
                                Emergency? Contact the Police
                            </CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent
                        class="space-y-4 text-amber-900 dark:text-amber-300"
                    >
                        <p>
                            If you are in immediate danger or have been a victim
                            of a crime, please contact emergency services
                            directly. Drink Safe cannot respond to emergencies.
                        </p>
                        <ul class="list-inside list-disc space-y-1">
                            <li><strong>UK Emergency:</strong> 999</li>
                            <li><strong>UK Non-Emergency:</strong> 101</li>
                            <li>
                                <strong>Crimestoppers (Anonymous):</strong>
                                0800 555 111
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <div class="grid gap-4 sm:gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Mail class="size-6 shrink-0 text-brand-teal" />
                                <CardTitle>General Enquiries</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-3 text-slate-700 dark:text-gray-300"
                        >
                            <p>
                                For questions about the platform, privacy
                                concerns, or general feedback:
                            </p>
                            <a
                                href="mailto:drinksafe@protonmail.com"
                                class="inline-flex items-center gap-2 text-brand-teal hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                drinksafe@protonmail.com
                                <ExternalLink class="size-4" />
                            </a>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <MessageSquare
                                    class="size-6 shrink-0 text-brand-teal"
                                />
                                <CardTitle>Report Issues</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-3 text-slate-700 dark:text-gray-300"
                        >
                            <p>
                                To report technical problems, incorrect venue
                                information, or content concerns:
                            </p>
                            <a
                                href="mailto:drinksafe@protonmail.com"
                                class="inline-flex items-center gap-2 text-brand-teal hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                drinksafe@protonmail.com
                                <ExternalLink class="size-4" />
                            </a>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-4">
                    <h2
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        Frequently Asked Questions
                    </h2>

                    <div class="space-y-3">
                        <Collapsible
                            v-for="faq in faqs"
                            :key="faq.question"
                            class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                        >
                            <CollapsibleTrigger
                                class="group flex w-full items-center justify-between gap-3 p-4 text-left"
                            >
                                <span
                                    class="font-semibold text-slate-900 dark:text-white"
                                >
                                    {{ faq.question }}
                                </span>
                                <ChevronDown
                                    class="size-5 shrink-0 text-slate-400 transition-transform duration-200 group-data-[state=open]:rotate-180"
                                />
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <p
                                    class="px-4 pb-4 text-slate-700 dark:text-gray-300"
                                >
                                    {{ faq.answer }}
                                </p>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </div>

                <Card class="bg-slate-50 dark:bg-gray-800">
                    <CardContent class="space-y-3 pt-6 text-center">
                        <p class="text-slate-700 dark:text-gray-300">
                            Thank you for helping make nightlife safer for
                            everyone.
                        </p>
                        <p class="text-sm text-slate-600 dark:text-gray-400">
                            Drink Safe is a community-driven platform. Your
                            feedback helps us improve.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
