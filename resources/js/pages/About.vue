<script setup lang="ts">
import { Shield, Search, MapPin, AlertTriangle } from 'lucide-vue-next';
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
                        About Drink Safe
                    </h1>
                    <p
                        class="mx-auto max-w-2xl text-xl text-slate-600 dark:text-gray-300"
                    >
                        Empowering communities to stay informed and make safer
                        decisions about nightlife venues.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <Shield class="size-6 text-brand-teal" />
                            <CardTitle>Our Mission</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent
                        class="space-y-4 text-slate-700 dark:text-gray-300"
                    >
                        <p>
                            Drink Safe was created to address the serious issue
                            of drink spiking in nightlife venues. We believe
                            that by sharing information anonymously, we can help
                            people make more informed decisions about where they
                            choose to spend their time.
                        </p>
                        <p>
                            Our platform allows anyone to report incidents and
                            search for venues with reported activity, all whilst
                            maintaining complete anonymity. No personal
                            information is collected or stored.
                        </p>
                    </CardContent>
                </Card>

                <div class="space-y-6">
                    <h2
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        How to Use Drink Safe
                    </h2>

                    <div class="grid gap-6 md:grid-cols-3">
                        <Card>
                            <CardContent class="space-y-3 pt-6">
                                <div
                                    class="flex size-12 items-center justify-center rounded-full bg-brand-teal/10"
                                >
                                    <Search class="size-6 text-brand-teal" />
                                </div>
                                <h3
                                    class="font-semibold text-slate-900 dark:text-white"
                                >
                                    Search Venues
                                </h3>
                                <p
                                    class="text-sm text-slate-600 dark:text-gray-300"
                                >
                                    Use our search tool to find venues and see
                                    if any incidents have been reported.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="space-y-3 pt-6">
                                <div
                                    class="flex size-12 items-center justify-center rounded-full bg-brand-teal/10"
                                >
                                    <MapPin class="size-6 text-brand-teal" />
                                </div>
                                <h3
                                    class="font-semibold text-slate-900 dark:text-white"
                                >
                                    View on Map
                                </h3>
                                <p
                                    class="text-sm text-slate-600 dark:text-gray-300"
                                >
                                    Browse venues on an interactive map to see
                                    reported incidents in your area.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="space-y-3 pt-6">
                                <div
                                    class="flex size-12 items-center justify-center rounded-full bg-brand-teal/10"
                                >
                                    <AlertTriangle
                                        class="size-6 text-brand-teal"
                                    />
                                </div>
                                <h3
                                    class="font-semibold text-slate-900 dark:text-white"
                                >
                                    Report Incidents
                                </h3>
                                <p
                                    class="text-sm text-slate-600 dark:text-gray-300"
                                >
                                    Submit anonymous reports about incidents
                                    you've experienced or witnessed.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Card
                    class="border-amber-200 bg-amber-50 dark:border-amber-800/50 dark:bg-amber-900/20"
                >
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <AlertTriangle
                                class="size-6 text-amber-600 dark:text-amber-400"
                            />
                            <CardTitle
                                class="text-amber-900 dark:text-amber-200"
                            >
                                Important Safety Information
                            </CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent
                        class="space-y-4 text-amber-900 dark:text-amber-300"
                    >
                        <p>
                            Drink Safe is an
                            <strong>informational platform only</strong>. It is
                            not a substitute for reporting crimes to the police.
                        </p>
                        <p>
                            If you have been a victim of drink spiking or any
                            other crime, please contact your local police
                            immediately. They can provide support, collect
                            evidence, and investigate the incident properly.
                        </p>
                        <div class="space-y-2">
                            <p class="font-semibold">Emergency Services:</p>
                            <ul class="list-inside list-disc space-y-1">
                                <li>UK Emergency: 999</li>
                                <li>UK Non-Emergency: 101</li>
                                <li>Crimestoppers (Anonymous): 0800 555 111</li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>

                <div class="space-y-6">
                    <h2
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        Staying Safe
                    </h2>

                    <Card>
                        <CardContent
                            class="space-y-4 pt-6 text-slate-700 dark:text-gray-300"
                        >
                            <ul class="space-y-3">
                                <li class="flex gap-3">
                                    <span
                                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-teal/10 text-sm font-medium text-brand-teal"
                                    >
                                        1
                                    </span>
                                    <span>
                                        Never leave your drink unattended, even
                                        for a moment.
                                    </span>
                                </li>
                                <li class="flex gap-3">
                                    <span
                                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-teal/10 text-sm font-medium text-brand-teal"
                                    >
                                        2
                                    </span>
                                    <span>
                                        Watch your drink being prepared and
                                        carried to you.
                                    </span>
                                </li>
                                <li class="flex gap-3">
                                    <span
                                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-teal/10 text-sm font-medium text-brand-teal"
                                    >
                                        3
                                    </span>
                                    <span>
                                        If your drink tastes or smells unusual,
                                        don't drink it.
                                    </span>
                                </li>
                                <li class="flex gap-3">
                                    <span
                                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-teal/10 text-sm font-medium text-brand-teal"
                                    >
                                        4
                                    </span>
                                    <span>
                                        Stay with trusted friends and look out
                                        for each other.
                                    </span>
                                </li>
                                <li class="flex gap-3">
                                    <span
                                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-teal/10 text-sm font-medium text-brand-teal"
                                    >
                                        5
                                    </span>
                                    <span>
                                        If you feel unwell or suspect you've
                                        been spiked, tell someone immediately
                                        and seek help.
                                    </span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <h2
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        Resources & Support
                    </h2>

                    <Card>
                        <CardContent
                            class="space-y-4 pt-6 text-slate-700 dark:text-gray-300"
                        >
                            <div>
                                <h3 class="mb-2 font-semibold dark:text-white">
                                    Drink Spiking Support
                                </h3>
                                <ul class="space-y-2 text-sm">
                                    <li>
                                        <strong>Stamp Out Spiking:</strong>
                                        <a
                                            href="https://www.stampoutspiking.org"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="ml-1 text-brand-teal hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                                        >
                                            stampoutspiking.org
                                        </a>
                                    </li>
                                    <li>
                                        <strong>Victim Support:</strong>
                                        <span class="ml-1">
                                            08 08 16 89 111
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <h3 class="mb-2 font-semibold dark:text-white">
                                    Sexual Assault Support
                                </h3>
                                <ul class="space-y-2 text-sm">
                                    <li>
                                        <strong>Rape Crisis:</strong>
                                        <span class="ml-1">
                                            0808 500 2222
                                        </span>
                                    </li>
                                    <li>
                                        <strong>The Survivors Trust:</strong>
                                        <span class="ml-1">
                                            08088 010 818
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <h3 class="mb-2 font-semibold dark:text-white">
                                    Mental Health Support
                                </h3>
                                <ul class="space-y-2 text-sm">
                                    <li>
                                        <strong>Samaritans:</strong>
                                        <span class="ml-1">116 123</span>
                                    </li>
                                    <li>
                                        <strong>Mind:</strong>
                                        <span class="ml-1">
                                            0300 123 3393
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card class="bg-slate-50 dark:bg-gray-800">
                    <CardContent class="space-y-4 pt-6 text-center">
                        <p class="text-slate-700 dark:text-gray-300">
                            Have questions or feedback about Drink Safe?
                        </p>
                        <p class="text-sm text-slate-600 dark:text-gray-400">
                            Drink Safe is a community-driven platform dedicated
                            to keeping people informed and safe.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
