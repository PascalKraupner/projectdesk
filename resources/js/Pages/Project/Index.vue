<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader,
    AlertDialogTitle, AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { ProjectStatus } from '@/Enums/ProjectStatus';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    projects: Array,
});

const formatDuration = (seconds) => {
    const s = Math.max(0, Math.floor(seconds || 0));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
};

const statusClass = (status) => {
    return {
        [ProjectStatus.Active]: 'border-green-500/30 bg-green-500/10 text-green-600 dark:text-green-400',
        [ProjectStatus.Paused]: 'border-yellow-500/30 bg-yellow-500/10 text-yellow-600 dark:text-yellow-400',
        [ProjectStatus.Completed]: 'border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-400',
    }[status] || '';
};

const destroy = (project) => {
    router.delete(route('projects.destroy', project.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Projects" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-foreground">
                    Projects
                </h2>
                <Button as-child size="sm">
                    <Link :href="route('projects.create')">New Project</Link>
                </Button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Client</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Time</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="project in projects" :key="project.id">
                            <TableCell class="font-medium">
                                <Link :href="route('projects.show', project.id)">
                                    {{ project.title }}
                                </Link>
                            </TableCell>
                            <TableCell>{{ project.client?.name || '—' }}</TableCell>
                            <TableCell>
                                <Badge variant="outline" :class="statusClass(project.status)" class="capitalize">{{ project.status }}</Badge>
                            </TableCell>
                            <TableCell class="text-right font-mono text-sm tabular-nums">
                                {{ formatDuration(project.total_seconds) }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button as-child variant="outline" size="sm">
                                        <Link :href="route('projects.edit', project.id)">Edit</Link>
                                    </Button>
                                    <AlertDialog>
                                        <AlertDialogTrigger as-child>
                                            <Button variant="destructive" size="sm">
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
                                                <AlertDialogAction @click="destroy(project)">
                                                    Delete
                                                </AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="!projects.length">
                            <TableCell colspan="5" class="text-center text-muted-foreground">
                                No projects yet.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
