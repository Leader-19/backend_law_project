<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import type { BreadcrumbItem } from '@/types'

interface Category {
    id: number
    name: string
}

interface Plan {
    id: number
    name: string
}

interface Book {
    id: number
    title: string
    name: string
    description: string | null
    file_path: string | null
    file_url: string | null
    category?: Category | null
    plans: Plan[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrator Book', href: route('administrator-books.index') },
    { title: 'Detail', href: '#' },
]

const page = usePage()
const book = page.props.book as Book

</script>

<template>
    <Head :title="book.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto p-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-6 py-5 border-b border-gray-200">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ book.title }}</h1>
                        <p class="text-sm text-gray-500 mt-1">by Administrator: {{ book.name }}</p>
                    </div>
                    <Link
                        :href="route('administrator-books.index')"
                        class="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900"
                    >
                        ← Back to list
                    </Link>
                </div>

                <!-- Body -->
                <div class="px-6 py-6 space-y-6">
                    <!-- Category + Plans -->
                    <div class="flex flex-wrap gap-3">
                        <span
                            v-if="book.category"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700"
                        >
                            Category: {{ book.category.name }}
                        </span>
                        <span
                            v-for="plan in book.plans"
                            :key="plan.id"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700"
                        >
                            Plan: {{ plan.name }}
                        </span>
                        <span
                            v-if="book.plans.length === 0"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-600"
                        >
                            Available to all subscribers
                        </span>
                    </div>

                    <!-- Description -->
                    <div>
                        <h2 class="text-sm font-semibold text-gray-700 mb-1">Description</h2>
                        <p class="text-sm text-gray-600 whitespace-pre-line">
                            {{ book.description || 'No description provided.' }}
                        </p>
                    </div>

                    <!-- Read action -->
                    <div v-if="book.file_path" class="pt-4 border-t border-gray-100">
                        <a
                            :href="route('administrator-books.read', book.id)"
                            class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700"
                        >
                            📖 Read / Download Book
                        </a>
                    </div>
                    <p v-else class="text-sm text-gray-500">No file has been attached to this book yet.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
