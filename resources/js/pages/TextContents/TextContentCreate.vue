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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'អត្ថបទ', href: route('text-contents.index') },
    { title: 'បង្កើតអត្ថបទ', href: route('text-contents.create') },
]

const page = usePage()
const categories = page.props.categories as Category[]

const form = useForm({
    title: '',
    body: '',
    category_id: '' as string | number,
})

const submit = () => {
    form.post(route('text-contents.store'), {
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <Head title="បង្កើតអត្ថបទ" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto p-6 bg-white rounded-xl shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900">បង្កើតអត្ថបទថ្មី</h1>
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
                    submit-label="បង្កើត"
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
                        class="px-5 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ form.processing ? 'កំពុងរក្សាទុក...' : 'បង្កើត' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
