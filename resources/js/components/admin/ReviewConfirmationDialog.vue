<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { ReviewConfirmation } from '@/types/report';

defineProps<{
    confirmation: ReviewConfirmation | null;
    isSubmitting: boolean;
}>();

const emit = defineEmits<{ close: [] }>();
</script>

<template>
    <Dialog
        :open="confirmation !== null"
        @update:open="(isOpen) => !isOpen && emit('close')"
    >
        <DialogContent v-if="confirmation">
            <DialogHeader>
                <DialogTitle>{{ confirmation.title }}</DialogTitle>
                <DialogDescription>
                    {{ confirmation.description }}
                </DialogDescription>
            </DialogHeader>

            <ul
                v-if="confirmation.affectedVenues.length > 1"
                class="list-disc pl-5 text-sm"
            >
                <li
                    v-for="venue in confirmation.affectedVenues"
                    :key="venue.uuid"
                >
                    {{ venue.label }}
                </li>
            </ul>
            <p
                v-else-if="confirmation.affectedVenues.length === 1"
                class="text-sm font-medium"
            >
                {{ confirmation.affectedVenues[0].label }}
            </p>

            <DialogFooter class="gap-2">
                <template v-if="confirmation.affectedVenues.length > 0">
                    <Button
                        variant="outline"
                        :disabled="isSubmitting"
                        @click="confirmation.submit(false)"
                    >
                        Keep the venue listed
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="isSubmitting"
                        @click="confirmation.submit(true)"
                    >
                        Hide the venue too
                    </Button>
                </template>
                <template v-else>
                    <DialogClose as-child>
                        <Button variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        :disabled="isSubmitting"
                        @click="confirmation.submit(false)"
                    >
                        {{ confirmation.confirmLabel }}
                    </Button>
                </template>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
