<script setup>
import { useTheme } from '@/Composables/useTheme'
import { Sun, Moon, Monitor } from 'lucide-vue-next'

const { mode, setLight, setDark, setSystem } = useTheme()
</script>

<template>
    <div class="flex items-center gap-1">
        <button
            @click="setLight"
            :class="[
                'rounded-md p-1.5 transition',
                mode === 'light'
                    ? 'bg-accent text-accent-foreground'
                    : 'text-muted-foreground hover:text-foreground'
            ]"
            title="Light"
        >
            <Sun class="h-4 w-4" />
        </button>
        <button
            @click="setDark"
            :class="[
                'rounded-md p-1.5 transition',
                mode === 'dark'
                    ? 'bg-accent text-accent-foreground'
                    : 'text-muted-foreground hover:text-foreground'
            ]"
            title="Dark"
        >
            <Moon class="h-4 w-4" />
        </button>
        <button
            @click="setSystem"
            :class="[
                'rounded-md p-1.5 transition',
                mode === 'auto'
                    ? 'bg-accent text-accent-foreground'
                    : 'text-muted-foreground hover:text-foreground'
            ]"
            title="System"
        >
            <Monitor class="h-4 w-4" />
        </button>
    </div>
</template>
