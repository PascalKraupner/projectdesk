<script setup>
import { Card, CardContent } from '@/Components/ui/card';
import { Head } from '@inertiajs/vue3';
import { LinkIcon, Timer } from 'lucide-vue-next';
</script>

<template>
    <Head title="Link no longer works" />

    <div class="min-h-screen bg-background">
        <header class="border-b border-border bg-card">
            <div class="mx-auto flex max-w-3xl items-center gap-2 px-6 py-4">
                <Timer class="h-5 w-5 text-foreground" :stroke-width="2" />
                <span class="text-sm font-medium text-foreground">Project Desk</span>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-6 py-12">
            <Card>
                <CardContent class="flex flex-col items-center gap-4 py-16 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                        <LinkIcon class="h-6 w-6 text-muted-foreground" />
                    </div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        This link no longer works
                    </h1>
                    <p class="max-w-md text-sm text-muted-foreground">
                        It may have expired or been revoked by the project owner.
                        Ask them to share an updated link.
                    </p>
                </CardContent>
            </Card>

            <p class="mt-6 text-center text-xs text-muted-foreground">
                Shared via Project Desk
            </p>
        </main>
    </div>
</template>
