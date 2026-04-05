<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Head } from '@inertiajs/vue3';

defineProps({
    totalClients: Number,
    totalProjects: Number,
});
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
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Total Clients</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-semibold">{{ totalClients }}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Total Projects</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-semibold">{{ totalProjects }}</div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
