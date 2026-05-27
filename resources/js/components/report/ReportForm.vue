<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { AlertTriangle, ShieldCheck } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { TimeOfDay } from '@/types/report';
import type { Venue } from '@/types/venue';

declare global {
    interface Window {
        turnstile: {
            render: (
                container: string | HTMLElement,
                options: {
                    sitekey: string;
                    callback?: (token: string) => void;
                    'expired-callback'?: () => void;
                    'error-callback'?: () => void;
                    theme?: 'light' | 'dark' | 'auto';
                },
            ) => string;
            reset: (widgetId: string) => void;
            remove: (widgetId: string) => void;
        };
        onTurnstileLoad?: () => void;
    }
}

interface Props {
    initialVenue?: Venue;
}

const props = defineProps<Props>();

interface Emits {
    (
        e: 'submit',
        data: {
            venue_uuid?: string;
            venue_name?: string;
            venue_city?: string;
            venue_address?: string;
            latitude?: number;
            longitude?: number;
            incident_date: string;
            time_of_day: TimeOfDay;
            description: string;
            'cf-turnstile-response': string;
        },
    ): void;
}

// Get Turnstile site key from shared data
const page = usePage();
const turnstileSiteKey = computed(
    () => (page.props.turnstile as { siteKey: string })?.siteKey ?? '',
);

const emit = defineEmits<Emits>();

// Form state
const currentStep = ref(1);
const totalSteps = 3;

// Step 1: Venue selection
const venueSearchQuery = ref('');
const selectedVenue = ref<Venue | null>(props.initialVenue || null);
const isCreatingNewVenue = ref(false);
const newVenueName = ref('');
const newVenueCity = ref('');
const newVenueAddress = ref('');

// Step 2: Incident details
const incidentDate = ref('');
const timeOfDay = ref<TimeOfDay>(TimeOfDay.Unknown);
const description = ref('');

// Turnstile CAPTCHA
const turnstileToken = ref('');
const turnstileWidgetId = ref<string | null>(null);
const turnstileContainerId = 'turnstile-container';

const loadTurnstileScript = (): Promise<void> => {
    return new Promise((resolve) => {
        if (window.turnstile) {
            resolve();
            return;
        }

        const existingScript = document.querySelector(
            'script[src*="challenges.cloudflare.com/turnstile"]',
        );
        if (existingScript) {
            window.onTurnstileLoad = () => resolve();
            return;
        }

        window.onTurnstileLoad = () => resolve();

        const script = document.createElement('script');
        script.src =
            'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad&render=explicit';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    });
};

const renderTurnstileWidget = (): void => {
    if (!window.turnstile || !turnstileSiteKey.value) return;

    const container = document.getElementById(turnstileContainerId);
    if (!container) return;

    // Remove existing widget if present
    if (turnstileWidgetId.value) {
        window.turnstile.remove(turnstileWidgetId.value);
    }

    turnstileWidgetId.value = window.turnstile.render(container, {
        sitekey: turnstileSiteKey.value,
        callback: (token: string) => {
            turnstileToken.value = token;
        },
        'expired-callback': () => {
            turnstileToken.value = '';
        },
        'error-callback': () => {
            turnstileToken.value = '';
        },
        theme: 'auto',
    });
};

// Load Turnstile script on mount
onMounted(() => {
    loadTurnstileScript();
});

// Render widget when step 3 becomes active
watch(
    () => currentStep.value,
    async (step) => {
        if (step === 3) {
            await nextTick();
            renderTurnstileWidget();
        }
    },
);

onUnmounted(() => {
    if (turnstileWidgetId.value && window.turnstile) {
        window.turnstile.remove(turnstileWidgetId.value);
    }
});

// Validation
const step1Valid = computed((): boolean => {
    if (isCreatingNewVenue.value) {
        return (
            newVenueName.value.trim() !== '' && newVenueCity.value.trim() !== ''
        );
    }

    return selectedVenue.value !== null;
});

const step2Valid = computed((): boolean => {
    const today = new Date().toISOString().split('T')[0];

    return (
        incidentDate.value !== '' &&
        incidentDate.value <= today &&
        timeOfDay.value !== TimeOfDay.Unknown &&
        description.value.trim().length >= 10
    );
});

// PII Detection
const piiDetected = computed((): boolean => {
    const text = description.value.toLowerCase();
    const emailPattern = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/;
    const phonePattern = /\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/;
    const urlPattern = /https?:\/\/[^\s]+/;

    return (
        emailPattern.test(text) ||
        phonePattern.test(text) ||
        urlPattern.test(text)
    );
});

