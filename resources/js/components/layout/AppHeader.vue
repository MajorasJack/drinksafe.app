<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

const mobileMenuOpen = ref(false);
const page = usePage();

const navigationLinks = [
    { href: '/', label: 'Home' },
    { href: '/map', label: 'Map' },
    { href: '/about', label: 'About' },
    { href: '/contact', label: 'Contact' },
    { href: '/support', label: 'Support Us' },
];

const currentPath = computed((): string => page.url.split('?')[0]);

const isActive = (href: string): boolean =>
    href === '/'
        ? currentPath.value === '/'
        : currentPath.value.startsWith(href);

const closeMobileMenu = (): void => {
    mobileMenuOpen.value = false;
};
</script>

<template>
    <header
        class="border-b border-slate-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <!-- Logo -->
                <Link href="/" class="text-xl font-bold text-brand-teal">
                    Drink Safe
                </Link>

                <!-- Desktop Navigation -->
                <nav class="hidden items-center gap-6 md:flex">
                    <Link
                        v-for="link in navigationLinks"
                        :key="link.href"
                        :href="link.href"
                        :aria-current="isActive(link.href) ? 'page' : undefined"
                        :class="[
                            'text-sm font-medium transition-colors',
                            isActive(link.href)
                                ? 'text-brand-teal'
                                : 'text-slate-600 hover:text-brand-teal dark:text-gray-300 dark:hover:text-brand-teal',
                        ]"
                    >
                        {{ link.label }}
                    </Link>
                    <Button
                        as-child
                        class="bg-brand-teal text-white hover:bg-brand-teal/90"
                    >
                        <Link href="/submit-report">Share a Report</Link>
                    </Button>
                </nav>

                <!-- Mobile menu -->
                <Sheet v-model:open="mobileMenuOpen">
                    <SheetTrigger
                        class="inline-flex size-10 items-center justify-center rounded-md text-slate-900 md:hidden dark:text-white"
                        aria-label="Open navigation menu"
                    >
                        <Menu class="size-6" />
                    </SheetTrigger>
                    <SheetContent side="right" class="w-[300px] sm:w-[340px]">
                        <SheetHeader>
                            <SheetTitle class="text-brand-teal">
                                Drink Safe
                            </SheetTitle>
                        </SheetHeader>

                        <nav class="mt-6 flex flex-col gap-1 px-4">
                            <Link
                                v-for="link in navigationLinks"
                                :key="link.href"
                                :href="link.href"
                                :aria-current="
                                    isActive(link.href) ? 'page' : undefined
                                "
                                :class="[
                                    'flex min-h-12 items-center rounded-lg px-3 text-base font-medium transition-colors',
                                    isActive(link.href)
                                        ? 'bg-brand-teal/10 text-brand-teal'
                                        : 'text-slate-700 hover:bg-slate-100 dark:text-gray-200 dark:hover:bg-gray-800',
                                ]"
                                @click="closeMobileMenu"
                            >
                                {{ link.label }}
                            </Link>
                        </nav>

                        <div class="mt-4 px-4">
                            <Button
                                as-child
                                class="min-h-12 w-full bg-brand-teal text-white hover:bg-brand-teal/90"
                            >
                                <Link
                                    href="/submit-report"
                                    @click="closeMobileMenu"
                                >
                                    Share a Report
                                </Link>
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </div>
    </header>
</template>
