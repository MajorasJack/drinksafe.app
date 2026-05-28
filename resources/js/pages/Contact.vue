<script setup lang="ts">
import { Mail, MessageSquare, AlertTriangle, ExternalLink } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
</script>

<template>
    <AppLayout>
        <div class="container mx-auto max-w-4xl px-4 py-12">
            <div class="space-y-12">
                <div class="space-y-4 text-center">
                    <h1
                        class="text-4xl font-bold text-slate-900 dark:text-white"
                    >
                        Contact Us
                    </h1>
                    <p
                        class="mx-auto max-w-2xl text-xl text-slate-600 dark:text-gray-300"
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
                                class="size-6 text-amber-600 dark:text-amber-400"
                            />
                            <CardTitle class="text-amber-900 dark:text-amber-200">
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
                        <div class="space-y-2">
                            <ul class="list-inside list-disc space-y-1">
                                <li>
                                    <strong>UK Emergency:</strong> 999
                                </li>
                                <li>
                                    <strong>UK Non-Emergency:</strong> 101
                                </li>
                                <li>
                                    <strong>Crimestoppers (Anonymous):</strong>
                                    0800 555 111
                                </li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>

                <div class="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Mail class="size-6 text-brand-teal" />
                                <CardTitle>General Enquiries</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
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
                                <MessageSquare class="size-6 text-brand-teal" />
                                <CardTitle>Report Issues</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
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

                <div class="space-y-6">
                    <h2
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        Frequently Asked Questions
                    </h2>

                    <div class="space-y-4">
                        <Card>
                            <CardContent class="pt-6">
                                <h3
                                    class="mb-2 font-semibold text-slate-900 dark:text-white"
                                >
                                    Are reports verified?
                                </h3>
                                <p class="text-slate-700 dark:text-gray-300">
                                    Reports are submitted anonymously and are not
                                    independently verified. We rely on the
                                    community to submit honest reports. The
                                    presence of reports indicates community
                                    concern but does not confirm incidents
                                    occurred.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="pt-6">
                                <h3
                                    class="mb-2 font-semibold text-slate-900 dark:text-white"
                                >
                                    Can I report on behalf of someone else?
                                </h3>
                                <p class="text-slate-700 dark:text-gray-300">
                                    Yes, you can submit a report if you directly
                                    witnessed an incident. However, please only
                                    report incidents you have first-hand
                                    knowledge of, not rumours or hearsay.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="pt-6">
                                <h3
                                    class="mb-2 font-semibold text-slate-900 dark:text-white"
                                >
                                    I'm a venue owner. Can I dispute a report?
                                </h3>
                                <p class="text-slate-700 dark:text-gray-300">
                                    Venue owners can contact us to discuss
                                    reports they believe to be false or
                                    malicious. We will review such requests but
                                    cannot guarantee removal. We encourage venues
                                    to take safety concerns seriously.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="pt-6">
                                <h3
                                    class="mb-2 font-semibold text-slate-900 dark:text-white"
                                >
                                    How can I add a venue that isn't listed?
                                </h3>
                                <p class="text-slate-700 dark:text-gray-300">
                                    When submitting a report, you can search for
                                    venues. If a venue isn't in our database, it
                                    will be added automatically when you submit
                                    your report. Alternatively, contact us with
                                    venue details.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="pt-6">
                                <h3
                                    class="mb-2 font-semibold text-slate-900 dark:text-white"
                                >
                                    Is my report truly anonymous?
                                </h3>
                                <p class="text-slate-700 dark:text-gray-300">
                                    Yes. We do not collect any personal
                                    information, require accounts, or store IP
                                    addresses linked to reports. There is no way
                                    for us or anyone else to identify who
                                    submitted a report.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Card class="bg-slate-50 dark:bg-gray-800">
                    <CardContent class="space-y-4 pt-6 text-center">
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
