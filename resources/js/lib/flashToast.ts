import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashMessages } from '@/types/shared';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });

    // the session flash messages that `->with('success', ...)` sets
    router.on('success', (event) => {
        const flash = event.detail.page.props.flash as
            | FlashMessages
            | undefined;

        (['success', 'warning', 'error'] as const).forEach((type) => {
            const message = flash?.[type];

            if (message) {
                toast[type](message);
            }
        });
    });
}
