<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const currentYear = new Date().getFullYear();

const footerLinks = [
    { href: '/privacy', label: 'Privacy Policy' },
    { href: '/terms', label: 'Terms of Service' },
    { href: '/contact', label: 'Contact' },
];
</script>

<template>
    <footer class="border-t border-slate-200 bg-slate-50 dark:border-gray-700 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-6 md:flex-row md:justify-between">
                <!-- Branding and Description -->
                <div class="flex-1">
                    <h3 class="mb-2 text-lg font-bold text-brand-teal">
                        Drink Safe
                    </h3>
                    <p class="max-w-md text-sm text-slate-600 dark:text-gray-300">
                        A community platform for sharing safety information
                        about venues. Help others make informed decisions about
                        where they choose to spend their time.
                    </p>
                </div>

                <!-- Links -->
                <div class="flex flex-col gap-4 sm:flex-row sm:gap-8">
                    <nav class="flex flex-col gap-2">
                        <Link
                            v-for="link in footerLinks"
                            :key="link.href"
                            :href="link.href"
                            class="text-sm text-slate-600 hover:text-brand-teal dark:text-gray-300 dark:hover:text-brand-teal"
                        >
                            {{ link.label }}
                        </Link>
                    </nav>
                </div>
            </div>

            <!-- Disclaimer and Copyright -->
            <div class="mt-6 border-t border-slate-200 pt-6 dark:border-gray-700">
                <p class="mb-2 text-xs text-slate-500 dark:text-gray-400">
                    <strong>Disclaimer:</strong> Drink Safe is an informational
                    platform only. Information provided may not be verified or
                    accurate. This does not replace reporting to authorities. If
                    you have experienced a crime, please contact the police
                    directly.
                </p>
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    &copy; {{ currentYear }} Drink Safe. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</template>
