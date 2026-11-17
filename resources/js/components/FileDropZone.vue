<script setup lang="ts">
import { ref } from 'vue'
import {
    FolderOpen,
    Upload,
    FileText,
    X,
    CheckCircle2
} from '@lucide/vue'


const MAX_FILE_BYTES = 2 * 1024 * 1024 * 1024 // 2 GB — matches backend limit
const MAX_FILES = 50

const props = defineProps<{
    modelValue: File[]
    errors: string[]
    fileErrors: Record<number, string>
    isDragging: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: File[]): void
    (e: 'update:errors', value: string[]): void
    (e: 'update:fileErrors', value: Record<number, string>): void
    (e: 'update:isDragging', value: boolean): void
    (e: 'submit'): void
}>()

const folderInput = ref<HTMLInputElement | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 B'
    const units = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i]
}

function isValidFileType(file: File): boolean {
    const allowedExts = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx']
    const ext = '.' + file.name.split('.').pop()?.toLowerCase()
    return allowedExts.includes(ext)
}

function processFiles(fileList: FileList | null) {
    emit('update:errors', [])
    emit('update:fileErrors', {})

    if (!fileList || fileList.length === 0) return

    const newFiles: File[] = []
    const skipped: string[] = []
    const fileErrs: Record<number, string> = {}

    Array.from(fileList).forEach((file, idx) => {
        if (!isValidFileType(file)) {
            skipped.push(`${file.name} (unsupported format)`)
            return
        }

        if (file.size > MAX_FILE_BYTES) {
            fileErrs[props.modelValue.length + idx] = `${file.name} exceeds 2 GB limit`
            return
        }

        newFiles.push(file)
    })

    if (skipped.length > 0) {
        emit('update:errors', skipped)
    }

    if (Object.keys(fileErrs).length > 0) {
        emit('update:fileErrors', { ...props.fileErrors, ...fileErrs })
    }

    const remainingSlots = Math.max(0, MAX_FILES - props.modelValue.length)
    if (newFiles.length > remainingSlots) {
        emit('update:errors', [
            ...skipped,
            `${newFiles.length - remainingSlots} file(s) skipped; a batch can contain at most ${MAX_FILES} files.`,
        ])
    }

    const merged = [...props.modelValue, ...newFiles.slice(0, remainingSlots)]
    emit('update:modelValue', merged)
}

function handleFolderSelect(event: Event) {
    const target = event.target as HTMLInputElement
    processFiles(target.files)
    target.value = ''
}

function handleFileSelect(event: Event) {
    const target = event.target as HTMLInputElement
    processFiles(target.files)
    target.value = ''
}

function handleDrop(event: DragEvent) {
    emit('update:isDragging', false)
    if (event.dataTransfer?.files) {
        processFiles(event.dataTransfer.files)
    }
}

function removeFile(index: number) {
    const next = props.modelValue.filter((_, i) => i !== index)
    emit('update:modelValue', next)
}

function clearAll() {
    emit('update:modelValue', [])
    emit('update:errors', [])
    emit('update:fileErrors', {})
}
</script>

<template>
    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-800 dark:text-slate-100">
            Upload Files <span class="text-red-500">*</span>
        </label>

        <div
            class="group cursor-pointer rounded-2xl border-2 border-dashed p-7 text-center transition-all sm:p-10"
            :class="isDragging ? 'scale-[1.005] border-blue-500 bg-blue-50 ring-4 ring-blue-500/10 dark:bg-blue-950/40' : 'border-slate-300 bg-slate-50/60 hover:border-blue-400 hover:bg-blue-50/50 dark:border-slate-700 dark:bg-slate-950/40 dark:hover:border-blue-700 dark:hover:bg-blue-950/20'"
            @dragover.prevent="$emit('update:isDragging', true)"
            @dragleave.prevent="$emit('update:isDragging', false)"
            @drop.prevent="handleDrop"
            @click="folderInput?.click()"
        >
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-slate-200 transition-transform group-hover:-translate-y-0.5 dark:bg-slate-900 dark:text-blue-400 dark:ring-slate-700">
                <FolderOpen class="h-7 w-7" />
            </div>
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                Drop files here or click to browse
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Add a folder or individual files. PDF, DOC, DOCX, XLS, XLSX, PPT and PPTX · up to 2 GB each
            </p>
            <span class="mt-4 inline-flex items-center rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition group-hover:bg-blue-700">Browse folder</span>

            <input
                ref="folderInput"
                type="file"
                webkitdirectory
                @change="handleFolderSelect"
                class="hidden"
            />
        </div>

        <div class="mt-3 flex items-center justify-between gap-3">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                <Upload class="w-4 h-4" />
                Or select files manually
                <input
                    ref="fileInput"
                    type="file"
                    multiple
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                    @change="handleFileSelect"
                    class="hidden"
                />
            </label>
        </div>

        <slot name="file-list">
            <div v-if="modelValue.length > 0" class="mt-5 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/70">
                    <div class="flex items-center gap-2">
                        <CheckCircle2 class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ modelValue.length }} file(s) selected</span>
                    </div>
                    <button
                        type="button"
                        @click="clearAll"
                        class="text-xs font-semibold text-slate-500 transition hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400"
                    >
                        Clear all
                    </button>
                </div>
                <div class="max-h-64 overflow-y-auto">
                    <div
                        v-for="(file, index) in modelValue"
                        :key="index"
                        class="flex items-center justify-between border-b border-slate-100 px-4 py-3 transition last:border-b-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/50"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-300">
                                <FileText class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="max-w-[55vw] truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ file.name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ formatFileSize(file.size) }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="removeFile(index)"
                            class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/50 dark:hover:text-red-400"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </slot>
    </div>
</template>
