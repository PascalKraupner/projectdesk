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
import { Pencil, Play, Plus, Square, Trash2 } from 'lucide-vue-next';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    project: Object,
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

const completedLogs = computed(() =>
    props.project.time_logs.filter((l) => l.ended_at !== null),
);

const formatDuration = (seconds) => {
    const s = Math.max(0, Math.floor(seconds));
    const h = String(Math.floor(s / 3600)).padStart(2, '0');
    const m = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
    const sec = String(s % 60).padStart(2, '0');
    return `${h}:${m}:${sec}`;
};

const liveSeconds = computed(() => {
    if (!runningLog.value) return 0;
    return (now.value - new Date(runningLog.value.started_at).getTime()) / 1000;
});

const display = computed(() =>
    runningLog.value ? formatDuration(liveSeconds.value) : '00:00:00',
);

const totalSeconds = computed(() =>
    (props.project.total_seconds ?? 0) + (runningLog.value ? liveSeconds.value : 0),
);

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

const openAddEntry = () => {
    manualDialogLog.value = null;
    manualDialogOpen.value = true;
};

const openEditEntry = (log) => {
    manualDialogLog.value = log;
    manualDialogOpen.value = true;
};

const statusClass = (status) => {
    return {
        [ProjectStatus.Active]: 'border-green-500/30 bg-green-500/10 text-green-600 dark:text-green-400',
        [ProjectStatus.Paused]: 'border-yellow-500/30 bg-yellow-500/10 text-yellow-600 dark:text-yellow-400',
        [ProjectStatus.Completed]: 'border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-400',
    }[status] || '';
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
                    <span class="text-sm text-muted-foreground">
                        {{ project.client?.name }}
                    </span>
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
                                class="font-mono text-7xl font-light tracking-wider text-foreground tabular-nums"
                                :class="{ 'text-primary': runningLog }"
                            >
                                {{ display }}
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

                            <div class="flex items-center gap-3">
                                <Button
                                    v-if="!runningLog"
                                    @click="start"
                                    size="lg"
                                    class="h-14 w-14 rounded-full"
                                >
                                    <Play class="h-6 w-6" />
                                </Button>
                                <Button
                                    v-else
                                    @click="stop"
                                    size="lg"
                                    class="h-14 w-14 rounded-full"
                                >
                                    <Square class="h-6 w-6" />
                                </Button>
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
                                <div class="text-sm font-medium text-foreground">{{ project.client?.name || '—' }}</div>
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
                                    <TableHead>Ended</TableHead>
                                    <TableHead>Duration</TableHead>
                                    <TableHead>Note</TableHead>
                                    <TableHead class="text-right"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="log in completedLogs" :key="log.id">
                                    <TableCell class="text-sm">{{ formatDateTime(log.started_at) }}</TableCell>
                                    <TableCell class="text-sm">{{ formatDateTime(log.ended_at) }}</TableCell>
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
                                    <TableCell colspan="5" class="text-center text-muted-foreground">
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
    </AuthenticatedLayout>
</template>
