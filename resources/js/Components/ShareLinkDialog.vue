<script setup>
import { ref, watch } from 'vue';
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
    mode: { type: String, default: 'create' },
    currentExpiresAt: { type: String, default: null },
});

const emit = defineEmits(['update:open']);

const expiresAt = ref('');
const errors = ref({});
const submitting = ref(false);

const toLocalInput = (input) => {
    const d = input ? new Date(input) : new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

watch(
    () => props.open,
    (open) => {
        if (!open) return;
        errors.value = {};
        if (props.currentExpiresAt) {
            expiresAt.value = toLocalInput(props.currentExpiresAt);
        } else {
            const d = new Date();
            d.setDate(d.getDate() + 30);
            expiresAt.value = toLocalInput(d);
        }
    },
);

const close = () => emit('update:open', false);

const submit = () => {
    submitting.value = true;
    router.post(
        route('projects.share.store', props.projectId),
        {
            expires_at: new Date(expiresAt.value).toISOString(),
            regenerate: props.mode === 'regenerate',
        },
        {
            preserveScroll: true,
            onSuccess: () => { close(); },
            onError: (e) => { errors.value = e; },
            onFinish: () => { submitting.value = false; },
        },
    );
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{ mode === 'regenerate' ? 'Regenerate share link' : 'Create share link' }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        mode === 'regenerate'
                            ? 'A new link will be generated. Any previously shared link stops working immediately.'
                            : 'Anyone with the link can view this project until it expires.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="expires_at">Expires</Label>
                    <Input
                        id="expires_at"
                        v-model="expiresAt"
                        type="datetime-local"
                        required
                    />
                    <p v-if="errors.expires_at" class="text-sm text-destructive">
                        {{ errors.expires_at }}
                    </p>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="close">Cancel</Button>
                    <Button type="submit" :disabled="submitting">
                        {{ mode === 'regenerate' ? 'Regenerate' : 'Create link' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
