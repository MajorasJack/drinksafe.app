<script setup lang="ts">
import { FileText, AlertTriangle, Scale, Users, Ban, RefreshCw } from 'lucide-vue-next';
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
                        Terms of Service
                    </h1>
                    <p
                        class="mx-auto max-w-2xl text-xl text-slate-600 dark:text-gray-300"
                    >
                        Please read these terms carefully before using Drink
                        Safe.
                    </p>
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        Last updated: January 2025
                    </p>
                </div>

                <Card
                    class="border-amber-200 bg-amber-50 dark:border-amber-800/50 dark:bg-amber-900/20"
                >
                    <CardContent
                        class="flex items-start gap-4 pt-6 text-amber-900 dark:text-amber-300"
                    >
                        <AlertTriangle
                            class="mt-1 size-8 shrink-0 text-amber-600 dark:text-amber-400"
                        />
                        <div>
                            <h3 class="mb-2 font-semibold">Important Notice</h3>
                            <p>
                                Drink Safe is an informational platform only. It
                                is not a substitute for reporting crimes to the
                                police. If you have been a victim of drink
                                spiking or any crime, please contact emergency
                                services immediately.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <FileText class="size-6 text-brand-teal" />
                                <CardTitle>Acceptance of Terms</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
                        >
                            <p>
                                By accessing or using Drink Safe, you agree to be
                                bound by these Terms of Service. If you do not
                                agree to these terms, please do not use the
                                platform.
                            </p>
                            <p>
                                We reserve the right to modify these terms at any
                                time. Continued use of the platform after changes
                                constitutes acceptance of the modified terms.
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Scale class="size-6 text-brand-teal" />
                                <CardTitle>Platform Purpose</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
                        >
                            <p>
                                Drink Safe provides a platform for anonymous
                                reporting and viewing of drink spiking incidents
                                at venues. Our purpose is to:
                            </p>
                            <ul class="list-inside list-disc space-y-2">
                                <li>Enable community awareness of safety concerns</li>
                                <li>Allow anonymous incident reporting</li>
                                <li>Help individuals make informed decisions about venues</li>
                                <li>Complement (not replace) official reporting channels</li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Users class="size-6 text-brand-teal" />
                                <CardTitle>User Responsibilities</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
                        >
                            <p>When using Drink Safe, you agree to:</p>
                            <ul class="list-inside list-disc space-y-2">
                                <li>
                                    <strong>Report honestly:</strong> Only submit
                                    reports based on genuine incidents you
                                    experienced or directly witnessed
                                </li>
                                <li>
                                    <strong>Avoid false reports:</strong> Do not
                                    submit fabricated or malicious reports about
                                    venues
                                </li>
                                <li>
                                    <strong>Respect privacy:</strong> Do not
                                    include personal information about others in
                                    reports
                                </li>
                                <li>
                                    <strong>Use appropriately:</strong> Do not
                                    attempt to manipulate, spam, or abuse the
                                    platform
                                </li>
                                <li>
                                    <strong>Report crimes properly:</strong>
                                    Contact police for any criminal matters, not
                                    just this platform
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Ban class="size-6 text-brand-teal" />
                                <CardTitle>Prohibited Activities</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
                        >
                            <p>The following activities are strictly prohibited:</p>
                            <ul class="list-inside list-disc space-y-2">
                                <li>Submitting false or misleading reports</li>
                                <li>Attempting to identify anonymous reporters</li>
                                <li>Harassing venues or individuals based on reports</li>
                                <li>Using automated tools to scrape or manipulate data</li>
                                <li>Attempting to interfere with platform operation</li>
                                <li>Using the platform for defamation or harassment</li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <AlertTriangle class="size-6 text-brand-teal" />
                                <CardTitle>Disclaimers</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
                        >
                            <p>
                                <strong>No verification:</strong> Reports are
                                submitted anonymously and are not independently
                                verified. The presence of reports does not
                                confirm that incidents occurred.
                            </p>
                            <p>
                                <strong>No endorsement:</strong> Absence of
                                reports for a venue does not indicate the venue
                                is safe or endorse it in any way.
                            </p>
                            <p>
                                <strong>Use at own risk:</strong> Information on
                                this platform is provided "as is" without
                                warranty. Users should exercise their own
                                judgement and take appropriate precautions.
                            </p>
                            <p>
                                <strong>Not legal advice:</strong> Nothing on
                                this platform constitutes legal advice. Consult
                                appropriate authorities and professionals for
                                legal matters.
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <RefreshCw class="size-6 text-brand-teal" />
                                <CardTitle>Content Moderation</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
                        >
                            <p>
                                We reserve the right to remove reports that violate
                                these terms, appear to be spam or false, contain
                                inappropriate content, or are otherwise harmful to
                                the platform or community.
                            </p>
                            <p>
                                Venues may request review of reports they believe
                                to be false or defamatory. We will consider such
                                requests but are not obligated to remove reports.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <Card class="bg-slate-50 dark:bg-gray-800">
                    <CardContent class="space-y-4 pt-6 text-center">
                        <p class="text-slate-700 dark:text-gray-300">
                            Questions about these terms?
                        </p>
                        <p class="text-sm text-slate-600 dark:text-gray-400">
                            Contact us and we'll be happy to clarify any points.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
