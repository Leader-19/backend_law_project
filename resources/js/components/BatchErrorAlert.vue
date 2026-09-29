<script setup lang="ts">
import { AlertCircle } from '@lucide/vue'

defineProps<{
    errors: string[]
}>()
</script>

<template>
    <div v-if="errors.length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-start gap-2">
            <AlertCircle class="w-5 h-5 text-red-500 mt-0.5" />
            <div>
                <p class="text-sm font-medium text-red-800">Some files were skipped:</p>
                <ul class="mt-1 text-sm text-red-600 list-disc list-inside">
                    <li v-for="err in errors" :key="err">{{ err }}</li>
                </ul>
            </div>
        </div>
    </div>
</template>
