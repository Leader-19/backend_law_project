<script setup lang="ts">
import { ref } from 'vue'
import { FileArchive, CheckCircle2, X } from '@lucide/vue'

defineProps<{
    modelValue: File | null
    error?: string
}>()

defineEmits<{
    (e: 'update:modelValue', value: File | null): void
}>()

const zipInput = ref<HTMLInputElement | null>(null)

function formatFileSize(bytes: number): string {
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}
</script>

<template>
    <div>
        <div
            class="group cursor-pointer rounded-2xl border-2 border-dashed p-8 text-center transition-all sm:p-10"
            :class="modelValue ? 'border-emerald-400 bg-emerald-50/70 dark:border-emerald-800 dark:bg-emerald-950/30' : 'border-slate-300 bg-slate-50/60 hover:border-blue-400 hover:bg-blue-50/50 dark:border-slate-700 dark:bg-slate-950/40 dark:hover:border-blue-700 dark:hover:bg-blue-950/20'"
            @click="zipInput?.click()"
        >
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:text-blue-400 dark:ring-slate-700">
                <FileArchive class="h-7 w-7" />
            </div>
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                {{ modelValue ? 'Choose a different ZIP archive' : 'Click to select a ZIP archive' }}
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Include supported documents in the archive · maximum upload size 500 MB
            </p>
            <span class="mt-4 inline-flex items-center rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition group-hover:bg-blue-700">Browse ZIP file</span>

            <input
                ref="zipInput"
                type="file"
                accept=".zip,application/zip,application/x-zip-compressed"
                @change="(e) => {
                    const target = e.target as HTMLInputElement
                    if (target.files && target.files.length > 0) {
                        $emit('update:modelValue', target.files[0])
                    }
                    target.value = ''
                }"
                class="hidden"
            />
        </div>

        <div v-if="modelValue" class="mt-4 overflow-hidden rounded-xl border border-emerald-200 dark:border-emerald-900">
            <div class="flex items-center justify-between gap-3 bg-emerald-50/70 px-4 py-3 dark:bg-emerald-950/30">
                <div class="flex min-w-0 items-center gap-3">
                    <CheckCircle2 class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                    <div class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ modelValue.name }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ formatFileSize(modelValue.size) }}</span>
                    </div>
                </div>
                <button
                    type="button"
                    @click="$emit('update:modelValue', null)"
                    class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-white hover:text-red-600 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-red-400"
                >
                    <X class="h-3.5 w-3.5" /> Remove
                </button>
            </div>
        </div>

        <p v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </p>
    </div>
</template>
