<script setup lang="ts">
import { Download, HandHeart, Mail, Scale, Shield } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface PrintDownload {
    href: string;
    title: string;
    description: string;
}

const reviewSteps = [
    'Someone submits a report without an account and without giving their name.',
    'Email addresses and phone numbers in the description are removed automatically. If any are still there afterwards, the report is turned away and the person is asked to take them out.',
    'The report is saved as waiting for review. It does not appear anywhere on the site yet.',
    'A person reads it and either publishes it or rejects it.',
    'If the report names a venue that is not on Drink Safe yet, the venue stays hidden as well until its first report is published.',
    'A published report can still be taken down later.',
];

const placements = [
    'Toilets, on the back of cubicle doors and by the mirrors or hand dryers',
    'Welfare, first aid and security desks',
    'Halls of residence and students’ union buildings',
];

const downloads: PrintDownload[] = [
    {
        href: '/downloads/drink-safe-poster-a4.pdf',
        title: 'Poster, A4',
        description:
            'White background, made for an office printer. Fine in black and white.',
    },
    {
        href: '/downloads/drink-safe-poster-a3.pdf',
        title: 'Poster, A3',
        description: 'The same poster at A3, for noticeboards and corridors.',
    },
    {
        href: '/downloads/drink-safe-flyer.pdf',
        title: 'Flyer, A5',
        description:
            'Double sided, with the safety tips and who can help. Set up for a print shop.',
    },
    {
        href: '/downloads/drink-safe-sticker-a.pdf',
        title: 'Sticker A',
        description:
            '100mm square, for cubicle doors. What to do if a drink has been spiked.',
    },
    {
        href: '/downloads/drink-safe-sticker-b.pdf',
        title: 'Sticker B',
        description:
            '100mm square, for mirrors and hand dryers. A QR code to check a venue.',
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
                        For venues and partners
                    </h1>
                    <p
                        class="mx-auto max-w-2xl text-lg text-slate-600 sm:text-xl dark:text-gray-300"
                    >
                        What Drink Safe is, how reports are handled, and how to
                        put our posters and stickers up.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <Shield class="size-6 shrink-0 text-brand-teal" />
                            <CardTitle>What Drink Safe is</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent
                        class="space-y-4 text-slate-700 dark:text-gray-300"
                    >
                        <p>
                            Drink Safe is a website where people can share and
                            read anonymous reports of drink spiking at venues.
                        </p>
                        <p>
                            Drink Safe will always be free to use, with no ads
                            on the site, no paywalls, and no data collection.
                            Nobody needs an account or has to give their name to
                            read or submit a report.
                        </p>
                        <p>
                            Drink Safe is an
                            <strong>informational platform only</strong>. It is
                            not a substitute for reporting crimes to the police.
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

                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <Scale class="size-6 shrink-0 text-brand-teal" />
                            <CardTitle>A report is not an accusation</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent
                        class="space-y-4 text-slate-700 dark:text-gray-300"
                    >
                        <p>
                            A report is one person's account of what happened to
                            them, or what they saw, at a venue. It is not a
                            finding against the venue or its staff.
                        </p>
                        <p>
                            Every report is reviewed by a person before it is
                            published. This is what happens to a report:
                        </p>
                        <ol class="space-y-3">
                            <li
                                v-for="(step, index) in reviewSteps"
                                :key="step"
                                class="flex gap-3"
                            >
                                <span
                                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-teal/10 text-sm font-medium text-brand-teal"
                                >
                                    {{ index + 1 }}
                                </span>
                                <span>{{ step }}</span>
                            </li>
                        </ol>
                        <p>
                            If your venue disagrees with a published report,
                            email
                            <a
                                href="mailto:drinksafe@protonmail.com"
                                class="text-brand-teal hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                                >drinksafe@protonmail.com</a
                            >
                            with a link to the report. We'll look at it and
                            remove anything that breaks our rules or names a
                            person.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <HandHeart
                                class="size-6 shrink-0 text-brand-teal"
                            />
                            <CardTitle>How to help</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent
                        class="space-y-4 text-slate-700 dark:text-gray-300"
                    >
                        <p>
                            Posters and stickers work best where people stop for
                            a moment on a night out, such as:
                        </p>
                        <ul class="list-inside list-disc space-y-1">
                            <li v-for="place in placements" :key="place">
                                {{ place }}
                            </li>
                        </ul>
                        <p>
                            <strong
                                >Please only put anything up with the venue's
                                permission.</strong
                            >
                            Ask the manager, or whoever looks after the
                            building, first.
                        </p>
                    </CardContent>
                </Card>

                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <Download class="size-6 shrink-0 text-brand-teal" />
                        <h2
                            class="text-2xl font-bold text-slate-900 dark:text-white"
                        >
                            Downloads
                        </h2>
                    </div>
                    <p class="text-slate-700 dark:text-gray-300">
                        Each file is a PDF and free to print as many times as
                        you like.
                    </p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <Card v-for="file in downloads" :key="file.href">
                            <CardContent class="space-y-2 p-4 sm:p-6">
                                <a
                                    :href="file.href"
                                    download
                                    class="inline-flex items-center gap-2 font-semibold text-brand-teal hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    <Download class="size-4" />
                                    {{ file.title }}
                                </a>
                                <p
                                    class="text-sm text-slate-600 dark:text-gray-300"
                                >
                                    {{ file.description }}
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Card class="bg-slate-50 dark:bg-gray-800">
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <Mail class="size-6 shrink-0 text-brand-teal" />
                            <CardTitle>Contact</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent
                        class="space-y-3 text-slate-700 dark:text-gray-300"
                    >
                        <p>
                            For questions about putting material up, or about a
                            report on the site, email us at:
                        </p>
                        <a
                            href="mailto:drinksafe@protonmail.com"
                            class="text-brand-teal hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                        >
                            drinksafe@protonmail.com
                        </a>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
