<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { type BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'អត្ថបទ',
        href: '/text-contents',
    },
]

const page = usePage()
const textContent = page.props.textContent as any
</script>

<template>
    <Head title="ព័ត៌មានអត្ថបទ" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl mx-auto p-6 bg-white rounded-lg shadow">

            <div class="mb-6">
                <Link
                    :href="route('text-contents.index')"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    ← Back
                </Link>
            </div>

            <h1 class="text-xl font-bold mb-2">{{ textContent.title }}</h1>

            <div class="mb-4 flex items-center gap-3">
                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">
                    {{ textContent.category?.title || 'No Category' }}
                </span>
                <span class="text-xs text-gray-400">
                    {{ new Date(textContent.created_at).toLocaleDateString() }}
                </span>
            </div>

            <div class="border-t pt-4">
                <h2 class="text-sm font-semibold text-gray-600 mb-2">អត្ថបទ (Body)</h2>
                <div class="prose prose-sm max-w-none text-sm text-gray-800 leading-relaxed" v-html="textContent.body"></div>
            </div>

            <div class="mt-6 flex gap-3">
                <Link
                    :href="route('text-contents.edit', textContent.id)"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700"
                >
                    Edit
                </Link>
                <Link
                    :href="route('text-contents.index')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300"
                >
                    Back to list
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
