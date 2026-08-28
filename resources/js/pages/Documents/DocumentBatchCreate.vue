<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft } from 'lucide-vue-next'
import { ref } from 'vue'
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
const { progress, isUploading, upload, cancel, reset } = useChunkUpload()
const batchProgress = ref<UploadProgress | null>(null)
const batchStatus = ref('')
const batchTotalFiles = ref(0)
const batchUploadedFiles = ref(0)

function hasLargeFiles(files: File[]): boolean {
    return files.some((f) => f.size > CHUNK_UPLOAD_THRESHOLD)
}

async function submitFiles() {
    if (selectedFiles.value.length === 0) {
        errors.value = ['Please select at least one file.']
        return
    }

    // If any file is large, use chunked upload for each
    if (hasLargeFiles(selectedFiles.value)) {
        const filesToUpload = selectedFiles.value
        batchTotalFiles.value = filesToUpload.length
        batchUploadedFiles.value = 0
        errors.value = []

        for (let i = 0; i < filesToUpload.length; i++) {
            const file = filesToUpload[i]
            batchStatus.value = `Uploading file ${i + 1} of ${filesToUpload.length}: ${file.name}`

            try {
                if (file.size > CHUNK_UPLOAD_THRESHOLD) {
                    // Use chunked upload for large files
                    await upload(
                        file,
                        {
                            doc_name: file.name.replace(/\.[^/.]+$/, ''),
                            doc_title: file.name.replace(/\.[^/.]+$/, ''),
                            category_id: form.category_id,
                            description: form.description || undefined,
                        },
                        (p) => {
                            batchProgress.value = { ...p }
                        },
                    )
                } else {
                    // Use normal upload for small files
                    const smallForm = new FormData()
                    smallForm.append('doc_upload', file)
                    smallForm.append('doc_name', file.name.replace(/\.[^/.]+$/, ''))
                    smallForm.append('doc_title', file.name.replace(/\.[^/.]+$/, ''))
                    smallForm.append('category_id', form.category_id)
                    if (form.description) smallForm.append('description', form.description)

                    const res = await fetch(route('documents.store'), {
                        method: 'POST',
                        body: smallForm,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-XSRF-TOKEN': decodeURIComponent(
                                (document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
                            ),
                        },
                        credentials: 'same-origin',
                    })

                    if (!res.ok) {
                        throw new Error(`Failed to upload ${file.name}`)
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

                batchUploadedFiles.value++
            } catch (err: any) {
                if (progress.value.status === 'cancelled') break
                fileErrors.value[i] = err.message || `Failed to upload ${file.name}`
            }
        }

        batchStatus.value = `Upload complete! ${batchUploadedFiles.value} of ${batchTotalFiles.value} files uploaded.`
        batchProgress.value = null
        reset()

        // Redirect to documents index
        router.visit(route('documents.index'), {
            only: [],
            onFinish: () => {
                selectedFiles.value = []
                form.reset()
            },
        })
        return
n    }

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

function submitZip() {
    if (!zipForm.zip_file) return

    zipForm.post(route('documents.batch.zip'), {
        forceFormData: true,
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

            <!-- Batch upload progress -->
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

            <DocumentBatchActions
                :active-tab="activeTab"
                :form-processing="form.processing || isUploading"
                :zip-form-processing="zipForm.processing"
                :selected-files-count="selectedFiles.length"
                :zip-form-has-file="!!zipForm.zip_file"
                @submit-files="submitFiles"
                @submit-zip="submitZip"
            />
        </div>
    </AppLayout>
</template>