<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/Components/ui/badge';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    clients: Array,
});
</script>

<template>
    <Head title="Clients" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-foreground">
                Clients
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead class="text-right">Projects</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="client in clients" :key="client.id">
                            <TableCell class="font-medium">{{ client.name }}</TableCell>
                            <TableCell>{{ client.email || '—' }}</TableCell>
                            <TableCell class="text-right">
                                <Badge variant="secondary">{{ client.projects_count }}</Badge>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="!clients.length">
                            <TableCell colspan="3" class="text-center text-muted-foreground">
                                No clients yet.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
