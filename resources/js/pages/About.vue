<script setup lang="ts">
import { AlertTriangle, MapPin, Search, Shield } from 'lucide-vue-next';
import type { Component } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface HowToUseStep {
    icon: Component;
    title: string;
    description: string;
}

const howToUseSteps: HowToUseStep[] = [
    {
        icon: Search,
        title: 'Search Venues',
        description:
            'Use our search tool to find venues and see if any incidents have been reported.',
    },
    {
        icon: MapPin,
        title: 'View on Map',
        description:
            'Browse venues on an interactive map to see reported incidents in your area.',
    },
    {
        icon: AlertTriangle,
        title: 'Report Incidents',
        description:
            "Submit anonymous reports about incidents you've experienced or witnessed.",
    },
];

const safetyTips = [
    'Never leave your drink unattended, even for a moment.',
    'Watch your drink being prepared and carried to you.',
    "If your drink tastes or smells unusual, don't drink it.",
    'Stay with trusted friends and look out for each other.',
    "If you feel unwell or suspect you've been spiked, tell someone immediately and seek help.",
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
                        About Drink Safe
                    </h1>
                    <p
                        class="mx-auto max-w-2xl text-lg text-slate-600 sm:text-xl dark:text-gray-300"
                    >
                        Empowering communities to stay informed and make safer
                        decisions about nightlife venues.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <Shield class="size-6 shrink-0 text-brand-teal" />
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

                <div class="space-y-4">
                    <h2
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        How to Use Drink Safe
                    </h2>

                    <div class="grid gap-4 sm:grid-cols-3 sm:gap-6">
                        <Card v-for="step in howToUseSteps" :key="step.title">
                            <CardContent
                                class="flex items-start gap-4 p-4 sm:block sm:space-y-3 sm:p-6"
                            >
                                <div
                                    class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-teal/10"
                                >
                                    <component
                                        :is="step.icon"
                                        class="size-6 text-brand-teal"
                                    />
                                </div>
                                <div class="space-y-1 sm:space-y-2">
                                    <h3
                                        class="font-semibold text-slate-900 dark:text-white"
                                    >
                                        {{ step.title }}
                                    </h3>
                                    <p
                                        class="text-sm text-slate-600 dark:text-gray-300"
                                    >
                                        {{ step.description }}
                                    </p>
                                </div>
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
                                class="size-6 shrink-0 text-amber-600 dark:text-amber-400"
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

                <div class="space-y-4">
                    <h2
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        Staying Safe
                    </h2>

                    <Card>
                        <CardContent class="pt-6">
                            <ul
                                class="space-y-3 text-slate-700 dark:text-gray-300"
                            >
                                <li
                                    v-for="(tip, index) in safetyTips"
                                    :key="tip"
                                    class="flex gap-3"
                                >
                                    <span
                                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-teal/10 text-sm font-medium text-brand-teal"
                                    >
                                        {{ index + 1 }}
                                    </span>
                                    <span>{{ tip }}</span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-4">
                    <h2
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        Resources & Support
                    </h2>

                    <Card>
                        <CardContent class="pt-6">
                            <div class="grid gap-6 sm:grid-cols-3">
                                <div>
                                    <h3
                                        class="mb-2 font-semibold text-slate-900 dark:text-white"
                                    >
                                        Drink Spiking Support
                                    </h3>
                                    <ul
                                        class="space-y-2 text-sm text-slate-700 dark:text-gray-300"
                                    >
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
                                    <h3
                                        class="mb-2 font-semibold text-slate-900 dark:text-white"
                                    >
                                        Sexual Assault Support
                                    </h3>
                                    <ul
                                        class="space-y-2 text-sm text-slate-700 dark:text-gray-300"
                                    >
                                        <li>
                                            <strong>Rape Crisis:</strong>
                                            <span class="ml-1">
                                                0808 500 2222
                                            </span>
                                        </li>
                                        <li>
                                            <strong
                                                >The Survivors Trust:</strong
                                            >
                                            <span class="ml-1">
                                                08088 010 818
                                            </span>
                                        </li>
                                    </ul>
                                </div>

                                <div>
                                    <h3
                                        class="mb-2 font-semibold text-slate-900 dark:text-white"
                                    >
                                        Mental Health Support
                                    </h3>
                                    <ul
                                        class="space-y-2 text-sm text-slate-700 dark:text-gray-300"
                                    >
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
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card class="bg-slate-50 dark:bg-gray-800">
                    <CardContent class="space-y-3 pt-6 text-center">
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
