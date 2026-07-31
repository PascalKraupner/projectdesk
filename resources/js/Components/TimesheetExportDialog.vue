<script setup>
import { computed, ref, watch } from 'vue';
import { Button } from '@/Components/ui/button';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter,
    DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Download } from 'lucide-vue-next';

const props = defineProps({
    open: Boolean,
    routeName: { type: String, required: true },
    routeParams: { type: Object, required: true },
});

const emit = defineEmits(['update:open']);

const pad = (n) => String(n).padStart(2, '0');
const toDateInput = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

const monthRange = (monthOffset = 0) => {
    const now = new Date();
    return {
        from: toDateInput(new Date(now.getFullYear(), now.getMonth() + monthOffset, 1)),
        to: toDateInput(new Date(now.getFullYear(), now.getMonth() + monthOffset + 1, 0)),
    };
};

const form = ref(monthRange());

watch(
    () => props.open,
    (open) => {
        if (open) form.value = monthRange();
    },
);

const valid = computed(() =>
    !!form.value.from && !!form.value.to && form.value.from <= form.value.to,
);

const href = computed(() => route(props.routeName, {
    ...props.routeParams,
    from: form.value.from,
    to: form.value.to,
}));

const close = () => emit('update:open', false);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Export timesheet</DialogTitle>
                <DialogDescription>
                    Completed time entries in this range, as a PDF.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="timesheet_from">From</Label>
                        <Input id="timesheet_from" v-model="form.from" type="date" />
                    </div>
                    <div class="space-y-2">
                        <Label for="timesheet_to">To</Label>
                        <Input id="timesheet_to" v-model="form.to" type="date" />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="sm" @click="form = monthRange()">
                        This month
                    </Button>
                    <Button variant="ghost" size="sm" @click="form = monthRange(-1)">
                        Last month
                    </Button>
                </div>

                <p v-if="!valid" class="text-sm text-destructive">
                    Pick a range that starts before it ends.
                </p>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="close">Cancel</Button>
                <Button v-if="valid" as-child>
                    <a :href="href" @click="close">
                        <Download class="mr-1 h-4 w-4" />
                        Download PDF
                    </a>
                </Button>
                <Button v-else disabled>
                    <Download class="mr-1 h-4 w-4" />
                    Download PDF
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
