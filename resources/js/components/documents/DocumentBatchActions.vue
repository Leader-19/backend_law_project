<script setup lang="ts">
import { Upload, FileArchive } from '@lucide/vue'

defineProps<{
    activeTab: 'files' | 'zip'
    formProcessing: boolean
    zipFormProcessing: boolean
    selectedFilesCount: number
    zipFormHasFile: boolean
}>()

defineEmits<{
    (e: 'submit-files'): void
    (e: 'submit-zip'): void
}>()
</script>

<template>
    <div v-if="activeTab === 'files'">
        <button
            type="button"
            @click="$emit('submit-files')"
            :disabled="formProcessing || selectedFilesCount === 0"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <Upload class="w-5 h-5" />
            {{ formProcessing ? 'Importing...' : `Import ${selectedFilesCount} Document(s)` }}
        </button>
    </div>

    <div v-if="activeTab === 'zip'">
        <button
            type="button"
            @click="$emit('submit-zip')"
            :disabled="zipFormProcessing || !zipFormHasFile"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <FileArchive class="w-5 h-5" />
            {{ zipFormProcessing ? 'Importing...' : 'Import from ZIP' }}
        </button>
    </div>
</template>
