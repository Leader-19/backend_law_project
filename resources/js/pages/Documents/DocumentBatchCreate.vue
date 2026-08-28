<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft, AlertCircle, RotateCcw } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { type BreadcrumbItem } from '@/types'
import DocumentBatchForm from '@/components/documents/DocumentBatchForm.vue'
import DocumentBatchActions from '@/components/documents/DocumentBatchActions.vue'
import UploadProgressBar from '@/components/documents/UploadProgressBar.vue'
import { useChunkUpload, CHUNK_UPLOAD_THRESHOLD } from '@/composables/useChunkUpload'
import type { UploadProgress } from '@/composables/useChunkUpload'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Batch Create Documents',
        href: '/documents',
    },
]

const page = usePage()
const categories = page.props.categories as any[]

const form = useForm({
    doc_upload: [] as File[],
    category_id: '',
    description: '',
})

const zipForm = useForm({
    zip_file: null as File | null,
    category_id: '',
    description: '',
})

const selectedFiles = ref<File[]>([])
const errors = ref<string[]>([])
const fileErrors = ref<Record<number, string>>({})
const isDragging = ref(false)
const activeTab = ref<'files' | 'zip'>('files')

// Chunked upload state
const { progress, isUploading, lastUploadId, upload, resume, cancel, reset } = useChunkUpload()
const batchProgress = ref<UploadProgress | null>(null)
const batchStatus = ref('')
const batchTotalFiles = ref(0)
const batchUploadedFiles = ref(0)
const uploadSummary = ref<{ success: number; failed: number; failedFiles: string[] } | null>(null)

// Track failed files for retry (keyed by original index)
interface FailedFile {
    file: File
    index: number
    uploadId: string | null // non-null means a chunked upload was started (resume possible)
    error: string
}
const failedFilesMap = ref<Map<number, FailedFile>>(new Map())
const isRetrying = ref(false)

// ZIP upload state
const zipUploading = ref(false)
const zipProgress = ref('')

const showFileErrors = computed(() => Object.keys(fileErrors.value).length > 0)
const hasFailedFiles = computed(() => failedFilesMap.value.size > 0)

function fileNameWithoutExt(name: string): string {
    return name.replace(/\.[^/.]+$/, '')
}

