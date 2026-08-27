<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { type BreadcrumbItem } from '@/types'
import TextContentFormFields from '@/components/text-contents/TextContentFormFields.vue'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'បង្កើតអត្ថបទ', href: '/text-contents' },
]

const page = usePage()
const categories = page.props.categories as any[]

const form = useForm({
    title: '',
    body: '',
    category_id: '',
})

const submit = () => {
    form.post(route('text-contents.store'), {
        onSuccess: () => {
            form.reset()
        },
    })
}
</script>

<template>
    <Head title="បង្កើតអត្ថបទ" />

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

            <h1 class="text-lg font-semibold mb-4">បង្កើតអត្ថបទថ្មី</h1>

            <form @submit.prevent="submit" class="space-y-5">
                <TextContentFormFields
                    :form="form"
                    :categories="categories"
                    :processing="form.processing"
                    submit-label="បង្កើត"
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
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded hover:bg-green-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'បង្កើត' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
