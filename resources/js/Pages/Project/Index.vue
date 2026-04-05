<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/Components/ui/badge';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { ProjectStatus } from '@/Enums/ProjectStatus';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    projects: Array,
});

const statusClass = (status) => {
    return {
        [ProjectStatus.Active]: 'border-green-500/30 bg-green-500/10 text-green-600 dark:text-green-400',
        [ProjectStatus.Paused]: 'border-yellow-500/30 bg-yellow-500/10 text-yellow-600 dark:text-yellow-400',
        [ProjectStatus.Completed]: 'border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-400',
    }[status] || '';
};
</script>

<template>
    <Head title="Projects" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-foreground">
                Projects
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Client</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="project in projects" :key="project.id">
                            <TableCell class="font-medium">
                                <Link :href="route('projects.show', project.id)" class="hover:text-primary">
                                    {{ project.title }}
                                </Link>
                            </TableCell>
                            <TableCell>{{ project.client?.name || '—' }}</TableCell>
                            <TableCell>
                                <Badge variant="outline" :class="statusClass(project.status)" class="capitalize">{{ project.status }}</Badge>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="!projects.length">
                            <TableCell colspan="3" class="text-center text-muted-foreground">
                                No projects yet.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
