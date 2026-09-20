<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { AlertTriangle, Check, ShieldCheck } from 'lucide-vue-next';
import {
    computed,
    onMounted,
    onUnmounted,
    reactive,
    ref,
    watch,
    nextTick,
} from 'vue';
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
import VenueSearchInput from '@/components/venue/VenueSearchInput.vue';
import { timeOfDayFromTime } from '@/lib/timeOfDay';
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
    serverErrors?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    serverErrors: () => ({}),
});

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
            incident_time?: string;
            time_of_day: TimeOfDay;
            description: string;
            'cf-turnstile-response': string;
        },
    ): void;
}

const emit = defineEmits<Emits>();

// Single source of truth, matching StoreReportRequest's description rule.
const DESCRIPTION_MIN = 20;

// Get Turnstile site key from shared data
const page = usePage();
const turnstileSiteKey = computed(
    () => (page.props.turnstile as { siteKey: string })?.siteKey ?? '',
);

// Form state
const currentStep = ref(1);
const totalSteps = 3;

// Step 1: Venue selection
const selectedVenue = ref<Venue | null>(props.initialVenue ?? null);
const isCreatingNewVenue = ref(false);
const newVenueName = ref('');
const newVenueCity = ref('');
const newVenueAddress = ref('');

// Step 2: Incident details
const incidentDate = ref('');
const incidentTime = ref('');
const timeOfDay = ref<TimeOfDay>(TimeOfDay.Unknown);
const description = ref('');

// Tracks which fields the user has interacted with, so inline errors only
// appear after a blur or an attempt to advance — never on a pristine field.
const touched = reactive({
    venueSelection: false,
    venueName: false,
    venueCity: false,
    date: false,
    description: false,
});

// Turnstile CAPTCHA
const turnstileToken = ref('');
const turnstileWidgetId = ref<string | null>(null);
const turnstileContainerId = 'turnstile-container';

const maxDate = computed((): string => new Date().toISOString().split('T')[0]);

// Per-field validation (local rules)
const venueNameLocalError = computed((): string | null =>
    isCreatingNewVenue.value && newVenueName.value.trim() === ''
        ? 'Please enter the venue name.'
        : null,
);

const venueCityLocalError = computed((): string | null =>
    isCreatingNewVenue.value && newVenueCity.value.trim() === ''
        ? 'Please enter the city.'
        : null,
);

const venueSelectionLocalError = computed((): string | null =>
    !isCreatingNewVenue.value && selectedVenue.value === null
        ? 'Please search and select a venue, or add a new one.'
        : null,
);

const dateLocalError = computed((): string | null => {
    if (incidentDate.value === '') {
        return 'Please provide the date of the incident.';
    }

    if (incidentDate.value > maxDate.value) {
        return 'The incident date cannot be in the future.';
    }

    return null;
});

const descriptionLocalError = computed((): string | null => {
    const length = description.value.trim().length;

    if (length === 0) {
        return 'Please describe what happened.';
    }

    if (length < DESCRIPTION_MIN) {
        return `Please provide at least ${DESCRIPTION_MIN} characters (currently ${length}).`;
    }

    return null;
});

// Display errors: local rule once touched, otherwise any matching server error.
const venueNameError = computed(
    (): string | null =>
        (touched.venueName ? venueNameLocalError.value : null) ??
        props.serverErrors.venue_name ??
        null,
);

const venueCityError = computed(
    (): string | null =>
        (touched.venueCity ? venueCityLocalError.value : null) ??
        props.serverErrors.venue_city ??
        null,
);

const venueSelectionError = computed(
    (): string | null =>
        (touched.venueSelection ? venueSelectionLocalError.value : null) ??
        props.serverErrors.venue_uuid ??
        null,
);

const dateError = computed(
    (): string | null =>
        (touched.date ? dateLocalError.value : null) ??
        props.serverErrors.incident_date ??
        null,
);

const timeError = computed(
    (): string | null => props.serverErrors.incident_time ?? null,
);

const descriptionError = computed(
    (): string | null =>
        (touched.description ? descriptionLocalError.value : null) ??
        props.serverErrors.description ??
        null,
);

// Step validity
const step1Valid = computed((): boolean =>
    isCreatingNewVenue.value
        ? venueNameLocalError.value === null &&
          venueCityLocalError.value === null
        : selectedVenue.value !== null,
);

const step2Valid = computed(
    (): boolean =>
        dateLocalError.value === null && descriptionLocalError.value === null,
);

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

const turnstileVerified = computed((): boolean => turnstileToken.value !== '');

const canSubmit = computed(
    (): boolean =>
        step1Valid.value &&
        step2Valid.value &&
        !piiDetected.value &&
        turnstileVerified.value,
);

