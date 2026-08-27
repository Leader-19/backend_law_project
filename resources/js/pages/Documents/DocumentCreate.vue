<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref } from 'vue'
import { type BreadcrumbItem } from '@/types'
import DocumentFormFields from '@/components/documents/DocumentFormFields.vue'

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

const submit = () => {
    form.post(route('documents.store'), {
        forceFormData: true,

        onSuccess: () => {
            form.reset()
        },
    })
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
                    :processing="form.processing"
                    submit-label="Create Document"
                />
            </form>
        </div>
    </AppLayout>
</template>