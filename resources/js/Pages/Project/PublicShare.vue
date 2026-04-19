<script setup>
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent } from '@/Components/ui/card';
import { Head } from '@inertiajs/vue3';
import { Timer } from 'lucide-vue-next';
import { formatDuration } from '@/lib/time';
import { statusClass } from '@/lib/projectStatus';

defineProps({
    project: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head :title="project.title" />

    <div class="min-h-screen bg-background">
        <header class="border-b border-border bg-card">
            <div class="mx-auto flex max-w-3xl items-center gap-2 px-6 py-4">
                <Timer class="h-5 w-5 text-foreground" :stroke-width="2" />
                <span class="text-sm font-medium text-foreground">Project Desk</span>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-6 py-12">
            <Card>
                <CardContent class="space-y-8 py-12 text-center">
                    <div class="space-y-3">
                        <h1 class="text-3xl font-semibold text-foreground">
                            {{ project.title }}
                        </h1>
                        <Badge variant="outline" :class="statusClass(project.status)" class="capitalize">
                            {{ project.status }}
                        </Badge>
                    </div>

                    <div class="space-y-1">
                        <div class="text-xs uppercase tracking-wide text-muted-foreground">
                            Total time tracked
                        </div>
                        <div class="font-mono text-5xl font-light text-foreground tabular-nums">
                            {{ formatDuration(project.total_seconds) }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <p class="mt-6 text-center text-xs text-muted-foreground">
                Shared via Project Desk
            </p>
        </main>
    </div>
</template>