// Check if turnstile is verified
const turnstileVerified = computed((): boolean => turnstileToken.value !== '');

// Can submit when all validations pass
const canSubmit = computed(
    (): boolean =>
        step1Valid.value &&
        step2Valid.value &&
        !piiDetected.value &&
        turnstileVerified.value,
);

const timeOfDayOptions = [
    { value: TimeOfDay.Morning, label: 'Morning (6am - 12pm)' },
    { value: TimeOfDay.Afternoon, label: 'Afternoon (12pm - 6pm)' },
    { value: TimeOfDay.Evening, label: 'Evening (6pm - 10pm)' },
    { value: TimeOfDay.Night, label: 'Night (10pm - 6am)' },
    { value: TimeOfDay.Unknown, label: 'Unknown' },
];

const nextStep = (): void => {
    if (currentStep.value === 1 && !step1Valid.value) {
        return;
    }

    if (currentStep.value === 2 && !step2Valid.value) {
        return;
    }

    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
};

const previousStep = (): void => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const handleSubmit = (): void => {
    if (!canSubmit.value) {
        return;
    }

    const data: {
        venue_uuid?: string;
        venue_name?: string;
        venue_city?: string;
        venue_address?: string;
        latitude?: number;
        longitude?: number;
        incident_date: string;
        time_of_day: TimeOfDay;
        description: string;
        'cf-turnstile-response': string;
    } = {
        incident_date: incidentDate.value,
        time_of_day: timeOfDay.value,
        description: description.value,
        'cf-turnstile-response': turnstileToken.value,
    };

    if (isCreatingNewVenue.value) {
        data.venue_name = newVenueName.value;
        data.venue_city = newVenueCity.value;
        data.venue_address = newVenueAddress.value;
    } else if (selectedVenue.value) {
        data.venue_uuid = selectedVenue.value.uuid;
    }

    emit('submit', data);
};

const maxDate = computed((): string => {
    return new Date().toISOString().split('T')[0];
});
</script>

