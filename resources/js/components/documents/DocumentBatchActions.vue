<script setup lang="ts">
import { Upload, FileArchive } from 'lucide-vue-next'

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
            class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded font-medium disabled:opacity-50 flex items-center justify-center gap-2"
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
            class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded font-medium disabled:opacity-50 flex items-center justify-center gap-2"
        >
            <FileArchive class="w-5 h-5" />
            {{ zipFormProcessing ? 'Importing...' : 'Import from ZIP' }}
        </button>
    </div>
</template>