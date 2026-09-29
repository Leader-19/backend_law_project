<script setup lang="ts">
import { ref } from 'vue'
import {
    FolderOpen,
    Upload,
    FileText,
    X,
    CheckCircle2,
    AlertCircle
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
        <label class="block font-medium mb-1">
            Upload Files <span class="text-red-500">*</span>
        </label>

        <div
            class="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors"
            :class="isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'"
            @dragover.prevent="$emit('update:isDragging', true)"
            @dragleave.prevent="$emit('update:isDragging', false)"
            @drop.prevent="handleDrop"
            @click="folderInput?.click()"
        >
            <FolderOpen class="w-12 h-12 mx-auto text-gray-400 mb-3" />
            <p class="text-sm text-gray-600 mb-1">
                Click to select a folder, or drag and drop files here
            </p>
            <p class="text-xs text-gray-400">
                Supported formats: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX (max 2 GB each; 50 files per batch)
            </p>

            <input
                ref="folderInput"
                type="file"
                webkitdirectory
                @change="handleFolderSelect"
                class="hidden"
            />
        </div>

        <div class="mt-3">
            <label class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 rounded cursor-pointer hover:bg-gray-200 text-sm">
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

        <p v-if="errors.length > 0" class="mt-3">
            <span class="flex items-start gap-2 text-red-500 text-sm">
                <AlertCircle class="w-4 h-4 mt-0.5" />
                {{ errors.length }} file(s) skipped due to unsupported format or size limit.
            </span>
        </p>

        <slot name="file-list">
            <div v-if="modelValue.length > 0" class="mt-4 border rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <CheckCircle2 class="w-5 h-5 text-green-600" />
                        <span class="font-medium text-sm">{{ modelValue.length }} file(s) selected</span>
                    </div>
                    <button
                        type="button"
                        @click="clearAll"
                        class="text-sm text-red-600 hover:text-red-700"
                    >
                        Clear all
                    </button>
                </div>
                <div class="max-h-64 overflow-y-auto">
                    <div
                        v-for="(file, index) in modelValue"
                        :key="index"
                        class="flex items-center justify-between px-4 py-2 border-b last:border-b-0 hover:bg-gray-50"
                    >
                        <div class="flex items-center gap-3">
                            <FileText class="w-5 h-5 text-gray-400" />
                            <div>
                                <p class="text-sm font-medium">{{ file.name }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ formatFileSize(file.size) }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="removeFile(index)"
                            class="p-1 text-gray-400 hover:text-red-500"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </slot>
    </div>
</template>
