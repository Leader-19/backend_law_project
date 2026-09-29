<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref } from 'vue'
import { type BreadcrumbItem } from '@/types'
import DocumentFormFields from '@/components/documents/DocumentFormFields.vue'
import UploadProgressBar from '@/components/documents/UploadProgressBar.vue'
import { useChunkUpload, CHUNK_UPLOAD_THRESHOLD } from '@/composables/useChunkUpload'
import type { UploadProgress } from '@/composables/useChunkUpload'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Create Document',
        href: '/documents',
    },
]

const page = usePage()
const categories = page.props.categories as any[]

const form = useForm({
    doc_name: '',
    doc_title: '',
    description: '',
    category_id: '',
    doc_upload: null as File | null,
    image: null as File | null,
})

const { progress, isUploading, upload, cancel, reset } = useChunkUpload()
const uploadProgress = ref<UploadProgress | null>(null)

const submit = async () => {
    const file = form.doc_upload

    // Use chunked upload for large files
    if (file && file.size > CHUNK_UPLOAD_THRESHOLD) {
        try {
            await upload(
                file,
                {
                    doc_name: form.doc_name,
                    doc_title: form.doc_title,
                    category_id: form.category_id,
                    description: form.description || undefined,
                },
                (p) => {
                    uploadProgress.value = { ...p }
                },
            )
            // On success, redirect to the documents index
            router.visit(route('documents.index'), {
                only: [],
                onFinish: () => {
                    form.reset()
                    uploadProgress.value = null
                    reset()
                },
            })
        } catch (err) {
            // Error is already captured in progress state
            uploadProgress.value = { ...progress.value }
        }
        return
    }

    // Use normal Inertia upload for small files
    form.post(route('documents.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset()
        },
    })
}

const handleCancel = () => {
    cancel()
    uploadProgress.value = null
}
</script>

<template>
    <Head title="Create Document" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-8xl mx-auto p-4 sm:p-6">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <Link
                    :href="route('documents.index')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-600 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-[5px] hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm"
                >
                    ← Back to Documents
                </Link>
            </div>

            <!-- Form Card -->
            <div class="rounded-[5px] border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <h1 class="text-lg font-bold text-slate-900 dark:text-white">Create New Document</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Fill in the details below to upload a new document.</p>
                </div>

                <form @submit.prevent="submit" class="p-6">
                    <DocumentFormFields
                        :form="form"
                        :categories="categories"
                        :processing="form.processing || isUploading"
                        submit-label="Create Document"
                    />

                    <UploadProgressBar
                        v-if="uploadProgress"
                        :progress="uploadProgress"
                        @cancel="handleCancel"
                    />
                </form>
            </div>
        </div>
    </AppLayout>
</template>