function getCsrfToken(): string {
    return decodeURIComponent(
        (document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
    )
}

function fileMetadata(file: File) {
    return {
        doc_name: fileNameWithoutExt(file.name),
        doc_title: fileNameWithoutExt(file.name),
        category_id: form.category_id,
        description: form.description || undefined,
    }
}

async function submitFiles() {
    if (selectedFiles.value.length === 0) {
        errors.value = ['Please select at least one file.']
        return
    }

    // If any file is large, use chunked upload for each
    if (hasLargeFiles(selectedFiles.value)) {
        await submitFilesIndividually(selectedFiles.value)
        return
    }

    // All files are small — use the normal batch upload
    form.doc_upload = selectedFiles.value

    form.post(route('documents.batch.store'), {
        forceFormData: true,
        onSuccess: () => {
            selectedFiles.value = []
            form.reset()
        },
    })
}

async function submitFilesIndividually(filesToUpload: File[]) {
    batchTotalFiles.value = filesToUpload.length
    batchUploadedFiles.value = 0
    errors.value = []
    fileErrors.value = {}
    uploadSummary.value = null
    failedFilesMap.value = new Map()

    const newFailed = new Map<number, FailedFile>()

    for (let i = 0; i < filesToUpload.length; i++) {
        const file = filesToUpload[i]
        batchStatus.value = `Uploading file ${i + 1} of ${filesToUpload.length}: ${file.name}`

        try {
            if (file.size > CHUNK_UPLOAD_THRESHOLD) {
                // Use chunked upload for large files
                await upload(
                    file,
                    fileMetadata(file),
                    (p) => {
                        batchProgress.value = { ...p }
                    },
                )
            } else {
                // Use normal upload for small files
                await uploadSingleFile(file)
            }

            batchUploadedFiles.value++
        } catch (err: any) {
            if (progress.value.status === 'cancelled') break

            const msg = err.message || `Failed to upload ${file.name}`
            fileErrors.value[i] = msg

            // Store uploadId if it was a chunked upload (enables resume)
            const uploadId = file.size > CHUNK_UPLOAD_THRESHOLD ? lastUploadId.value : null
            newFailed.set(i, { file, index: i, uploadId, error: msg })
        }
    }

    failedFilesMap.value = newFailed
    batchStatus.value = `Upload complete! ${batchUploadedFiles.value} of ${batchTotalFiles.value} files uploaded.`
    batchProgress.value = null
    reset()

    const failedFileNames = Array.from(newFailed.values()).map((f) => f.file.name)

    uploadSummary.value = {
        success: batchUploadedFiles.value,
        failed: failedFileNames.length,
        failedFiles: failedFileNames,
    }

    // Only redirect on full success
    if (failedFileNames.length === 0) {
        router.visit(route('documents.index'), {
            only: [],
            onFinish: () => {
                selectedFiles.value = []
                form.reset()
            },
        })
    }
}

/**
 * Retry only the files that failed in the previous attempt.
 * For chunked uploads that have an upload_id, uses resume() to skip
 * already-uploaded chunks. Otherwise re-uploads from scratch.
 */
async function retryFailedFiles() {
    const failed = Array.from(failedFilesMap.value.values())
    if (failed.length === 0) return

    isRetrying.value = true
    const prevFailed = failedFilesMap.value
    const newFailed = new Map<number, FailedFile>()

    batchTotalFiles.value = failed.length
    batchUploadedFiles.value = 0
    errors.value = []
    fileErrors.value = {}
    uploadSummary.value = null

    for (let i = 0; i < failed.length; i++) {
        const entry = failed[i]
        const file = entry.file
        batchStatus.value = `Retrying file ${i + 1} of ${failed.length}: ${file.name}`

        try {
            if (file.size > CHUNK_UPLOAD_THRESHOLD) {
                if (entry.uploadId) {
                    // Resume interrupted chunked upload
                    batchStatus.value = `Resuming file ${i + 1} of ${failed.length}: ${file.name}`
                    await resume(
                        entry.uploadId,
                        file,
                        fileMetadata(file),
                        (p) => {
                            batchProgress.value = { ...p }
                        },
                    )
                } else {
                    // No upload_id available — restart from scratch
                    await upload(
                        file,
                        fileMetadata(file),
                        (p) => {
                            batchProgress.value = { ...p }
                        },
                    )
                }
            } else {
                await uploadSingleFile(file)
            }

            batchUploadedFiles.value++
        } catch (err: any) {
            if (progress.value.status === 'cancelled') break

            const msg = err.message || `Failed to upload ${file.name}`
            fileErrors.value[entry.index] = msg

            // Preserve the upload_id if this was a chunked upload
            const uploadId = file.size > CHUNK_UPLOAD_THRESHOLD ? lastUploadId.value : null
            newFailed.set(entry.index, { file, index: entry.index, uploadId, error: msg })
        }
    }

    failedFilesMap.value = newFailed
    batchStatus.value = `Retry complete! ${batchUploadedFiles.value} of ${failed.length} failed files uploaded.`
    batchProgress.value = null
    reset()
    isRetrying.value = false

    const failedFileNames = Array.from(newFailed.values()).map((f) => f.file.name)

    uploadSummary.value = {
        success: batchUploadedFiles.value,
        failed: failedFileNames.length,
        failedFiles: failedFileNames,
    }

    // Clear errors for successfully retried files
    const remainingErrors: Record<number, string> = {}
    for (const entry of newFailed.values()) {
        remainingErrors[entry.index] = entry.error
    }
    fileErrors.value = remainingErrors

    if (failedFileNames.length === 0) {
        router.visit(route('documents.index'), {
            only: [],
            onFinish: () => {
                selectedFiles.value = []
                form.reset()
            },
        })
    }
}

async function uploadSingleFile(file: File): Promise<void> {
    const formData = new FormData()
    formData.append('doc_upload', file)
    formData.append('doc_name', fileNameWithoutExt(file.name))
    formData.append('doc_title', fileNameWithoutExt(file.name))
    formData.append('category_id', form.category_id)
    if (form.description) formData.append('description', form.description)

    const res = await fetch(route('documents.store'), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'same-origin',
    })

    if (!res.ok) {
        const body = await res.json().catch(() => ({}))
        throw new Error(body.message || `Failed to upload ${file.name}`)
    }

    batchProgress.value = {
        uploadedBytes: file.size,
        totalBytes: file.size,
        percent: 100,
        uploadedChunks: 0,
        totalChunks: 0,
        currentChunk: 0,
        status: 'uploading',
        error: null,
        speed: 0,
        estimatedTimeLeft: 0,
    }
}

