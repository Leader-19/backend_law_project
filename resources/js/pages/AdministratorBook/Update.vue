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

interface Book {
    id: number
    title: string
    name: string
    description: string | null
    administrator_book_category_id: number | null
    file_path: string | null
    file_url: string | null
    plan_ids: number[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrator Book', href: route('administrator-books.index') },
    { title: 'Edit', href: '#' },
]

const page = usePage()
const book = page.props.book as Book
const categories = (page.props.categories ?? []) as Category[]
const plans = (page.props.plans ?? []) as Plan[]

const form = useForm({
    title: book.title,
    name: book.name,
    description: book.description ?? '',
    administrator_book_category_id: book.administrator_book_category_id ?? '',
    file: null as File | null,
    plan_ids: book.plan_ids as number[],
})

const onFileChange = (file: File | null) => {
    form.file = file
}

const submit = () => {
    form.put(route('administrator-books.update', book.id), { forceFormData: true })
}
</script>

<template>
    <Head title="Edit Administrator Book" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto p-6 bg-white rounded-xl shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900">Edit Book</h1>
                <Link
                    :href="route('administrator-books.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Back
                </Link>
            </div>

            <!-- Currently uploaded file -->
            <div
                v-if="book.file_path"
                class="mb-6 flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700"
            >
                <span class="font-medium">Current file:</span>
                <a v-if="book.file_url" :href="book.file_url" target="_blank" class="text-blue-600 hover:underline truncate">
                    {{ book.file_path }}
                </a>
                <span class="text-xs text-gray-400">(upload a new file below to replace it)</span>
            </div>

            <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
                <BookFormFields
                    :form="form"
                    :categories="categories"
                    :plans="plans"
                    :processing="form.processing"
                    :is-edit="true"
                    submit-label="Update"
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
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
