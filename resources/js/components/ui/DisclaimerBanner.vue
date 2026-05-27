<script setup lang="ts">
import { AlertTriangle, X } from 'lucide-vue-next';

interface Props {
    dismissible?: boolean;
}

withDefaults(defineProps<Props>(), {
    dismissible: false,
});

interface Emits {
    (e: 'dismissed'): void;
}

const emit = defineEmits<Emits>();
</script>

<template>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/50 dark:bg-amber-900/20">
        <div class="flex items-start gap-3">
            <AlertTriangle
                class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400"
            />
            <div class="flex-grow">
                <h4 class="mb-1 font-semibold text-amber-900 dark:text-amber-200">
                    Important Notice
                </h4>
                <p class="text-sm text-amber-800 dark:text-amber-300">
                    Drink Safe is an informational platform. This does not
                    replace reporting to authorities. If you have experienced a
                    crime, please contact the police directly.
                </p>
            </div>
            <button
                v-if="dismissible"
                type="button"
                class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                @click="emit('dismissed')"
            >
                <X class="size-5" />
            </button>
        </div>
    </div>
</template>
