<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-background pt-6 sm:justify-center sm:pt-0">
        <div>
            <Link href="/">
                <ApplicationLogo class="h-16 w-16 text-muted-foreground" />
            </Link>
        </div>

        <div class="mt-6 w-full sm:max-w-md">
            <slot />
        </div>

        <footer class="mt-8 flex gap-4 text-xs text-muted-foreground">
            <a href="https://pascalkraupner.com/imprint" target="_blank" rel="noopener noreferrer" class="hover:text-foreground">Impressum</a>
            <a href="https://pascalkraupner.com/privacy" target="_blank" rel="noopener noreferrer" class="hover:text-foreground">Datenschutz</a>
        </footer>
    </div>
</template>
