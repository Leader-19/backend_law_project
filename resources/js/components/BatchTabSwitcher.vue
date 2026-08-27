<script setup lang="ts">
import { FileArchive } from 'lucide-vue-next'

defineProps<{
    activeTab: 'files' | 'zip'
}>()

defineEmits<{
    (e: 'update:activeTab', value: 'files' | 'zip'): void
}>()
</script>

<template>
    <div class="flex border-b border-gray-200 mb-6">
        <button
            type="button"
            @click="$emit('update:activeTab', 'files')"
            class="px-4 py-2 text-sm font-medium transition-colors"
            :class="activeTab === 'files' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
        >
            Select Files / Folder
        </button>
        <button
            type="button"
            @click="$emit('update:activeTab', 'zip')"
            class="px-4 py-2 text-sm font-medium transition-colors flex items-center gap-2"
            :class="activeTab === 'zip' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
        >
            <FileArchive class="w-4 h-4" />
            Upload ZIP (for large folders)
        </button>
    </div>
</template>
