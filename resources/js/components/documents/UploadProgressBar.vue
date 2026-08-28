<script setup lang="ts">
import { computed } from 'vue'
import type { UploadProgress } from '@/composables/useChunkUpload'

const props = defineProps<{
    progress: UploadProgress
}>()

const emit = defineEmits<{
    cancel: []
}>()

const statusText = computed(() => {
    switch (props.progress.status) {
        case 'initializing':
            return 'Preparing upload…'
        case 'uploading':
            return `Uploading chunk ${props.progress.currentChunk}/${props.progress.totalChunks}…`
        case 'reassembling':
            return 'Assembling file on server…'
        case 'completed':
            return 'Upload complete!'
        case 'error':
            return `Error: ${props.progress.error}`
        case 'cancelled':
            return 'Upload cancelled.'
        default:
            return ''
    }
})

function formatBytes(bytes: number): string {
    if (bytes === 0) return '0 B'
    const units = ['B', 'KB', 'MB', 'GB', 'TB']
    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i]
}

function formatTime(seconds: number): string {
    if (seconds < 60) return `${Math.ceil(seconds)}s`
    const mins = Math.floor(seconds / 60)
    const secs = Math.ceil(seconds % 60)
    return `${mins}m ${secs}s`
}

const speedText = computed(() => {
    if (props.progress.speed <= 0) return ''
    return `${formatBytes(props.progress.speed)}/s`
})

const etaText = computed(() => {
    if (props.progress.estimatedTimeLeft <= 0) return ''
    return `~${formatTime(props.progress.estimatedTimeLeft)} left`
})

const barColor = computed(() => {
    switch (props.progress.status) {
        case 'completed':
            return 'bg-green-500'
        case 'error':
            return 'bg-red-500'
        case 'cancelled':
            return 'bg-yellow-500'
        default:
            return 'bg-blue-500'
    }
})
</script>

<template>
    <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <!-- Status text -->
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ statusText }}
            </span>
            <span
                v-if="progress.status === 'uploading'"
                class="text-sm text-gray-500 dark:text-gray-400"
            >
                {{ progress.percent }}%
            </span>
        </div>

        <!-- Progress bar -->
        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-3 overflow-hidden">
            <div
                :class="[
                    'h-full rounded-full transition-all duration-300 ease-out',
                    barColor,
                ]"
                :style="{ width: `${progress.percent}%` }"
            />
        </div>

        <!-- Speed & ETA -->
        <div
            v-if="progress.status === 'uploading'"
            class="flex items-center justify-between mt-2"
        >
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ formatBytes(progress.uploadedBytes) }} / {{ formatBytes(progress.totalBytes) }}
            </span>
            <div class="flex items-center gap-3">
                <span v-if="speedText" class="text-xs text-gray-500 dark:text-gray-400">
                    {{ speedText }}
                </span>
                <span v-if="etaText" class="text-xs text-gray-500 dark:text-gray-400">
                    {{ etaText }}
                </span>
            </div>
        </div>

        <!-- Cancel button -->
        <div v-if="progress.status === 'uploading' || progress.status === 'initializing'" class="mt-3 flex justify-end">
            <button
                type="button"
                @click="emit('cancel')"
                class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 underline"
            >
                Cancel Upload
            </button>
        </div>

        <!-- Error message -->
        <p
            v-if="progress.status === 'error'"
            class="mt-2 text-sm text-red-600 dark:text-red-400"
        >
            {{ progress.error }}
        </p>
    </div>
</template>
