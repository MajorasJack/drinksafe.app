<script setup lang="ts">
import { Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Input from '@/components/ui/input/Input.vue';
import { useSearch } from '@/composables/useSearch';
import type { Venue } from '@/types/venue';

interface Props {
    inputId?: string;
}

interface Emits {
    (e: 'select', venue: Venue): void;
    (e: 'clear'): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

const { query, results, isSearching, search } = useSearch();
const showDropdown = ref(false);
const inputRef = ref<HTMLInputElement | null>(null);

watch(query, (newQuery) => {
    if (newQuery.trim()) {
        search(newQuery);
        showDropdown.value = true;
    } else {
        showDropdown.value = false;
        emit('clear');
    }
});

const handleSelect = (venue: Venue): void => {
    query.value = venue.name;
    showDropdown.value = false;
    emit('select', venue);
};

const handleClear = (): void => {
    query.value = '';
    showDropdown.value = false;
    emit('clear');
};

const handleBlur = (): void => {
    setTimeout(() => {
        showDropdown.value = false;
    }, 200);
};
</script>

<template>
    <div class="relative w-full">
        <div class="relative">
            <Search
                class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400"
            />
            <Input
                :id="inputId"
                ref="inputRef"
                v-model="query"
                type="text"
                placeholder="Search by city, town, or venue name..."
                class="w-full pr-9 pl-9"
                @focus="showDropdown = !!query.trim()"
                @blur="handleBlur"
            />
            <button
                v-if="query"
                type="button"
                class="absolute top-1/2 right-3 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                @click="handleClear"
            >
                <X class="size-4" />
            </button>
        </div>

        <!-- Dropdown -->
        <div
            v-if="showDropdown && (isSearching || results.length > 0)"
            class="absolute z-50 mt-2 w-full rounded-md border border-slate-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            <div
                v-if="isSearching"
                class="px-4 py-3 text-sm text-slate-500 dark:text-gray-400"
            >
                Searching...
            </div>

            <div
                v-else-if="results.length === 0"
                class="px-4 py-3 text-sm text-slate-500 dark:text-gray-400"
            >
                No venues found
            </div>

            <div v-else class="max-h-64 overflow-y-auto">
                <button
                    v-for="venue in results"
                    :key="venue.uuid"
                    type="button"
                    class="w-full border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-slate-50 dark:border-gray-700 dark:hover:bg-gray-700"
                    @click="handleSelect(venue)"
                >
                    <div class="font-medium text-slate-900 dark:text-white">
                        {{ venue.name }}
                    </div>
                    <div
                        class="mt-0.5 text-sm text-slate-500 dark:text-gray-400"
                    >
                        {{ venue.city }}
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>