function hasLargeFiles(files: File[]): boolean {
    return files.some((f) => f.size > CHUNK_UPLOAD_THRESHOLD)
}

async function submitZip() {
    if (!zipForm.zip_file) return

    zipUploading.value = true
    zipProgress.value = 'Uploading ZIP file…'

    zipForm.post(route('documents.batch.zip'), {
        forceFormData: true,
        onFinish: () => {
            zipUploading.value = false
            zipProgress.value = ''
        },
        onSuccess: () => {
            zipForm.reset()
        },
    })
}

const handleCancel = () => {
    cancel()
    batchProgress.value = null
    batchStatus.value = 'Upload cancelled.'
}
</script>

<template>
    <Head title="Batch Create Documents" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl mx-auto p-6 bg-white rounded-lg shadow">

            <div class="mb-6">
                <Link
                    :href="route('documents.index')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                >
                    <ArrowLeft class="w-4 h-4" />
                    Back
                </Link>
            </div>

            <h1 class="text-2xl font-bold mb-6">Batch Create Documents</h1>

            <DocumentBatchForm
                :categories="categories"
                v-model:active-tab="activeTab"
                v-model:form="form"
                v-model:zip-form="zipForm"
                v-model:selected-files="selectedFiles"
                v-model:errors="errors"
                v-model:file-errors="fileErrors"
                v-model:is-dragging="isDragging"
            />

            <!-- Per-file errors -->
            <div v-if="showFileErrors" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start gap-2">
                    <AlertCircle class="w-5 h-5 text-red-500 mt-0.5 shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-red-800">Some files failed to upload:</p>
                        <ul class="mt-1 text-sm text-red-600 list-disc list-inside">
                            <li v-for="(msg, idx) in fileErrors" :key="idx">{{ msg }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Batch upload progress (chunked) -->
            <div v-if="batchStatus" class="mb-4">
                <div class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                    {{ batchStatus }}
                    <span v-if="batchTotalFiles > 1">
                        ({{ batchUploadedFiles }}/{{ batchTotalFiles }})
                    </span>
                </div>
                <UploadProgressBar
                    v-if="batchProgress"
                    :progress="batchProgress"
                    @cancel="handleCancel"
                />
            </div>

            <!-- ZIP upload progress -->
            <div v-if="zipUploading" class="mb-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ zipProgress }}</span>
                    </div>
                </div>
            </div>

            <!-- Post-upload summary with retry button -->
            <div v-if="uploadSummary && uploadSummary.failed > 0" class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <div class="flex items-start gap-2">
                    <AlertCircle class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" />
                    <div class="flex-1">
                        <p class="text-sm font-medium text-amber-800">
                            {{ uploadSummary.success }} uploaded, {{ uploadSummary.failed }} failed.
                        </p>
                        <ul class="mt-1 text-sm text-amber-700 list-disc list-inside">
                            <li v-for="name in uploadSummary.failedFiles" :key="name">{{ name }}</li>
                        </ul>
                        <div class="flex items-center gap-3 mt-3">
                            <button
                                type="button"
                                @click="retryFailedFiles"
                                :disabled="isRetrying || isUploading"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded disabled:opacity-50"
                            >
                                <RotateCcw class="w-3.5 h-3.5" :class="{ 'animate-spin': isRetrying }" />
                                {{ isRetrying ? 'Retrying...' : 'Retry failed files' }}
                            </button>
                            <button
                                type="button"
                                @click="uploadSummary = null; failedFilesMap.clear()"
                                class="text-sm text-amber-800 underline hover:text-amber-900"
                            >
                                Dismiss
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <DocumentBatchActions
                :active-tab="activeTab"
                :form-processing="form.processing || isUploading || isRetrying"
                :zip-form-processing="zipForm.processing || zipUploading"
                :selected-files-count="selectedFiles.length"
                :zip-form-has-file="!!zipForm.zip_file"
                @submit-files="submitFiles"
                @submit-zip="submitZip"
            />
        </div>
    </AppLayout>
</template>
