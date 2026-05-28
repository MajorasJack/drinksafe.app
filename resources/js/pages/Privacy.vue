<script setup lang="ts">
import { Shield, Eye, Database, Clock, Lock, UserX } from 'lucide-vue-next';
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
                        Privacy Policy
                    </h1>
                    <p
                        class="mx-auto max-w-2xl text-xl text-slate-600 dark:text-gray-300"
                    >
                        Your privacy is fundamental to our mission. Learn how we
                        protect your anonymity.
                    </p>
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        Last updated: January 2025
                    </p>
                </div>

                <Card
                    class="border-brand-teal/30 bg-brand-teal/5 dark:border-brand-teal/20 dark:bg-brand-teal/10"
                >
                    <CardContent
                        class="flex items-start gap-4 pt-6 text-slate-700 dark:text-gray-300"
                    >
                        <Shield
                            class="mt-1 size-8 shrink-0 text-brand-teal"
                        />
                        <div>
                            <h3
                                class="mb-2 font-semibold text-slate-900 dark:text-white"
                            >
                                Our Privacy Commitment
                            </h3>
                            <p>
                                Drink Safe is designed with privacy at its core.
                                We do not collect personal information, require
                                accounts, or track individual users. Reports are
                                completely anonymous - we cannot identify who
                                submitted them.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Database class="size-6 text-brand-teal" />
                                <CardTitle>Information We Collect</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
                        >
                            <p>
                                We collect minimal information necessary to
                                operate the platform:
                            </p>
                            <ul class="list-inside list-disc space-y-2">
                                <li>
                                    <strong>Report Data:</strong> Venue name,
                                    date of incident, and incident description.
                                    No personal identifiers are collected.
                                </li>
                                <li>
                                    <strong>Venue Information:</strong> Publicly
                                    available venue details such as name,
                                    address, and location coordinates.
                                </li>
                                <li>
                                    <strong>Technical Data:</strong> Anonymous
                                    usage statistics to improve the platform
                                    (page views, browser type). No individual
                                    tracking.
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <UserX class="size-6 text-brand-teal" />
                                <CardTitle>Information We Don't Collect</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
                        >
                            <p>We deliberately avoid collecting:</p>
                            <ul class="list-inside list-disc space-y-2">
                                <li>Names, email addresses, or contact details</li>
                                <li>Account credentials or passwords</li>
                                <li>IP addresses linked to reports</li>
                                <li>Device identifiers or fingerprints</li>
                                <li>Location data beyond venue searches</li>
                                <li>Any information that could identify reporters</li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Eye class="size-6 text-brand-teal" />
                                <CardTitle>How We Use Information</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
                        >
                            <p>The limited information we collect is used to:</p>
                            <ul class="list-inside list-disc space-y-2">
                                <li>Display incident reports on the map and venue pages</li>
                                <li>Calculate venue statistics and heatmap data</li>
                                <li>Improve platform performance and user experience</li>
                                <li>Detect and prevent abuse or spam submissions</li>
                            </ul>
                            <p>
                                We do not sell, share, or monetise any data. We
                                do not use data for advertising or marketing
                                purposes.
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Lock class="size-6 text-brand-teal" />
                                <CardTitle>Data Security</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
                        >
                            <p>We protect the data we hold through:</p>
                            <ul class="list-inside list-disc space-y-2">
                                <li>HTTPS encryption for all data transmission</li>
                                <li>Secure hosting with reputable providers</li>
                                <li>Regular security updates and monitoring</li>
                                <li>Minimal data collection to reduce risk</li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Clock class="size-6 text-brand-teal" />
                                <CardTitle>Data Retention</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="space-y-4 text-slate-700 dark:text-gray-300"
                        >
                            <p>
                                Report data is retained indefinitely to provide a
                                comprehensive historical record for community safety.
                                Since reports contain no personal information, there
                                is no privacy risk in maintaining this record.
                            </p>
                            <p>
                                Anonymous analytics data is retained for up to 26
                                months to understand long-term usage patterns.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <Card class="bg-slate-50 dark:bg-gray-800">
                    <CardContent class="space-y-4 pt-6 text-center">
                        <p class="text-slate-700 dark:text-gray-300">
                            Have questions about our privacy practices?
                        </p>
                        <p class="text-sm text-slate-600 dark:text-gray-400">
                            Contact us and we'll be happy to explain how we
                            protect your privacy.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
