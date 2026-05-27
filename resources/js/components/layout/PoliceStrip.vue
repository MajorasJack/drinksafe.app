<script setup lang="ts">
import { useLocalStorage } from '@vueuse/core';
import { AlertCircle, X } from 'lucide-vue-next';

const isDismissed = useLocalStorage('police-strip-dismissed', false);

const dismiss = (): void => {
    isDismissed.value = true;
};
</script>

<template>
    <div v-if="!isDismissed" class="bg-blue-600 py-2 text-white">
        <div
            class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4"
        >
            <p class="text-sm font-medium">
                <AlertCircle class="mr-2 inline size-4" />
                Need to report a crime? Contact your local police directly.
            </p>
            <button
                type="button"
                class="text-white transition-colors hover:text-blue-100"
                @click="dismiss"
            >
                <X class="size-4" />
            </button>
        </div>
    </div>
</template>
