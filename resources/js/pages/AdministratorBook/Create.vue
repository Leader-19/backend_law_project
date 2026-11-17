<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import BookFormFields from '@/components/administrator-books/BookFormFields.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import type { BreadcrumbItem } from '@/types'

interface Category {
    id: number
    name: string
}

interface Plan {
    id: number
    name: string
    price: string
    currency: string
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrator Book', href: route('administrator-books.index') },
    { title: 'Create', href: '#' },
]

const page = usePage()
const categories = (page.props.categories ?? []) as Category[]
const plans = (page.props.plans ?? []) as Plan[]

const form = useForm({
    title: '',
    name: '',
    description: '',
    administrator_book_category_id: '',
    file: null as File | null,
    plan_ids: [] as number[],
})

const onFileChange = (file: File | null) => {
    form.file = file
}

const submit = () => {
    form.post(route('administrator-books.store'), { forceFormData: true })
}
</script>

<template>
    <Head title="Create Administrator Book" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto p-6 bg-white rounded-xl shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900">Create Book</h1>
                <Link
                    :href="route('administrator-books.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Back
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
                <BookFormFields
                    :form="form"
                    :categories="categories"
                    :plans="plans"
                    :processing="form.processing"
                    :is-edit="false"
                    submit-label="Create"
                    @file-change="onFileChange"
                />

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <Link
                        :href="route('administrator-books.index')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ form.processing ? 'Creating...' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