const timeOfDayOptions = [
    { value: TimeOfDay.Unknown, label: 'Not sure' },
    { value: TimeOfDay.Morning, label: 'Morning (6am - 12pm)' },
    { value: TimeOfDay.Afternoon, label: 'Afternoon (12pm - 6pm)' },
    { value: TimeOfDay.Evening, label: 'Evening (6pm - 10pm)' },
    { value: TimeOfDay.Night, label: 'Night (10pm - 6am)' },
];

const selectedTimeOfDayLabel = computed((): string => {
    return (
        timeOfDayOptions.find((option) => option.value === timeOfDay.value)
            ?.label ?? 'Not sure'
    );
});

// Venue selection handlers
const handleVenueSelect = (venue: Venue): void => {
    selectedVenue.value = venue;
    touched.venueSelection = true;
};

const handleVenueClear = (): void => {
    selectedVenue.value = null;
};

const changeVenue = (): void => {
    selectedVenue.value = null;
};

const startCreatingVenue = (): void => {
    isCreatingNewVenue.value = true;
    selectedVenue.value = null;
};

const backToSearch = (): void => {
    isCreatingNewVenue.value = false;
};

const nextStep = (): void => {
    if (currentStep.value === 1) {
        touched.venueSelection = true;
        touched.venueName = true;
        touched.venueCity = true;

        if (!step1Valid.value) {
            return;
        }
    }

    if (currentStep.value === 2) {
        touched.date = true;
        touched.description = true;

        if (!step2Valid.value) {
            return;
        }
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

    const data: Parameters<Emits>[1] = {
        incident_date: incidentDate.value,
        time_of_day: timeOfDay.value,
        description: description.value,
        'cf-turnstile-response': turnstileToken.value,
    };

    if (incidentTime.value !== '') {
        data.incident_time = incidentTime.value;
    }

    if (isCreatingNewVenue.value) {
        data.venue_name = newVenueName.value;
        data.venue_city = newVenueCity.value;
        data.venue_address = newVenueAddress.value;
    } else if (selectedVenue.value) {
        data.venue_uuid = selectedVenue.value.uuid;
    }

    emit('submit', data);
};

// An exact time is more specific than a band, so it wins whenever one is given.
// Clearing the time leaves the band alone: the user may still know it was
// "evening" without remembering the clock.
watch(incidentTime, (time): void => {
    if (time !== '') {
        timeOfDay.value = timeOfDayFromTime(time);
    }
});

// When the backend rejects a submission, jump to the step holding the offending
// field so the inline error is actually visible to the user.
watch(
    () => props.serverErrors,
    (errors): void => {
        if (errors.venue_name || errors.venue_city || errors.venue_uuid) {
            currentStep.value = 1;
        } else if (
            errors.incident_date ||
            errors.incident_time ||
            errors.description
        ) {
            currentStep.value = 2;
        }
    },
    { deep: true },
);

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
    if (!window.turnstile || !turnstileSiteKey.value) {
        return;
    }

    const container = document.getElementById(turnstileContainerId);

    if (!container) {
        return;
    }

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

onMounted(() => {
    // Only inject Cloudflare's external script when a site key is configured.
    // Without this guard the script is requested even when Turnstile is
    // disabled (e.g. in tests), leaving the page waiting on an external
    // resource that never resolves.
    if (turnstileSiteKey.value) {
        loadTurnstileScript();
    }
});

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
                            'mx-2 h-0.5 w-8 transition-colors sm:w-12',
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
                    <div
                        v-if="selectedVenue"
                        class="flex items-start justify-between gap-3 rounded-lg border border-brand-teal/40 bg-brand-teal/5 p-4"
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-teal text-white"
                            >
                                <Check class="size-4" />
                            </span>
                            <div>
                                <p
                                    class="font-medium text-slate-900 dark:text-white"
                                >
                                    {{ selectedVenue.name }}
                                </p>
                                <p
                                    class="text-sm text-slate-600 dark:text-gray-400"
                                >
                                    {{ selectedVenue.city }}
                                </p>
                            </div>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="changeVenue"
                        >
                            Change
                        </Button>
                    </div>

                    <div v-else class="space-y-3">
                        <div class="space-y-2">
                            <Label for="venue-search">Search for a venue</Label>
                            <VenueSearchInput
                                input-id="venue-search"
                                @select="handleVenueSelect"
                                @clear="handleVenueClear"
                            />
                        </div>

                        <p
                            v-if="venueSelectionError"
                            class="text-sm font-medium text-red-600 dark:text-red-400"
                        >
                            {{ venueSelectionError }}
                        </p>

                        <button
                            type="button"
                            class="text-sm font-medium text-brand-teal underline-offset-2 hover:underline"
                            @click="startCreatingVenue"
                        >
                            Can't find your venue? Add it
                        </button>
                    </div>
                </div>

                <div v-else class="space-y-4">
                    <div class="space-y-2">
                        <Label for="venue-name">Venue Name *</Label>
                        <Input
                            id="venue-name"
                            v-model="newVenueName"
                            placeholder="The Crown & Anchor"
                            :aria-invalid="!!venueNameError"
                            @blur="touched.venueName = true"
                        />
                        <p
                            v-if="venueNameError"
                            class="text-sm font-medium text-red-600 dark:text-red-400"
                        >
                            {{ venueNameError }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="venue-city">City *</Label>
                        <Input
                            id="venue-city"
                            v-model="newVenueCity"
                            placeholder="London"
                            :aria-invalid="!!venueCityError"
                            @blur="touched.venueCity = true"
                        />
                        <p
                            v-if="venueCityError"
                            class="text-sm font-medium text-red-600 dark:text-red-400"
                        >
                            {{ venueCityError }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="venue-address">Address (optional)</Label>
                        <Input
                            id="venue-address"
                            v-model="newVenueAddress"
                            placeholder="123 High Street"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        @click="backToSearch"
                    >
                        Back to search
                    </Button>
                </div>
            </div>

            <div v-else-if="currentStep === 2" class="space-y-6">
                <h3
                    class="text-xl font-semibold text-slate-900 dark:text-white"
                >
                    Incident Details
                </h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="incident-date">Date of Incident *</Label>
                        <Input
                            id="incident-date"
                            v-model="incidentDate"
                            type="date"
                            :max="maxDate"
                            class="h-11 dark:[color-scheme:dark]"
                            :aria-invalid="!!dateError"
                            @blur="touched.date = true"
                        />
                        <p
                            v-if="dateError"
                            class="text-sm font-medium text-red-600 dark:text-red-400"
                        >
                            {{ dateError }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="time-of-day">Time of Day (optional)</Label>
                        <Select v-model="timeOfDay">
                            <SelectTrigger id="time-of-day" class="h-11 w-full">
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
                </div>

                <div class="space-y-2">
                    <Label for="incident-time">Time (optional)</Label>
                    <Input
                        id="incident-time"
                        v-model="incidentTime"
                        type="time"
                        class="h-11 dark:[color-scheme:dark]"
                        :aria-invalid="!!timeError"
                    />
                    <p
                        v-if="timeError"
                        class="text-sm font-medium text-red-600 dark:text-red-400"
                    >
                        {{ timeError }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="description">Description *</Label>
                    <textarea
                        id="description"
                        v-model="description"
                        rows="6"
                        :aria-invalid="!!descriptionError"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-red-500 aria-invalid:ring-red-500/30"
                        :placeholder="`Describe what happened (minimum ${DESCRIPTION_MIN} characters)...`"
                        @blur="touched.description = true"
                    ></textarea>
                    <div class="flex items-center justify-between gap-2">
                        <p
                            v-if="descriptionError"
                            class="text-sm font-medium text-red-600 dark:text-red-400"
                        >
                            {{ descriptionError }}
                        </p>
                        <p
                            :class="[
                                'ml-auto text-xs',
                                description.trim().length >= DESCRIPTION_MIN
                                    ? 'text-brand-teal'
                                    : 'text-slate-500 dark:text-gray-400',
                            ]"
                        >
                            {{ description.trim().length }} /
                            {{ DESCRIPTION_MIN }} min
                        </p>
                    </div>
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
                            Date &amp; Time
                        </h4>
                        <p class="text-slate-600 dark:text-gray-300">
                            {{ incidentDate
                            }}<span v-if="incidentTime">
                                at {{ incidentTime }}</span
                            >
                            <span class="text-slate-400">
                                · {{ selectedTimeOfDayLabel }}</span
                            >
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
                        <ShieldCheck
                            class="size-5 text-slate-600 dark:text-gray-400"
                        />
                        <span
                            class="text-sm font-medium text-slate-700 dark:text-gray-300"
                        >
                            Security Verification
                        </span>
                    </div>
                    <div
                        :id="turnstileContainerId"
                        class="flex justify-center"
                    />
                    <Alert
                        v-if="!turnstileVerified"
                        variant="default"
                        class="border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950"
                    >
                        <AlertTriangle
                            class="size-4 text-amber-600 dark:text-amber-400"
                        />
                        <AlertDescription
                            class="text-amber-700 dark:text-amber-300"
                        >
                            Please complete the security verification above to
                            submit your report.
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
