<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter,
    DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { monthRange } from '@/lib/dateRange';

const props = defineProps({
    open: Boolean,
    // Pass one of these to pin the invoice, or neither to pick a client.
    clientId: { type: Number, default: null },
    projectId: { type: Number, default: null },
    clients: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:open']);

const form = useForm({
    client_id: props.clientId,
    project_id: props.projectId,
    ...monthRange(),
});

watch(
    () => props.open,
    (open) => {
        if (!open) return;
        form.clearErrors();
        form.client_id = props.clientId;
        form.project_id = props.projectId;
        const range = monthRange();
        form.from = range.from;
        form.to = range.to;
    },
);

const needsClientPicker = computed(() => !props.clientId && !props.projectId);

const valid = computed(() =>
    !!form.from && !!form.to && form.from <= form.to
    && (!needsClientPicker.value || !!form.client_id),
);

const close = () => emit('update:open', false);

const setRange = (offset) => {
    const range = monthRange(offset);
    form.from = range.from;
    form.to = range.to;
};

const submit = () => {
    form.post(route('invoices.store'), {
        onSuccess: () => close(),
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Create invoice</DialogTitle>
                <DialogDescription>
                    One draft item per project, built from completed time entries in
                    this period.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div v-if="needsClientPicker" class="space-y-2">
                    <Label for="client_id">Client</Label>
                    <select
                        id="client_id"
                        v-model="form.client_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option :value="null" disabled>Pick a client</option>
                        <option v-for="client in clients" :key="client.id" :value="client.id">
                            {{ client.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.client_id" class="text-sm text-destructive">
                        {{ form.errors.client_id }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="invoice_from">From</Label>
                        <Input id="invoice_from" v-model="form.from" type="date" />
                    </div>
                    <div class="space-y-2">
                        <Label for="invoice_to">To</Label>
                        <Input id="invoice_to" v-model="form.to" type="date" />
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <Button type="button" variant="ghost" size="sm" @click="setRange(0)">
                        This month
                    </Button>
                    <Button type="button" variant="ghost" size="sm" @click="setRange(-1)">
                        Last month
                    </Button>
                </div>

                <p v-if="form.errors.client_id && !needsClientPicker" class="text-sm text-destructive">
                    {{ form.errors.client_id }}
                </p>
                <p v-if="form.errors.period" class="text-sm text-destructive">
                    {{ form.errors.period }}
                </p>
                <p v-if="form.errors.project_id" class="text-sm text-destructive">
                    {{ form.errors.project_id }}
                </p>
                <p v-if="form.errors.to" class="text-sm text-destructive">
                    {{ form.errors.to }}
                </p>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="close">Cancel</Button>
                    <Button type="submit" :disabled="!valid || form.processing">
                        Create draft
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