<template>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div class="flex gap-2">
                <div
                    v-for="step in totalSteps"
                    :key="step"
                    class="flex items-center"
                >
                    <div
                        :class="[
                            'flex size-8 items-center justify-center rounded-full text-sm font-medium transition-colors',
                            step === currentStep
                                ? 'bg-brand-teal text-white'
                                : step < currentStep
                                  ? 'bg-brand-teal/20 text-brand-teal'
                                  : 'bg-slate-200 text-slate-600 dark:bg-gray-700 dark:text-gray-400',
                        ]"
                    >
                        {{ step }}
                    </div>
                    <div
                        v-if="step < totalSteps"
                        :class="[
                            'mx-2 h-0.5 w-12 transition-colors',
                            step < currentStep
                                ? 'bg-brand-teal'
                                : 'bg-slate-200 dark:bg-gray-700',
                        ]"
                    />
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-gray-400">
                Step {{ currentStep }} of {{ totalSteps }}
            </p>
        </div>

        <div class="space-y-6">
            <div v-if="currentStep === 1" class="space-y-6">
                <h3
                    class="text-xl font-semibold text-slate-900 dark:text-white"
                >
                    Select Venue
                </h3>

                <div v-if="!isCreatingNewVenue" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="venue-search">Search for a venue</Label>
                        <Input
                            id="venue-search"
                            v-model="venueSearchQuery"
                            placeholder="Start typing venue name..."
                        />
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        @click="isCreatingNewVenue = true"
                    >
                        Create New Venue
                    </Button>
                </div>

                <div v-else class="space-y-4">
                    <div class="space-y-2">
                        <Label for="venue-name">Venue Name *</Label>
                        <Input
                            id="venue-name"
                            v-model="newVenueName"
                            placeholder="The Crown & Anchor"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="venue-city">City *</Label>
                        <Input
                            id="venue-city"
                            v-model="newVenueCity"
                            placeholder="London"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="venue-address">Address</Label>
                        <Input
                            id="venue-address"
                            v-model="newVenueAddress"
                            placeholder="123 High Street"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        @click="isCreatingNewVenue = false"
                    >
                        Back to Search
                    </Button>
                </div>
            </div>

            <div v-else-if="currentStep === 2" class="space-y-6">
                <h3
                    class="text-xl font-semibold text-slate-900 dark:text-white"
                >
                    Incident Details
                </h3>

                <div class="space-y-2">
                    <Label for="incident-date">Date of Incident *</Label>
                    <Input
                        id="incident-date"
                        v-model="incidentDate"
                        type="date"
                        :max="maxDate"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="time-of-day">Time of Day *</Label>
                    <Select v-model="timeOfDay">
                        <SelectTrigger id="time-of-day">
                            <SelectValue placeholder="Select time of day" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem
                                    v-for="option in timeOfDayOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <Label for="description">Description *</Label>
                    <textarea
                        id="description"
                        v-model="description"
                        rows="6"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Describe what happened (minimum 10 characters)..."
                    ></textarea>
                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        {{ description.length }} characters (minimum 10)
                    </p>
                </div>

                <Alert v-if="piiDetected" variant="destructive">
                    <AlertTriangle class="size-4" />
                    <AlertTitle>Personal Information Detected</AlertTitle>
                    <AlertDescription>
                        Please remove any personal information such as emails,
                        phone numbers, or URLs from your description.
                    </AlertDescription>
                </Alert>

                <Alert>
                    <AlertTriangle class="size-4" />
                    <AlertTitle>Privacy Notice</AlertTitle>
                    <AlertDescription>
                        Do not include any personal information that could
                        identify you or others. Keep your report anonymous.
                    </AlertDescription>
                </Alert>
            </div>

            <div v-else-if="currentStep === 3" class="space-y-6">
                <h3
                    class="text-xl font-semibold text-slate-900 dark:text-white"
                >
                    Review & Submit
                </h3>

                <div
                    class="space-y-4 rounded-lg border p-6 dark:border-gray-700"
                >
                    <div>
                        <h4
                            class="mb-1 font-medium text-slate-900 dark:text-white"
                        >
                            Venue
                        </h4>
                        <p class="text-slate-600 dark:text-gray-300">
                            <span v-if="isCreatingNewVenue">
                                {{ newVenueName }} - {{ newVenueCity }}
                                <span v-if="newVenueAddress">
                                    , {{ newVenueAddress }}
                                </span>
                            </span>
                            <span v-else-if="selectedVenue">
                                {{ selectedVenue.name }} -
                                {{ selectedVenue.city }}
                            </span>
                        </p>
                    </div>

                    <div>
                        <h4
                            class="mb-1 font-medium text-slate-900 dark:text-white"
                        >
                            Date
                        </h4>
                        <p class="text-slate-600 dark:text-gray-300">
                            {{ incidentDate }}
                        </p>
                    </div>

                    <div>
                        <h4
                            class="mb-1 font-medium text-slate-900 dark:text-white"
                        >
                            Time of Day
                        </h4>
                        <p class="text-slate-600 dark:text-gray-300">
                            {{ timeOfDay }}
                        </p>
                    </div>

                    <div>
                        <h4
                            class="mb-1 font-medium text-slate-900 dark:text-white"
                        >
                            Description
                        </h4>
                        <p
                            class="whitespace-pre-wrap text-slate-600 dark:text-gray-300"
                        >
                            {{ description }}
                        </p>
                    </div>
                </div>

                <Alert v-if="piiDetected" variant="destructive">
                    <AlertTriangle class="size-4" />
                    <AlertTitle>Cannot Submit</AlertTitle>
                    <AlertDescription>
                        Personal information detected. Please go back and remove
                        any emails, phone numbers, or URLs.
                    </AlertDescription>
                </Alert>

                <!-- Turnstile CAPTCHA -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="size-5 text-slate-600 dark:text-gray-400" />
                        <span class="text-sm font-medium text-slate-700 dark:text-gray-300">
                            Security Verification
                        </span>
                    </div>
                    <div :id="turnstileContainerId" class="flex justify-center" />
                    <Alert v-if="!turnstileVerified" variant="default" class="border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950">
                        <AlertTriangle class="size-4 text-amber-600 dark:text-amber-400" />
                        <AlertDescription class="text-amber-700 dark:text-amber-300">
                            Please complete the security verification above to submit your report.
                        </AlertDescription>
                    </Alert>
                </div>
            </div>
        </div>

        <div class="flex justify-between border-t pt-4">
            <Button
                type="button"
                variant="outline"
                :disabled="currentStep === 1"
                @click="previousStep"
            >
                Back
            </Button>

            <Button
                v-if="currentStep < totalSteps"
                type="button"
                :disabled="
                    (currentStep === 1 && !step1Valid) ||
                    (currentStep === 2 && !step2Valid)
                "
                @click="nextStep"
            >
                Next
            </Button>

            <Button
                v-else
                type="button"
                :disabled="!canSubmit"
                @click="handleSubmit"
            >
                Submit Report
            </Button>
        </div>
    </div>
</template>
