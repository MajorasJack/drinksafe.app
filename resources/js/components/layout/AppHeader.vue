<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Menu, X } from 'lucide-vue-next';
import { ref } from 'vue';

const mobileMenuOpen = ref(false);

const navigationLinks = [
    { href: '/', label: 'Home' },
    { href: '/map', label: 'Map' },
    { href: '/submit-report', label: 'Submit Report' },
    { href: '/about', label: 'About' },
];
</script>

<template>
    <header class="border-b border-slate-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <!-- Logo -->
                <Link href="/" class="text-xl font-bold text-brand-teal">
                    Drink Safe
                </Link>

                <!-- Desktop Navigation -->
                <nav class="hidden space-x-8 md:flex">
                    <Link
                        v-for="link in navigationLinks"
                        :key="link.href"
                        :href="link.href"
                        class="text-slate-600 hover:text-brand-teal dark:text-gray-300 dark:hover:text-brand-teal"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <!-- Mobile menu button -->
                <button
                    type="button"
                    class="md:hidden"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                >
                    <Menu v-if="!mobileMenuOpen" class="size-6 text-slate-900 dark:text-white" />
                    <X v-else class="size-6 text-slate-900 dark:text-white" />
                </button>
            </div>

            <!-- Mobile Navigation -->
            <nav v-if="mobileMenuOpen" class="space-y-2 py-4 md:hidden">
                <Link
                    v-for="link in navigationLinks"
                    :key="link.href"
                    :href="link.href"
                    class="block text-slate-600 hover:text-brand-teal dark:text-gray-300 dark:hover:text-brand-teal"
                    @click="mobileMenuOpen = false"
                >
                    {{ link.label }}
                </Link>
            </nav>
        </div>
    </header>
</template>
