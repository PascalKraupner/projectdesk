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
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    clients: Array,
});

const destroy = (client) => {
    router.delete(route('clients.destroy', client.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Clients" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-foreground">
                    Clients
                </h2>
                <Button as-child size="sm">
                    <Link :href="route('clients.create')">New Client</Link>
                </Button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead class="text-right">Projects</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="client in clients" :key="client.id">
                            <TableCell class="font-medium">
                                <Link :href="route('clients.edit', client.id)">
                                    {{ client.name }}
                                </Link>
                            </TableCell>
                            <TableCell>{{ client.email || '—' }}</TableCell>
                            <TableCell class="text-right">
                                <Badge variant="secondary">{{ client.projects_count }}</Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button as-child variant="outline" size="sm">
                                        <Link :href="route('clients.edit', client.id)">Edit</Link>
                                    </Button>
                                    <AlertDialog>
                                        <AlertDialogTrigger as-child>
                                            <Button variant="destructive" size="sm">
                                                Delete
                                            </Button>
                                        </AlertDialogTrigger>
                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>Delete {{ client.name }}?</AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    <template v-if="client.projects_count > 0">
                                                        This will also delete {{ client.projects_count }} project(s). This action cannot be undone.
                                                    </template>
                                                    <template v-else>
                                                        This action cannot be undone.
                                                    </template>
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                <AlertDialogAction @click="destroy(client)">
                                                    Delete
                                                </AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="!clients.length">
                            <TableCell colspan="4" class="text-center text-muted-foreground">
                                No clients yet.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
