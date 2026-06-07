<script setup>
import { computed } from 'vue';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { Head } from '@inertiajs/vue3';
import { Timer } from 'lucide-vue-next';
import { formatDuration } from '@/lib/time';
import { formatMoney, amountFromSeconds } from '@/lib/money';
import { statusClass } from '@/lib/projectStatus';

const props = defineProps({
    client: {
        type: Object,
        required: true,
    },
});

const currency = computed(() => props.client.currency || 'EUR');
const hourlyRate = computed(() => props.client.hourly_rate);

const monthAmount = computed(() => {
    if (hourlyRate.value == null) return null;
    return amountFromSeconds(props.client.total_seconds, hourlyRate.value);
});

const projectAmount = (seconds) => {
    if (hourlyRate.value == null) return null;
    return amountFromSeconds(seconds, hourlyRate.value);
};

const formatDateTime = (iso) =>
    new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
</script>

<template>
    <Head :title="client.name" />

    <div class="min-h-screen bg-background">
        <header class="border-b border-border bg-card">
            <div class="mx-auto flex max-w-4xl items-center gap-2 px-6 py-4">
                <Timer class="h-5 w-5 text-foreground" :stroke-width="2" />
                <span class="text-sm font-medium text-foreground">Project Desk</span>
            </div>
        </header>

        <main class="mx-auto max-w-4xl space-y-6 px-6 py-12">
            <Card>
                <CardContent class="space-y-6 py-10">
                    <div class="space-y-1 text-center">
                        <h1 class="text-3xl font-semibold text-foreground">
                            {{ client.name }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Activity for {{ client.period_label }}
                        </p>
                    </div>

                    <div
                        class="grid gap-6 text-center"
                        :class="hourlyRate != null ? 'sm:grid-cols-3' : 'sm:grid-cols-1'"
                    >
                        <div>
                            <div class="text-xs uppercase tracking-wide text-muted-foreground">
                                This month
                            </div>
                            <div class="font-mono text-3xl font-light text-foreground tabular-nums">
                                {{ formatDuration(client.total_seconds) }}
                            </div>
                        </div>
                        <div v-if="hourlyRate != null">
                            <div class="text-xs uppercase tracking-wide text-muted-foreground">
                                Hourly rate
                            </div>
                            <div class="text-3xl font-light text-foreground">
                                {{ formatMoney(hourlyRate, currency) }}
                            </div>
                        </div>
                        <div v-if="monthAmount != null">
                            <div class="text-xs uppercase tracking-wide text-muted-foreground">
                                Total this month
                            </div>
                            <div class="text-3xl font-light text-foreground">
                                {{ formatMoney(monthAmount, currency) }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card v-for="project in client.projects" :key="project.id">
                <CardHeader>
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-semibold text-foreground">{{ project.title }}</h2>
                            <Badge variant="outline" :class="statusClass(project.status)" class="capitalize">
                                {{ project.status }}
                            </Badge>
                        </div>
                        <div class="text-right">
                            <div class="font-mono text-sm tabular-nums text-foreground">
                                {{ formatDuration(project.total_seconds) }}
                            </div>
                            <div v-if="projectAmount(project.total_seconds) != null" class="text-xs text-muted-foreground">
                                {{ formatMoney(projectAmount(project.total_seconds), currency) }}
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Started</TableHead>
                                <TableHead>Duration</TableHead>
                                <TableHead>Note</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="log in project.time_logs" :key="log.id">
                                <TableCell class="text-sm">{{ formatDateTime(log.started_at) }}</TableCell>
                                <TableCell class="font-mono tabular-nums">
                                    {{ formatDuration(log.duration_seconds) }}
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">
                                    {{ log.note || '—' }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Card v-if="!client.projects.length">
                <CardContent class="py-12 text-center text-muted-foreground">
                    No time tracked this month yet.
                </CardContent>
            </Card>

            <p class="text-center text-xs text-muted-foreground">
                Shared via Project Desk
            </p>
        </main>
    </div>
</template>
