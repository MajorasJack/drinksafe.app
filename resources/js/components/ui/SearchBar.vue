<script setup lang="ts">
import { Search, X } from 'lucide-vue-next';

interface Props {
    modelValue: string;
    placeholder?: string;
}

withDefaults(defineProps<Props>(), {
    placeholder: 'Search...',
});

interface Emits {
    (e: 'update:modelValue', value: string): void;
    (e: 'search'): void;
}

const emit = defineEmits<Emits>();

const handleInput = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
};

const handleClear = (): void => {
    emit('update:modelValue', '');
};
</script>

<template>
    <div class="relative">
        <Search
            class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-slate-400"
        />
        <input
            :value="modelValue"
            type="text"
            :placeholder="placeholder"
            class="w-full rounded-xl border border-slate-200 py-3 pl-10 pr-10 outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/20"
            @input="handleInput"
            @keyup.enter="emit('search')"
        />
        <button
            v-if="modelValue"
            type="button"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
            @click="handleClear"
        >
            <X class="size-5" />
        </button>
    </div>
</template>
