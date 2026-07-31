<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader,
    AlertDialogTitle, AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { Input } from '@/Components/ui/input';
import ShareLinkDialog from '@/Components/ShareLinkDialog.vue';
import TimesheetExportDialog from '@/Components/TimesheetExportDialog.vue';
import { Copy, FileText, Pencil, Plus, RotateCcw, Share2 } from 'lucide-vue-next';
import { formatDuration } from '@/lib/time';
import { formatMoney, amountFromSeconds } from '@/lib/money';
import { statusClass } from '@/lib/projectStatus';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    client: Object,
    share_url: { type: String, default: null },
    share_expires_at: { type: String, default: null },
});

const shareDialogOpen = ref(false);
const shareDialogMode = ref('create');
const copyState = ref('idle');
const timesheetDialogOpen = ref(false);

const openCreateShare = () => {
    shareDialogMode.value = 'create';
    shareDialogOpen.value = true;
};

const openRegenerateShare = () => {
    shareDialogMode.value = 'regenerate';
    shareDialogOpen.value = true;
};

const revokeShare = () => {
    router.delete(route('clients.share.destroy', props.client.id), {
        preserveScroll: true,
    });
};

const copyShareUrl = async () => {
    if (!props.share_url) return;
    try {
        await navigator.clipboard.writeText(props.share_url);
        copyState.value = 'copied';
        setTimeout(() => { copyState.value = 'idle'; }, 1500);
    } catch (e) {
        copyState.value = 'error';
    }
};

const shareExpiryLabel = computed(() => {
    if (!props.share_expires_at) return null;
    return new Date(props.share_expires_at).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
});

const currency = computed(() => props.client.currency || 'EUR');
const hourlyRate = computed(() => props.client.hourly_rate);

const monthAmount = computed(() => {
    if (hourlyRate.value == null) return null;
    return amountFromSeconds(props.client.total_seconds_this_month, hourlyRate.value);
});

const destroyClient = () => {
    router.delete(route('clients.destroy', props.client.id));
};

const destroyProject = (project) => {
    router.delete(route('projects.destroy', project.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="client.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-foreground">
                    {{ client.name }}
                </h2>
                <div class="flex items-center gap-3">
                    <Button variant="outline" size="sm" @click="timesheetDialogOpen = true">
                        <FileText class="mr-1 h-4 w-4" />
                        Timesheet
                    </Button>
                    <Button as-child variant="outline" size="sm">
                        <Link :href="route('clients.edit', client.id)">
                            <Pencil class="mr-1 h-4 w-4" />
                            Edit
                        </Link>
                    </Button>
                    <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <Button variant="destructive" size="sm">Delete</Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Delete {{ client.name }}?</AlertDialogTitle>
                                <AlertDialogDescription>
                                    <template v-if="client.projects.length > 0">
                                        This will also delete {{ client.projects.length }} project(s) and all of their time logs. This action cannot be undone.
                                    </template>
                                    <template v-else>
                                        This action cannot be undone.
                                    </template>
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction @click="destroyClient">Delete</AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-muted-foreground">Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                            <div>
                                <div class="text-xs text-muted-foreground">Email</div>
                                <div class="text-sm font-medium text-foreground">{{ client.email || '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground">Hourly rate</div>
                                <div class="text-sm font-medium text-foreground">
                                    <template v-if="hourlyRate != null">
                                        {{ formatMoney(hourlyRate, currency) }}/h
                                    </template>
                                    <template v-else>—</template>
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground">This month</div>
                                <div class="font-mono text-sm font-medium text-foreground tabular-nums">
                                    {{ formatDuration(client.total_seconds_this_month) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground">Total this month</div>
                                <div class="text-sm font-medium text-foreground">
                                    <template v-if="monthAmount != null">
                                        {{ formatMoney(monthAmount, currency) }}
                                    </template>
                                    <template v-else>—</template>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Projects -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Projects</CardTitle>
                        <Button as-child variant="outline" size="sm">
                            <Link :href="`${route('projects.create')}?client_id=${client.id}`">
                                <Plus class="mr-1 h-4 w-4" />
                                New project
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Title</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right">This month</TableHead>
                                    <TableHead class="text-right">All-time</TableHead>
                                    <TableHead class="text-right"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="project in client.projects" :key="project.id">
                                    <TableCell class="font-medium">
                                        <Link
                                            :href="route('projects.show', project.id)"
                                            class="text-foreground hover:underline"
                                        >
                                            {{ project.title }}
                                        </Link>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline" :class="statusClass(project.status)" class="capitalize">
                                            {{ project.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right font-mono tabular-nums">
                                        {{ formatDuration(project.total_seconds_this_month) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono tabular-nums text-muted-foreground">
                                        {{ formatDuration(project.total_seconds) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <Button as-child variant="ghost" size="sm">
                                                <Link :href="route('projects.edit', project.id)">Edit</Link>
                                            </Button>
                                            <AlertDialog>
                                                <AlertDialogTrigger as-child>
                                                    <Button variant="ghost" size="sm" class="text-destructive hover:text-destructive dark:text-red-400 dark:hover:text-red-300">
                                                        Delete
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>Delete {{ project.title }}?</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            This action cannot be undone.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction @click="destroyProject(project)">
                                                            Delete
                                                        </AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="!client.projects.length">
                                    <TableCell colspan="5" class="text-center text-muted-foreground">
                                        No projects yet.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <!-- Share -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Share with client</CardTitle>
                        <Button
                            v-if="!share_url"
                            variant="outline"
                            size="sm"
                            @click="openCreateShare"
                        >
                            <Share2 class="mr-1 h-4 w-4" />
                            Create share link
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div v-if="share_url" class="space-y-3">
                            <div class="flex items-center gap-3">
                                <Input :model-value="share_url" readonly class="font-mono text-xs" />
                                <Button
                                    variant="outline"
                                    size="icon-lg"
                                    class="shrink-0 p-3"
                                    @click="copyShareUrl"
                                    :title="copyState === 'copied' ? 'Copied!' : 'Copy link'"
                                >
                                    <Copy class="h-4 w-4" />
                                </Button>
                            </div>
                            <div class="flex items-center justify-between">
                                <p class="text-xs text-muted-foreground">
                                    <span v-if="copyState === 'copied'" class="text-foreground">Copied to clipboard.</span>
                                    <span v-else>Expires {{ shareExpiryLabel }}.</span>
                                </p>
                                <div class="flex items-center gap-1">
                                    <Button variant="ghost" size="sm" @click="openRegenerateShare">
                                        <RotateCcw class="mr-1 h-4 w-4" />
                                        Regenerate
                                    </Button>
                                    <AlertDialog>
                                        <AlertDialogTrigger as-child>
                                            <Button variant="ghost" size="sm" class="text-destructive hover:text-destructive dark:text-red-400 dark:hover:text-red-300">
                                                Revoke
                                            </Button>
                                        </AlertDialogTrigger>
                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>Revoke share link?</AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    The current link stops working immediately.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                <AlertDialogAction @click="revokeShare">Revoke</AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            Sharing is off. Create a link to let your client view every project and time entry.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>

        <ShareLinkDialog
            v-model:open="shareDialogOpen"
            :client-id="client.id"
            :mode="shareDialogMode"
            :current-expires-at="share_expires_at"
        />

        <TimesheetExportDialog
            v-model:open="timesheetDialogOpen"
            route-name="clients.timesheet"
            :route-params="{ client: client.id }"
        />
    </AuthenticatedLayout>
</template>
