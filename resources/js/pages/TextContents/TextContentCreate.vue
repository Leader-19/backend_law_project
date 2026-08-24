<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { type BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'បង្កើតអត្ថបទ',
        href: '/text-contents',
    },
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
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">ចំណងជើង</label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Enter title"
                    />
                    <p v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</p>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">ប្រភេទ</label>
                    <select
                        v-model="form.category_id"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">-- Select Category --</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.title }}
                        </option>
                    </select>
                    <p v-if="form.errors.category_id" class="text-red-500 text-sm mt-1">{{ form.errors.category_id }}</p>
                </div>

                <!-- Body - Small textarea -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">អត្ថបទ (Body Text)</label>
                    <textarea
                        v-model="form.body"
                        rows="6"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        placeholder="Enter the text content here... This is the meaning/summary of the book."
                    ></textarea>
                    <p class="text-xs text-gray-400 mt-1">Enter the book text content or meaning here.</p>
                    <p v-if="form.errors.body" class="text-red-500 text-sm mt-1">{{ form.errors.body }}</p>
                </div>

                <!-- Submit -->
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
