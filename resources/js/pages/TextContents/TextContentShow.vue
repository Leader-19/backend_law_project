<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import type { BreadcrumbItem } from '@/types'

interface Category {
    id: number
    title: string
}

interface TextContent {
    id: number
    title: string
    body: string
    category?: Category | null
    created_at: string
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'អត្ថបទ', href: route('text-contents.index') },
    { title: 'ព័ត៌មាន', href: '#' },
]

const page = usePage()
const textContent = page.props.textContent as TextContent

const formattedDate = new Date(textContent.created_at).toLocaleDateString('km-KH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
})
</script>

<template>
    <Head :title="textContent.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto p-6 bg-white rounded-xl shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ textContent.title }}</h1>
                    <div class="mt-2 flex items-center gap-3 text-sm text-gray-500">
                        <span
                            v-if="textContent.category"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                        >
                            {{ textContent.category.title }}
                        </span>
                        <span v-else class="text-gray-400">គ្មានប្រភេទ</span>
                        <span>•</span>
                        <span>{{ formattedDate }}</span>
                    </div>
                </div>

                <Link
                    :href="route('text-contents.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900"
                >
                    ← ត្រឡប់ក្រោយ
                </Link>
            </div>

            <div class="border-t border-gray-100 pt-6">
                <h2 class="text-sm font-medium text-gray-500 mb-3">អត្ថបទ</h2>
                <div
                    class="prose prose-sm max-w-none text-gray-800 leading-relaxed"
                    v-html="textContent.body"
                ></div>
            </div>

            <div class="mt-8 pt-6 border-t flex gap-3">
                <Link
                    :href="route('text-contents.edit', textContent.id)"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                >
                    កែសម្រួល
                </Link>
                <Link
                    :href="route('text-contents.index')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                >
                    ត្រឡប់ទៅបញ្ជី
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
