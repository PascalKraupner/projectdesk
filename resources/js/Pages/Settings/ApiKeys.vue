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

const form = useForm({
    name: '',
    expires_at: '',
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        expires_at: data.expires_at === '' ? null : data.expires_at,
    })).post(route('api-keys.store'), {
        preserveScroll: true,
        onSuccess: () => {
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
                <Button v-if="!showForm" variant="outline" size="sm" @click="showForm = true">
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
                                    <Label for="expires_at">Expires (optional)</Label>
                                    <Input id="expires_at" v-model="form.expires_at" type="date" />
                                    <p v-if="form.errors.expires_at" class="text-sm text-destructive">
                                        {{ form.errors.expires_at }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button type="submit" :disabled="form.processing || !form.name">
                                    Create key
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="showForm = false; form.reset()"
                                >
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
