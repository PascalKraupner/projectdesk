<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter,
    DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

const props = defineProps({
    open: Boolean,
    projectId: { type: Number, required: true },
    log: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const isEdit = computed(() => props.log !== null);

const form = ref({ started_at: '', hours: 0, minutes: 0, note: '' });
const errors = ref({});
const submitting = ref(false);

const toLocalInput = (input) => {
    const d = input ? new Date(input) : new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const durationSeconds = computed(() => {
    const h = Math.max(0, Number(form.value.hours) || 0);
    const m = Math.max(0, Number(form.value.minutes) || 0);
    return h * 3600 + m * 60;
});

watch(
    () => props.open,
    (open) => {
        if (!open) return;
        errors.value = {};
        if (props.log) {
            const total = Number(props.log.duration_seconds ?? 0);
            form.value = {
                started_at: toLocalInput(props.log.started_at),
                hours: Math.floor(total / 3600),
                minutes: Math.floor((total % 3600) / 60),
                note: props.log.note ?? '',
            };
        } else {
            const now = new Date();
            const hourAgo = new Date(now.getTime() - 60 * 60 * 1000);
            form.value = {
                started_at: toLocalInput(hourAgo),
                hours: 1,
                minutes: 0,
                note: '',
            };
        }
    },
);

const close = () => emit('update:open', false);

const localToIso = (local) => new Date(local).toISOString();

const submit = () => {
    submitting.value = true;
    const payload = {
        started_at: localToIso(form.value.started_at),
        duration_seconds: durationSeconds.value,
        note: form.value.note.trim() === '' ? null : form.value.note,
    };
    const opts = {
        preserveScroll: true,
        onSuccess: () => { close(); },
        onError: (e) => { errors.value = e; },
        onFinish: () => { submitting.value = false; },
    };
    if (isEdit.value) {
        router.patch(route('time-logs.update-manual', props.log.id), payload, opts);
    } else {
        router.post(route('time-logs.store-manual', props.projectId), payload, opts);
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ isEdit ? 'Edit time entry' : 'Add time entry' }}</DialogTitle>
                <DialogDescription>
                    {{ isEdit ? 'Adjust start, duration, or note.' : 'Log time you forgot to track.' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="started_at">Started</Label>
                    <Input
                        id="started_at"
                        v-model="form.started_at"
                        type="datetime-local"
                        required
                    />
                    <p v-if="errors.started_at" class="text-sm text-destructive">
                        {{ errors.started_at }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label>Duration</Label>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1">
                            <Input
                                id="duration_hours"
                                v-model.number="form.hours"
                                type="number"
                                min="0"
                                class="w-20"
                            />
                            <span class="text-sm text-muted-foreground">h</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <Input
                                id="duration_minutes"
                                v-model.number="form.minutes"
                                type="number"
                                min="0"
                                max="59"
                                class="w-20"
                            />
                            <span class="text-sm text-muted-foreground">m</span>
                        </div>
                    </div>
                    <p v-if="errors.duration_seconds" class="text-sm text-destructive">
                        {{ errors.duration_seconds }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="note">Note</Label>
                    <Input
                        id="note"
                        v-model="form.note"
                        placeholder="What did you work on?"
                    />
                    <p v-if="errors.note" class="text-sm text-destructive">
                        {{ errors.note }}
                    </p>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="close">Cancel</Button>
                    <Button type="submit" :disabled="submitting || durationSeconds < 1">
                        {{ isEdit ? 'Save' : 'Add entry' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
