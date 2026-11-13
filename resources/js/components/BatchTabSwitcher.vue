<script setup lang="ts">
import { FileArchive, Files } from '@lucide/vue'

defineProps<{
    activeTab: 'files' | 'zip'
}>()

defineEmits<{
    (e: 'update:activeTab', value: 'files' | 'zip'): void
}>()
</script>

<template>
    <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-950">
        <button
            type="button"
            @click="$emit('update:activeTab', 'files')"
            class="inline-flex items-center justify-center gap-2 rounded-lg px-3 py-3 text-sm font-semibold transition-all"
            :class="activeTab === 'files' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200 dark:bg-slate-800 dark:text-white dark:ring-slate-700' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100'"
        >
            <Files class="h-4 w-4" />
            <span>Select files or folder</span>
        </button>
        <button
            type="button"
            @click="$emit('update:activeTab', 'zip')"
            class="inline-flex items-center justify-center gap-2 rounded-lg px-3 py-3 text-sm font-semibold transition-all"
            :class="activeTab === 'zip' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200 dark:bg-slate-800 dark:text-white dark:ring-slate-700' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100'"
        >
            <FileArchive class="h-4 w-4" />
            <span>Upload a ZIP archive</span>
        </button>
    </div>
</template>
