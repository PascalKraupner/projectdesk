<script setup>
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
import { ProjectStatus } from '@/Enums/ProjectStatus';
import { Input } from '@/Components/ui/input';
import ManualTimeEntryDialog from '@/Components/ManualTimeEntryDialog.vue';
import TimesheetExportDialog from '@/Components/TimesheetExportDialog.vue';
import { Download, FileText, Pause, Pencil, Play, Plus, Square, Trash2 } from 'lucide-vue-next';
import { formatDuration, liveSeconds as computeLiveSeconds } from '@/lib/time';
import { statusClass } from '@/lib/projectStatus';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    project: Object,
});

const page = usePage();
const runningTimer = computed(() => page.props.runningTimer);
const runningElsewhere = computed(() =>
    runningTimer.value && runningTimer.value.project_id !== props.project.id
        ? runningTimer.value
        : null,
);
const isActive = computed(() => props.project.status === ProjectStatus.Active);
const startDisabledReason = computed(() => {
    if (runningElsewhere.value) return 'elsewhere';
    if (!isActive.value) return 'inactive';
    return null;
});

const now = ref(Date.now());
let tickHandle = null;

onMounted(() => {
    tickHandle = setInterval(() => { now.value = Date.now(); }, 1000);
});
onUnmounted(() => clearInterval(tickHandle));

const runningLog = computed(() =>
    props.project.time_logs.find((l) => l.ended_at === null) ?? null,
);

const isPaused = computed(() => !!runningLog.value && !runningLog.value.last_resumed_at);
const isTicking = computed(() => !!runningLog.value && !!runningLog.value.last_resumed_at);

const completedLogs = computed(() =>
    props.project.time_logs.filter((l) => l.ended_at !== null),
);

const liveSeconds = computed(() => {
    if (!runningLog.value) return 0;
    return computeLiveSeconds(runningLog.value, now.value);
});

const display = computed(() =>
    runningLog.value ? formatDuration(liveSeconds.value) : '00:00:00',
);

const totalSeconds = computed(() => {
    let total = 0;
    for (const log of props.project.time_logs) {
        if (runningLog.value && log.id === runningLog.value.id) {
            total += liveSeconds.value;
        } else {
            total += log.duration_seconds ?? 0;
        }
    }
    return total;
});

const formatDateTime = (iso) =>
    new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });

const pendingNote = ref('');

const start = () => {
    const note = pendingNote.value.trim() === '' ? null : pendingNote.value;
    router.post(route('time-logs.store', props.project.id), { note }, {
        preserveScroll: true,
        onSuccess: () => { pendingNote.value = ''; },
    });
};

const stop = () => {
    if (!runningLog.value) return;
    router.patch(route('time-logs.update', runningLog.value.id), {}, {
        preserveScroll: true,
    });
};

const pause = () => {
    if (!isTicking.value) return;
    router.patch(route('time-logs.pause', runningLog.value.id), {}, {
        preserveScroll: true,
    });
};

const resumeRunning = () => {
    if (!isPaused.value) return;
    router.patch(route('time-logs.resume', runningLog.value.id), {}, {
        preserveScroll: true,
    });
};

const resumeLog = (log) => {
    if (runningTimer.value) return;
    router.patch(route('time-logs.resume', log.id), {}, {
        preserveScroll: true,
    });
};

const destroy = (log) => {
    router.delete(route('time-logs.destroy', log.id), {
        preserveScroll: true,
    });
};

const editingNoteId = ref(null);
const noteDraft = ref('');
const noteInputRef = ref(null);

const startEditingNote = async (log) => {
    editingNoteId.value = log.id;
    noteDraft.value = log.note ?? '';
    await nextTick();
    noteInputRef.value?.$el?.focus?.() ?? noteInputRef.value?.focus?.();
};

const cancelEditingNote = () => {
    editingNoteId.value = null;
    noteDraft.value = '';
};

const saveNote = (log) => {
    if (editingNoteId.value !== log.id) return;
    const next = noteDraft.value.trim() === '' ? null : noteDraft.value;
    editingNoteId.value = null;
    if (next === (log.note ?? null)) {
        noteDraft.value = '';
        return;
    }
    router.patch(route('time-logs.update-note', log.id), { note: next }, {
        preserveScroll: true,
        onSuccess: () => { noteDraft.value = ''; },
    });
};

