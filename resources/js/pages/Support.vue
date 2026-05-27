<script setup lang="ts">
import { Heart, Server, Shield, Coffee } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Button from '@/components/ui/button/Button.vue';

const kofiUsername = 'parasyte';
const kofiUrl = `https://ko-fi.com/${kofiUsername}`;
</script>

<template>
    <AppLayout>
        <div class="container mx-auto max-w-4xl px-4 py-12">
            <div class="space-y-12">
                <div class="space-y-4 text-center">
                    <h1
                        class="text-4xl font-bold text-slate-900 dark:text-white"
                    >
                        Support Drink Safe
                    </h1>
                    <p
                        class="mx-auto max-w-2xl text-xl text-slate-600 dark:text-gray-300"
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
                            class="flex flex-col items-center space-y-6 text-center"
                        >
                            <div
                                class="flex size-16 items-center justify-center rounded-full bg-brand-teal/10"
                            >
                                <Heart
                                    class="size-8 text-brand-teal"
                                    fill="currentColor"
                                />
                            </div>
                            <div class="space-y-3">
                                <h2
                                    class="text-2xl font-bold text-slate-900 dark:text-white"
                                >
                                    Drink Safe is completely free
                                </h2>
                                <p
                                    class="max-w-xl text-slate-600 dark:text-gray-300"
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
                                    class="bg-[#FF5E5B] text-white hover:bg-[#e54d4a]"
                                >
                                    <Coffee class="mr-2 size-5" />
                                    Support us on Ko-fi
                                </Button>
                            </a>
                        </div>
                    </CardContent>
                </Card>

                <div class="space-y-6">
                    <h2
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        Where your support goes
                    </h2>

                    <div class="grid gap-6 md:grid-cols-3">
                        <Card>
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-10 items-center justify-center rounded-full bg-brand-teal/10"
                                    >
                                        <Server
                                            class="size-5 text-brand-teal"
                                        />
                                    </div>
                                    <CardTitle class="text-lg">
                                        Server Costs
                                    </CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p
                                    class="text-sm text-slate-600 dark:text-gray-300"
                                >
                                    Hosting, database, and infrastructure costs
                                    to keep Drink Safe running smoothly and
                                    reliably.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-10 items-center justify-center rounded-full bg-brand-teal/10"
                                    >
                                        <Shield
                                            class="size-5 text-brand-teal"
                                        />
                                    </div>
                                    <CardTitle class="text-lg">
                                        Security
                                    </CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p
                                    class="text-sm text-slate-600 dark:text-gray-300"
                                >
                                    SSL certificates, security monitoring, and
                                    regular updates to protect user privacy.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-10 items-center justify-center rounded-full bg-brand-teal/10"
                                    >
                                        <Heart class="size-5 text-brand-teal" />
                                    </div>
                                    <CardTitle class="text-lg">
                                        Development
                                    </CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p
                                    class="text-sm text-slate-600 dark:text-gray-300"
                                >
                                    Continued improvements, new features, and
                                    maintaining the platform for the community.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Card class="bg-slate-50 dark:bg-gray-800">
                    <CardContent class="space-y-4 pt-6 text-center">
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
