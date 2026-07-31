<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader,
    AlertDialogTitle, AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { Copy, KeyRound, Plus } from 'lucide-vue-next';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    keys: { type: Array, default: () => [] },
});

const page = usePage();
const createdKey = computed(() => page.props.flash?.apiKey ?? null);

const showForm = ref(false);
const copyState = ref('idle');

const pad = (n) => String(n).padStart(2, '0');
const toDateInput = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

// Clamped to the end of the target month: bumping setMonth() directly would turn
// 31 Jan + 1 month into 3 Mar, so a button labelled "1 month" could hand back a
// date two months out.
const inMonths = (months) => {
    const d = new Date();
    const day = d.getDate();
    d.setDate(1);
    d.setMonth(d.getMonth() + months);
    const lastDayOfMonth = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
    d.setDate(Math.min(day, lastDayOfMonth));
    return toDateInput(d);
};

const tomorrow = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return toDateInput(d);
});

const defaults = () => ({
    name: `API key ${toDateInput(new Date())}`,
    expires_at: inMonths(1),
});

const form = useForm(defaults());

// Recomputed on open so a page left sitting overnight still offers today's date.
const openForm = () => {
    form.defaults(defaults());
    form.reset();
    form.clearErrors();
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    form.reset();
    form.clearErrors();
};

const setExpiry = (months) => {
    form.expires_at = months === null ? '' : inMonths(months);
};

const expiryLabel = computed(() => {
    if (form.expires_at === '') return 'Never expires.';
    const date = new Date(form.expires_at);
    if (Number.isNaN(date.getTime())) return '';
    return `Expires ${date.toLocaleDateString(undefined, { dateStyle: 'long' })}.`;
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        expires_at: data.expires_at === '' ? null : data.expires_at,
    })).post(route('api-keys.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.defaults(defaults());
            form.reset();
            showForm.value = false;
        },
    });
};

const copyKey = async () => {
    if (!createdKey.value) return;
    try {
        await navigator.clipboard.writeText(createdKey.value);
        copyState.value = 'copied';
        setTimeout(() => { copyState.value = 'idle'; }, 1500);
    } catch (e) {
        copyState.value = 'error';
    }
};

const revoke = (key) => {
    router.delete(route('api-keys.destroy', key.id), { preserveScroll: true });
};

const formatDateTime = (iso) =>
    iso
        ? new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
        : null;
</script>

<template>
    <Head title="API keys" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-foreground">API keys</h2>
                <Button v-if="!showForm" variant="outline" size="sm" @click="openForm">
                    <Plus class="mr-1 h-4 w-4" />
                    New key
                </Button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <!-- Newly created key, shown once -->
                <Card v-if="createdKey" class="border-primary">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm font-medium text-foreground">
                            <KeyRound class="h-4 w-4" />
                            Copy your new key now
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex items-center gap-3">
                            <Input :model-value="createdKey" readonly class="font-mono text-xs" />
                            <Button
                                variant="outline"
                                size="icon-lg"
                                class="shrink-0 p-3"
                                @click="copyKey"
                                :title="copyState === 'copied' ? 'Copied!' : 'Copy key'"
                            >
                                <Copy class="h-4 w-4" />
                            </Button>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            <span v-if="copyState === 'copied'" class="text-foreground">Copied to clipboard.</span>
                            <span v-else>
                                This is the only time the key is shown. Only the hash is stored, so it
                                cannot be recovered later. Send it as
                                <code class="font-mono">Authorization: Bearer &lt;key&gt;</code>.
                            </span>
                        </p>
                    </CardContent>
                </Card>

                <!-- Create form -->
                <Card v-if="showForm">
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-muted-foreground">New key</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="name">Name</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="Laptop CLI"
                                        required
                                    />
                                    <p v-if="form.errors.name" class="text-sm text-destructive">
                                        {{ form.errors.name }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="expires_at">Expires</Label>
                                    <Input
                                        id="expires_at"
                                        v-model="form.expires_at"
                                        type="date"
                                        :min="tomorrow"
                                    />
                                    <div class="flex flex-wrap items-center gap-1">
                                        <Button type="button" variant="ghost" size="sm" @click="setExpiry(1)">
                                            1 month
                                        </Button>
                                        <Button type="button" variant="ghost" size="sm" @click="setExpiry(3)">
                                            3 months
                                        </Button>
                                        <Button type="button" variant="ghost" size="sm" @click="setExpiry(12)">
                                            1 year
                                        </Button>
                                        <Button type="button" variant="ghost" size="sm" @click="setExpiry(null)">
                                            Never
                                        </Button>
                                    </div>
                                    <p v-if="form.errors.expires_at" class="text-sm text-destructive">
                                        {{ form.errors.expires_at }}
                                    </p>
                                    <p v-else class="text-xs text-muted-foreground">
                                        {{ expiryLabel }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button type="submit" :disabled="form.processing || !form.name">
                                    Create key
                                </Button>
                                <Button type="button" variant="outline" @click="closeForm">
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Existing keys -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-muted-foreground">Your keys</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead>Last used</TableHead>
                                    <TableHead>Expires</TableHead>
                                    <TableHead class="text-right"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="key in keys" :key="key.id">
                                    <TableCell class="font-medium">{{ key.name }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDateTime(key.created_at) }}
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDateTime(key.last_used_at) || 'Never' }}
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDateTime(key.expires_at) || 'Never' }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="text-destructive hover:text-destructive dark:text-red-400 dark:hover:text-red-300"
                                                >
                                                    Revoke
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Revoke {{ key.name }}?</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Anything using this key stops working immediately.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                    <AlertDialogAction @click="revoke(key)">
                                                        Revoke
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="!keys.length">
                                    <TableCell colspan="5" class="text-center text-muted-foreground">
                                        No API keys yet.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <p class="text-xs text-muted-foreground">
                    The API is documented at
                    <a :href="route('openapi')" class="underline hover:text-foreground">/openapi.yaml</a>.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
