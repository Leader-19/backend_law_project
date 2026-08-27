<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { type BreadcrumbItem } from '@/types'
import TextContentFormFields from '@/components/text-contents/TextContentFormFields.vue'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'កែសម្រួលអត្ថបទ', href: '/text-contents' },
]

const page = usePage()
const textContent = page.props.textContent as any
const categories = page.props.categories as any[]

const form = useForm({
    title: textContent.title,
    body: textContent.body,
    category_id: textContent.category_id,
})

const submit = () => {
    form.put(route('text-contents.update', textContent.id), {
        onSuccess: () => {
            // stay on page or redirect
        },
    })
}
</script>

<template>
    <Head title="កែសម្រួលអត្ថបទ" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow">

            <div class="mb-6">
                <Link
                    :href="route('text-contents.index')"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    ← Back
                </Link>
            </div>

            <h1 class="text-lg font-semibold mb-4">កែសម្រួលអត្ថបទ</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <TextContentFormFields
                    :form="form"
                    :categories="categories"
                    :processing="form.processing"
                    submit-label="រក្សាទុក"
                    :is-edit="true"
                />

                <div class="flex justify-end gap-3">
                    <Link
                        :href="route('text-contents.index')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300"
                    >
                        បោះបង់
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'រក្សាទុក' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
