<script setup lang="ts">
import { AlertCircle } from '@lucide/vue'

defineProps<{
    errors: string[]
}>()
</script>

<template>
    <div v-if="errors.length > 0" class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/40">
        <div class="flex items-start gap-2">
            <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-red-500" />
            <div>
                <p class="text-sm font-semibold text-red-800 dark:text-red-200">Some files were skipped:</p>
                <ul class="mt-1 list-inside list-disc text-sm text-red-700 dark:text-red-300">
                    <li v-for="err in errors" :key="err">{{ err }}</li>
                </ul>
            </div>
        </div>
    </div>
</template>
