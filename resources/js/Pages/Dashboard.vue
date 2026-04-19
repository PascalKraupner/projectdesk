<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    totalClients: Number,
    totalProjects: Number,
    activeProjects: Number,
    secondsThisWeek: Number,
    secondsThisMonth: Number,
    topProjects: Array,
    topClients: Array,
});

const formatDuration = (seconds) => {
    const s = Math.max(0, Math.floor(seconds || 0));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-foreground">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Top stats -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground">This week</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="font-mono text-2xl font-semibold tabular-nums">
                                {{ formatDuration(secondsThisWeek) }}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground">This month</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="font-mono text-2xl font-semibold tabular-nums">
                                {{ formatDuration(secondsThisMonth) }}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground">Active projects</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-semibold">{{ activeProjects }}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground">Total projects</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-semibold">{{ totalProjects }}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground">Total clients</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-semibold">{{ totalClients }}</div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Top projects + clients -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium text-muted-foreground">Top projects by time</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!topProjects.length" class="text-sm text-muted-foreground">
                                No tracked time yet.
                            </div>
                            <ul v-else class="divide-y divide-border">
                                <li v-for="project in topProjects" :key="project.id" class="flex items-center justify-between py-2">
                                    <div class="flex flex-col">
                                        <Link
                                            :href="route('projects.show', project.id)"
                                            class="text-sm font-medium text-foreground hover:underline"
                                        >
                                            {{ project.title }}
                                        </Link>
                                        <span class="text-xs text-muted-foreground">{{ project.client?.name }}</span>
                                    </div>
                                    <span class="font-mono text-sm tabular-nums">
                                        {{ formatDuration(project.total_seconds) }}
                                    </span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium text-muted-foreground">Top clients by time</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!topClients.length" class="text-sm text-muted-foreground">
                                No tracked time yet.
                            </div>
                            <ul v-else class="divide-y divide-border">
                                <li v-for="client in topClients" :key="client.id" class="flex items-center justify-between py-2">
                                    <Link
                                        :href="route('clients.edit', client.id)"
                                        class="text-sm font-medium text-foreground hover:underline"
                                    >
                                        {{ client.name }}
                                    </Link>
                                    <span class="font-mono text-sm tabular-nums">
                                        {{ formatDuration(client.total_seconds) }}
                                    </span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
