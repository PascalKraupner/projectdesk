<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader,
    AlertDialogTitle, AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { ProjectStatus } from '@/Enums/ProjectStatus';
import { useTimer } from '@/Composables/useTimer';
import { Play, Pause, Square } from 'lucide-vue-next';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    project: Object,
});

const destroy = () => {
    router.delete(route('projects.destroy', props.project.id));
};

const { isRunning, display, start, pause, stop } = useTimer();

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
                                <AlertDialogAction @click="destroy">Delete</AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Timer -->
                    <Card class="lg:col-span-2">
                        <CardContent class="flex flex-col items-center py-12">
                            <div
                                class="font-mono text-7xl font-light tracking-wider text-foreground tabular-nums"
                                :class="{ 'text-primary': isRunning }"
                            >
                                {{ display }}
                            </div>

                            <Separator class="my-8 w-24" />

                            <div class="flex items-center gap-3">
                                <Button
                                    v-if="!isRunning"
                                    @click="start"
                                    size="lg"
                                    class="h-14 w-14 rounded-full"
                                >
                                    <Play class="h-6 w-6" />
                                </Button>
                                <Button
                                    v-else
                                    @click="pause"
                                    variant="secondary"
                                    size="lg"
                                    class="h-14 w-14 rounded-full"
                                >
                                    <Pause class="h-6 w-6" />
                                </Button>
                                <Button
                                    @click="stop"
                                    variant="outline"
                                    size="lg"
                                    class="h-14 w-14 rounded-full"
                                    :disabled="!isRunning && display === '00:00:00'"
                                >
                                    <Square class="h-5 w-5" />
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
                                <div class="text-xs text-muted-foreground">Created</div>
                                <div class="text-sm text-foreground">
                                    {{ new Date(project.created_at).toLocaleDateString() }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
