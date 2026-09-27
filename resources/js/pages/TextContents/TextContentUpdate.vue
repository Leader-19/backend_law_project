<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import type { BreadcrumbItem } from '@/types'
import TextContentFormFields from '@/components/text-contents/TextContentFormFields.vue'

interface Category {
    id: number
    title: string
}

interface TextContent {
    id: number
    title: string
    body: string
    category_id: number | null
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'អត្ថបទ', href: route('text-contents.index') },
    { title: 'កែសម្រួល', href: '#' },
]

const page = usePage()
const textContent = page.props.textContent as TextContent
const categories = page.props.categories as Category[]

const form = useForm({
    title: textContent.title,
    body: textContent.body,
    category_id: textContent.category_id ?? '',
})

const submit = () => {
    form.put(route('text-contents.update', textContent.id))
}
</script>

<template>
    <Head title="កែសម្រួលអត្ថបទ" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto p-6 bg-white rounded-xl shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900">កែសម្រួលអត្ថបទ</h1>
                <Link
                    :href="route('text-contents.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900"
                >
                    ← ត្រឡប់ក្រោយ
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <TextContentFormFields
                    v-model:form="form"
                    :categories="categories"
                    :processing="form.processing"
                    submit-label="រក្សាទុក"
                    :is-edit="true"
                />

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <Link
                        :href="route('text-contents.index')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                    >
                        បោះបង់
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ form.processing ? 'កំពុងរក្សាទុក...' : 'រក្សាទុក' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
