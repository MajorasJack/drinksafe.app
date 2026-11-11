import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import SidebarLayout from '@/layouts/SidebarLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import type { FlashMessages } from '@/types/shared';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('admin/'):
                return SidebarLayout;
            case name.startsWith('settings/'):
                return [SidebarLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    setup: ({ el, App, props, plugin }) => {
        if (!el) {
            throw new Error('Element not found');
        }

        createApp({ render: () => h(App, props) })
            .use(pinia)
            .use(plugin)
            .mount(el);

        // listen for flash toasts from the server, after mounting so the Toaster is there for the first page's messages
        initializeFlashToast(
            props.initialPage.props.flash as FlashMessages | undefined,
        );
    },
});

// This will set light / dark mode on page load...
initializeTheme();