const destroyProject = () => {
    router.delete(route('projects.destroy', props.project.id));
};

const manualDialogOpen = ref(false);
const manualDialogLog = ref(null);
const timesheetDialogOpen = ref(false);

const openAddEntry = () => {
    manualDialogLog.value = null;
    manualDialogOpen.value = true;
};

const openEditEntry = (log) => {
    manualDialogLog.value = log;
    manualDialogOpen.value = true;
};

</script>

<template>
    <Head :title="project.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-semibold leading-tight text-foreground">
                        {{ project.title }}
                    </h2>
                    <Badge variant="outline" :class="statusClass(project.status)" class="capitalize">
                        {{ project.status }}
                    </Badge>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="project.client"
                        :href="route('clients.show', project.client.id)"
                        class="text-sm text-muted-foreground hover:text-foreground hover:underline"
                    >
                        {{ project.client.name }}
                    </Link>
                    <Button variant="outline" size="sm" @click="timesheetDialogOpen = true">
                        <FileText class="mr-1 h-4 w-4" />
                        Timesheet
                    </Button>
                    <Button as-child variant="outline" size="sm" title="Export all completed time logs as CSV">
                        <a :href="route('time-logs.export', project.id)">
                            <Download class="mr-1 h-4 w-4" />
                            CSV
                        </a>
                    </Button>
                    <Button as-child variant="outline" size="sm">
                        <Link :href="route('projects.edit', project.id)">Edit</Link>
                    </Button>
                    <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <Button variant="destructive" size="sm">Delete</Button>
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
                                <AlertDialogAction @click="destroyProject">Delete</AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Timer -->
                    <Card class="lg:col-span-2">
                        <CardContent class="flex flex-col items-center py-12">
                            <div
                                class="font-mono text-7xl font-light tracking-wider tabular-nums"
                                :class="isTicking ? 'text-primary' : (isPaused ? 'text-muted-foreground' : 'text-foreground')"
                            >
                                {{ display }}
                            </div>

                            <div v-if="isPaused" class="mt-2 text-xs uppercase tracking-wide text-muted-foreground">
                                Paused
                            </div>

                            <Separator class="my-8 w-24" />

                            <div class="mb-6 w-full max-w-md">
                                <template v-if="runningLog">
                                    <Input
                                        v-if="editingNoteId === runningLog.id"
                                        ref="noteInputRef"
                                        v-model="noteDraft"
                                        class="h-9 text-center"
                                        placeholder="What are you working on?"
                                        @keydown.enter.prevent="saveNote(runningLog)"
                                        @keydown.esc.prevent="cancelEditingNote"
                                        @blur="saveNote(runningLog)"
                                    />
                                    <button
                                        v-else
                                        type="button"
                                        class="block w-full text-center text-sm text-muted-foreground hover:text-foreground"
                                        @click="startEditingNote(runningLog)"
                                    >
                                        {{ runningLog.note || 'What are you working on?' }}
                                    </button>
                                </template>
                                <Input
                                    v-else
                                    v-model="pendingNote"
                                    class="h-9 text-center"
                                    placeholder="What are you working on?"
                                    @keydown.enter.prevent="start"
                                />
                            </div>

                            <div class="flex flex-col items-center gap-3">
                                <div class="flex items-center gap-3">
                                    <Button
                                        v-if="!runningLog"
                                        @click="start"
                                        size="lg"
                                        class="h-14 w-14 rounded-full"
                                        :disabled="!!startDisabledReason"
                                        title="Start timer"
                                    >
                                        <Play class="h-6 w-6" />
                                    </Button>
                                    <template v-else>
                                        <Button
                                            v-if="isPaused"
                                            @click="resumeRunning"
                                            size="lg"
                                            class="h-14 w-14 rounded-full"
                                            title="Resume timer"
                                        >
                                            <Play class="h-6 w-6" />
                                        </Button>
                                        <Button
                                            v-else
                                            @click="pause"
                                            size="lg"
                                            class="h-14 w-14 rounded-full"
                                            title="Pause timer"
                                        >
                                            <Pause class="h-6 w-6" />
                                        </Button>
                                        <Button
                                            @click="stop"
                                            variant="outline"
                                            size="lg"
                                            class="h-14 w-14 rounded-full"
                                            title="Stop timer"
                                        >
                                            <Square class="h-6 w-6" />
                                        </Button>
                                    </template>
                                </div>
                                <p
                                    v-if="!runningLog && startDisabledReason === 'elsewhere'"
                                    class="text-xs text-muted-foreground"
                                >
                                    Timer running on
                                    <Link
                                        :href="route('projects.show', runningElsewhere.project_id)"
                                        class="font-medium text-foreground hover:underline"
                                    >
                                        {{ runningElsewhere.project_title }}
                                    </Link>
                                </p>
                                <p
                                    v-else-if="!runningLog && startDisabledReason === 'inactive'"
                                    class="text-xs text-muted-foreground capitalize"
                                >
                                    Project is {{ project.status }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Project Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium text-muted-foreground">Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <div class="text-xs text-muted-foreground">Client</div>
                                <div class="text-sm font-medium text-foreground">
                                    <Link
                                        v-if="project.client"
                                        :href="route('clients.show', project.client.id)"
                                        class="hover:underline"
                                    >
                                        {{ project.client.name }}
                                    </Link>
                                    <span v-else>—</span>
                                </div>
                            </div>
                            <Separator />
                            <div>
                                <div class="text-xs text-muted-foreground">Status</div>
                                <Badge variant="outline" :class="statusClass(project.status)" class="mt-1 capitalize">
                                    {{ project.status }}
                                </Badge>
                            </div>
                            <Separator />
                            <div>
                                <div class="text-xs text-muted-foreground">Total time</div>
                                <div class="font-mono text-sm font-medium text-foreground tabular-nums">
                                    {{ formatDuration(totalSeconds) }}
                                </div>
                            </div>
                            <Separator />
                            <div>
                                <div class="text-xs text-muted-foreground">Created</div>
                                <div class="text-sm text-foreground">
                                    {{ new Date(project.created_at).toLocaleDateString() }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Time logs -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Time logs</CardTitle>
                        <Button variant="outline" size="sm" @click="openAddEntry">
                            <Plus class="mr-1 h-4 w-4" />
                            Add entry
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Started</TableHead>
                                    <TableHead>Duration</TableHead>
                                    <TableHead>Note</TableHead>
                                    <TableHead class="text-right"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="log in completedLogs" :key="log.id">
                                    <TableCell class="text-sm">{{ formatDateTime(log.started_at) }}</TableCell>
                                    <TableCell class="font-mono tabular-nums">
                                        {{ formatDuration(log.duration_seconds) }}
                                    </TableCell>
                                    <TableCell class="text-sm">
                                        <Input
                                            v-if="editingNoteId === log.id"
                                            ref="noteInputRef"
                                            v-model="noteDraft"
                                            class="h-8"
                                            placeholder="Add a note"
                                            @keydown.enter.prevent="saveNote(log)"
                                            @keydown.esc.prevent="cancelEditingNote"
                                            @blur="saveNote(log)"
                                        />
                                        <button
                                            v-else
                                            type="button"
                                            class="w-full text-left text-muted-foreground hover:text-foreground"
                                            @click="startEditingNote(log)"
                                        >
                                            {{ log.note || 'Add a note' }}
                                        </button>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                @click="resumeLog(log)"
                                                :disabled="!!runningTimer"
                                                :title="runningTimer ? 'Stop the active timer first' : 'Resume this entry'"
                                            >
                                                <Play class="h-4 w-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon-sm" @click="openEditEntry(log)">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                            <AlertDialog>
                                                <AlertDialogTrigger as-child>
                                                    <Button variant="ghost" size="icon-sm">
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>Delete this log?</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            This action cannot be undone.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction @click="destroy(log)">
                                                            Delete
                                                        </AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="!completedLogs.length">
                                    <TableCell colspan="4" class="text-center text-muted-foreground">
                                        No time logged yet.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>

        <ManualTimeEntryDialog
            v-model:open="manualDialogOpen"
            :project-id="project.id"
            :log="manualDialogLog"
        />

        <TimesheetExportDialog
            v-model:open="timesheetDialogOpen"
            route-name="projects.timesheet"
            :route-params="{ project: project.id }"
        />
    </AuthenticatedLayout>
</template>
