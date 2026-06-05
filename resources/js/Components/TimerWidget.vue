<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Play, Search, Square } from 'lucide-vue-next';
import { formatDuration } from '@/lib/time';

const page = usePage();
const runningTimer = computed(() => page.props.runningTimer);
const timerProjects = computed(() => page.props.timerProjects ?? []);

const focusInput = () => {
    inputRef.value?.$el?.focus?.() ?? inputRef.value?.focus?.();
};

const search = ref('');
const selectedProject = ref(null);
const focused = ref(false);
const highlightedIndex = ref(0);
const inputRef = ref(null);

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return [];
    return timerProjects.value
        .filter((p) =>
            p.title.toLowerCase().includes(q)
            || (p.client_name && p.client_name.toLowerCase().includes(q)),
        )
        .slice(0, 8);
});

const showDropdown = computed(() => focused.value && filtered.value.length > 0);

watch(filtered, () => { highlightedIndex.value = 0; });

watch(search, (next) => {
    if (selectedProject.value && next !== selectedProject.value.title) {
        selectedProject.value = null;
    }
});

const selectProject = (project) => {
    selectedProject.value = project;
    search.value = project.title;
    focused.value = false;
    nextTick(() => inputRef.value?.$el?.blur?.() ?? inputRef.value?.blur?.());
};

const now = ref(Date.now());
let tickHandle = null;

onMounted(() => {
    tickHandle = setInterval(() => { now.value = Date.now(); }, 1000);
});
onUnmounted(() => clearInterval(tickHandle));

const liveSeconds = computed(() => {
    if (!runningTimer.value) return 0;
    return (now.value - new Date(runningTimer.value.started_at).getTime()) / 1000;
});

const display = computed(() => formatDuration(liveSeconds.value));

const submitting = ref(false);

const canStart = computed(() => {
    if (runningTimer.value || submitting.value) return false;
    return !!selectedProject.value || filtered.value.length > 0;
});

const start = () => {
    if (submitting.value || runningTimer.value) return;
    const project = selectedProject.value || filtered.value[highlightedIndex.value] || filtered.value[0];
    if (!project) {
        focusInput();
        return;
    }
    submitting.value = true;
    router.post(
        route('time-logs.store', project.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                submitting.value = false;
                search.value = '';
                selectedProject.value = null;
            },
        },
    );
};

const stop = () => {
    if (!runningTimer.value || submitting.value) return;
    submitting.value = true;
    router.patch(
        route('time-logs.update', runningTimer.value.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => { submitting.value = false; },
        },
    );
};

const onKeydown = (e) => {
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (filtered.value.length) {
            highlightedIndex.value = Math.min(filtered.value.length - 1, highlightedIndex.value + 1);
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIndex.value = Math.max(0, highlightedIndex.value - 1);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (filtered.value.length) {
            selectProject(filtered.value[highlightedIndex.value]);
        } else if (selectedProject.value) {
            start();
        }
    } else if (e.key === 'Escape') {
        focused.value = false;
        inputRef.value?.$el?.blur?.() ?? inputRef.value?.blur?.();
    }
};

const onBlur = () => {
    setTimeout(() => { focused.value = false; }, 120);
};
</script>

<template>
    <div class="flex items-center gap-2">
        <div
            v-if="runningTimer"
            class="flex items-center gap-2 rounded-md border border-border bg-background px-2 py-1"
        >
            <span class="h-2 w-2 animate-pulse rounded-full bg-primary" />
            <Link
                :href="route('projects.show', runningTimer.project_id)"
                class="max-w-[8rem] truncate text-sm font-medium text-foreground hover:underline"
                :title="runningTimer.project_title"
            >
                {{ runningTimer.project_title }}
            </Link>
            <span class="font-mono text-sm tabular-nums text-foreground">
                {{ display }}
            </span>
            <Button
                variant="ghost"
                size="icon-sm"
                @click="stop"
                :disabled="submitting"
                title="Stop timer"
            >
                <Square class="h-4 w-4" />
            </Button>
        </div>

        <div v-if="!runningTimer && timerProjects.length" class="flex items-center gap-2">
            <div class="relative">
                <Search class="pointer-events-none absolute left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                <Input
                    ref="inputRef"
                    v-model="search"
                    type="text"
                    placeholder="Search projects…"
                    class="h-9 w-56 pl-8"
                    autocomplete="off"
                    @focus="focused = true"
                    @blur="onBlur"
                    @keydown="onKeydown"
                />
                <div
                    v-if="showDropdown"
                    class="absolute left-0 right-0 z-50 mt-1 max-h-72 w-72 overflow-y-auto rounded-md border border-border bg-card py-1 shadow-lg"
                >
                    <button
                        v-for="(project, idx) in filtered"
                        :key="project.id"
                        type="button"
                        class="block w-full px-3 py-1.5 text-left text-sm transition-colors"
                        :class="idx === highlightedIndex
                            ? 'bg-accent text-accent-foreground'
                            : 'text-foreground hover:bg-accent hover:text-accent-foreground'"
                        @mousedown.prevent="selectProject(project)"
                        @mouseenter="highlightedIndex = idx"
                    >
                        <div class="font-medium">{{ project.title }}</div>
                        <div v-if="project.client_name" class="text-xs text-muted-foreground">
                            {{ project.client_name }}
                        </div>
                    </button>
                </div>
            </div>

            <Button
                size="sm"
                @click="start"
                :disabled="submitting"
                :title="selectedProject ? `Start ${selectedProject.title}` : 'Search a project to start'"
            >
                <Play class="h-4 w-4" />
                Start
            </Button>
        </div>
    </div>
</template>
