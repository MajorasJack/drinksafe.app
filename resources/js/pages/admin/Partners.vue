<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AdminPartnerController from '@/actions/DrinkSafe/Partners/Controllers/AdminPartnerController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PartnerTotal = {
    code: string;
    label: string;
    destination: string;
    active: boolean;
    total: number;
};

const props = defineProps<{
    partners: PartnerTotal[];
    range: { from: string; to: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Partners',
                href: AdminPartnerController.index(),
            },
        ],
    },
});

const page = usePage();
const errors = computed(
    () => (page.props.errors as Record<string, string> | undefined) ?? {},
);

const form = reactive({ from: props.range.from, to: props.range.to });

const showRange = (): void =>
    router.get(AdminPartnerController.index.url(), form, {
        preserveState: true,
    });
</script>

<template>
    <Head title="Partners" />

    <div class="flex flex-col gap-8 p-4">
        <section class="space-y-4">
            <Heading
                title="Partners"
                description="Scans per partner link, from the daily totals. Both dates are included, in UK dates."
            />

            <form
                class="flex flex-wrap items-end gap-4 rounded-xl border p-4"
                @submit.prevent="showRange"
            >
                <div class="grid gap-2">
                    <Label for="from">From</Label>
                    <Input id="from" v-model="form.from" type="date" />
                    <InputError :message="errors.from" />
                </div>
                <div class="grid gap-2">
                    <Label for="to">To</Label>
                    <Input id="to" v-model="form.to" type="date" />
                    <InputError :message="errors.to" />
                </div>
                <Button type="submit">Show totals</Button>
            </form>

            <p
                v-if="partners.length === 0"
                class="text-sm text-muted-foreground"
            >
                There are no partner links yet. Add one with
                <code>drinksafe:partners:add</code>.
            </p>

            <ul v-else class="divide-y rounded-xl border">
                <li
                    v-for="partner in partners"
                    :key="partner.code"
                    class="space-y-3 p-4"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ partner.code }}</span>
                            <Badge v-if="partner.active">On</Badge>
                            <Badge v-else variant="secondary">Off</Badge>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-sm">
                                {{ partner.total }}
                                {{ partner.total === 1 ? 'scan' : 'scans' }}
                            </span>
                            <Form
                                v-bind="
                                    AdminPartnerController.update.form(
                                        partner.code,
                                    )
                                "
                                :transform="() => ({ active: !partner.active })"
                                :options="{ preserveScroll: true }"
                                #default="{ processing }"
                            >
                                <Button
                                    type="submit"
                                    variant="outline"
                                    size="sm"
                                    :disabled="processing"
                                >
                                    {{
                                        partner.active
                                            ? 'Switch off'
                                            : 'Switch on'
                                    }}
                                </Button>
                            </Form>
                        </div>
                    </div>

                    <Form
                        v-bind="
                            AdminPartnerController.update.form(partner.code)
                        "
                        :options="{ preserveScroll: true }"
                        class="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                        #default="{ errors: rowErrors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label :for="`label-${partner.code}`">Label</Label>
                            <Input
                                :id="`label-${partner.code}`"
                                name="label"
                                :default-value="partner.label"
                                required
                            />
                            <InputError :message="rowErrors.label" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`destination-${partner.code}`">
                                Destination
                            </Label>
                            <Input
                                :id="`destination-${partner.code}`"
                                name="destination"
                                :default-value="partner.destination"
                                required
                            />
                            <InputError :message="rowErrors.destination" />
                        </div>
                        <Button type="submit" :disabled="processing">
                            Save
                        </Button>
                    </Form>
                </li>
            </ul>
        </section>
    </div>
</template>
