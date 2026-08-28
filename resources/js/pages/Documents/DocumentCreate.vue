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
        <div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow">

            <div class="mb-6">
                <Link
                    :href="route('documents.index')"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    ← Back
                </Link>
            </div>

            <form
                @submit.prevent="submit"
                class="space-y-5"
            >
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
    </AppLayout>
</template>