<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft } from 'lucide-vue-next'
import { ref } from 'vue'
import { type BreadcrumbItem } from '@/types'
import DocumentBatchForm from '@/components/documents/DocumentBatchForm.vue'
import DocumentBatchActions from '@/components/documents/DocumentBatchActions.vue'

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

function submitFiles() {
    if (selectedFiles.value.length === 0) {
        errors.value = ['Please select at least one file.']
        return
    }

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

            <DocumentBatchActions
                :active-tab="activeTab"
                :form-processing="form.processing"
                :zip-form-processing="zipForm.processing"
                :selected-files-count="selectedFiles.length"
                :zip-form-has-file="!!zipForm.zip_file"
                @submit-files="submitFiles"
                @submit-zip="submitZip"
            />
        </div>
    </AppLayout>
</template>