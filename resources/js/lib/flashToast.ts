import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashMessages } from '@/types/shared';
import type { FlashToast } from '@/types/ui';

// the session flash messages that `->with('success', ...)` sets
const toastSessionFlash = (flash: FlashMessages | undefined): void =>
    (['success', 'warning', 'error'] as const).forEach((type) => {
        const message = flash?.[type];

        if (message) {
            toast[type](message);
        }
    });

/**
 * Show the server's flash messages as toasts.
 *
 * Inertia fires `success` only after a visit, so the session messages on the
 * first page of a full page load are shown once from that page's props.
 */
export function initializeFlashToast(
    initialFlash: FlashMessages | undefined,
): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });

    router.on('success', (event) =>
        toastSessionFlash(
            event.detail.page.props.flash as FlashMessages | undefined,
        ),
    );

    toastSessionFlash(initialFlash);
}
