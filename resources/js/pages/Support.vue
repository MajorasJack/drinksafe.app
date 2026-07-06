<script setup lang="ts">
import { Coffee, Heart, Server, Shield } from 'lucide-vue-next';
import type { Component } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent } from '@/components/ui/card';

const kofiUsername = 'parasyte';
const kofiUrl = `https://ko-fi.com/${kofiUsername}`;

interface SupportArea {
    icon: Component;
    title: string;
    description: string;
}

const supportAreas: SupportArea[] = [
    {
        icon: Server,
        title: 'Server Costs',
        description:
            'Hosting, database, and infrastructure costs to keep Drink Safe running smoothly and reliably.',
    },
    {
        icon: Shield,
        title: 'Security',
        description:
            'SSL certificates, security monitoring, and regular updates to protect user privacy.',
    },
    {
        icon: Heart,
        title: 'Development',
        description:
            'Continued improvements, new features, and maintaining the platform for the community.',
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
                        Support Drink Safe
                    </h1>
                    <p
                        class="mx-auto max-w-2xl text-lg text-slate-600 sm:text-xl dark:text-gray-300"
                    >
                        Help us keep the lights on and continue protecting
                        communities.
                    </p>
                </div>

                <Card
                    class="border-brand-teal/20 bg-gradient-to-br from-brand-teal/5 to-transparent"
                >
                    <CardContent class="pt-6">
                        <div
                            class="flex flex-col items-center space-y-5 text-center"
                        >
                            <div
                                class="flex size-14 items-center justify-center rounded-full bg-brand-teal/10 sm:size-16"
                            >
                                <Heart
                                    class="size-7 text-brand-teal sm:size-8"
                                    fill="currentColor"
                                />
                            </div>
                            <div class="space-y-2">
                                <h2
                                    class="text-xl font-bold text-slate-900 sm:text-2xl dark:text-white"
                                >
                                    Drink Safe is completely free
                                </h2>
                                <p
                                    class="mx-auto max-w-xl text-slate-600 dark:text-gray-300"
                                >
                                    We believe safety information should be
                                    accessible to everyone. That's why Drink
                                    Safe will always be free to use, with no
                                    ads, no paywalls, and no data collection.
                                </p>
                            </div>
                            <a
                                :href="kofiUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-block"
                            >
                                <Button
                                    size="lg"
                                    class="min-h-12 bg-[#FF5E5B] text-white hover:bg-[#e54d4a]"
                                >
                                    <Coffee class="mr-2 size-5" />
                                    Support us on Ko-fi
                                </Button>
                            </a>
                        </div>
                    </CardContent>
                </Card>

                <div class="space-y-4">
                    <h2
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        Where your support goes
                    </h2>

                    <div class="grid gap-4 sm:grid-cols-3 sm:gap-6">
                        <Card v-for="area in supportAreas" :key="area.title">
                            <CardContent
                                class="flex items-start gap-4 p-4 sm:block sm:p-6"
                            >
                                <div
                                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-teal/10"
                                >
                                    <component
                                        :is="area.icon"
                                        class="size-5 text-brand-teal"
                                    />
                                </div>
                                <div class="space-y-1 sm:mt-3 sm:space-y-2">
                                    <h3
                                        class="font-semibold text-slate-900 dark:text-white"
                                    >
                                        {{ area.title }}
                                    </h3>
                                    <p
                                        class="text-sm text-slate-600 dark:text-gray-300"
                                    >
                                        {{ area.description }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Card class="bg-slate-50 dark:bg-gray-800">
                    <CardContent class="space-y-3 pt-6 text-center">
                        <h3
                            class="text-lg font-semibold text-slate-900 dark:text-white"
                        >
                            Every contribution helps
                        </h3>
                        <p class="text-slate-600 dark:text-gray-300">
                            Whether it's the price of a coffee or more, your
                            support directly helps keep Drink Safe available for
                            everyone who needs it.
                        </p>
                        <p class="text-sm text-slate-500 dark:text-gray-400">
                            Drink Safe is a community-driven project built by
                            volunteers who care about nightlife safety.
                        </p>
                    </CardContent>
                </Card>

                <div class="text-center">
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        Can't donate? No problem! You can still help by sharing
                        Drink Safe with others or submitting reports to help
                        keep your community informed.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
