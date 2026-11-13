<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { computed, ref } from 'vue'
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
    category?: Category | null
    subscriptionPlans?: Plan[]
    created_at: string
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrator Book', href: route('administrator-books.index') },
]

const page = usePage()
const books = (page.props.books ?? []) as Book[]
const categories = (page.props.categories ?? []) as Category[]
const pagination = page.props.pagination as Pagination
const searchProp = (page.props.search ?? '') as string
const selectedCategoryIdProp = (page.props.selectedCategoryId ?? '') as string | number

const search = ref(searchProp)
const categoryFilter = ref<string | number>(selectedCategoryIdProp)

const flashSuccess = computed(() => (page.props.flash as { success?: string })?.success)
const flashError = computed(() => (page.props.flash as { error?: string })?.error)

const submitFilter = () => {
    router.get(route('administrator-books.index'), {
        search: search.value || undefined,
        category_id: categoryFilter.value || undefined,
    }, { preserveState: true })
}

const goToPage = (pageNo: number) => {
    router.get(route('administrator-books.index'), {
        page: pageNo,
        search: search.value || undefined,
        category_id: categoryFilter.value || undefined,
    }, { preserveState: true })
}

const deleteBook = (id: number) => {
    if (confirm('Are you sure you want to delete this book?')) {
        router.delete(route('administrator-books.destroy', id))
    }
}

const formatDate = (dateStr: string) =>
    new Date(dateStr).toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
</script>

<template>
    <Head title="Administrator Book" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-7xl mx-auto p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Administrator Book</h1>
                    <p class="text-sm text-gray-500 mt-1">Total {{ pagination.total }} books</p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('administrator-book-categories.index')"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                    >
                        Manage Categories
                    </Link>
                    <Link
                        :href="route('administrator-books.create')"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700"
                    >
                        + New Book
                    </Link>
                </div>
            </div>

            <!-- Flash messages -->
            <div v-if="flashSuccess" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ flashSuccess }}
            </div>
            <div v-if="flashError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ flashError }}
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by title or administrator name..."
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            @keyup.enter="submitFilter"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Category</label>
                        <select
                            v-model="categoryFilter"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            @change="submitFilter"
                        >
                            <option value="">All</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Administrator Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Plans</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="book in books" :key="book.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <Link
                                        :href="route('administrator-books.show', book.id)"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                    >
                                        {{ book.title }}
                                    </Link>
                                    <span v-if="!book.file_path" class="ml-1 text-xs text-gray-400">(no file)</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ book.name }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="book.category"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700"
                                    >
                                        {{ book.category.name }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        v-for="plan in (book.subscriptionPlans ?? [])"
                                        :key="plan.id"
                                        class="inline-flex items-center px-2 py-0.5 mr-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700"
                                    >
                                        {{ plan.name }}
                                    </span>
                                    <span v-if="(book.subscriptionPlans ?? []).length === 0" class="text-xs text-gray-400">All plans</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(book.created_at) }}</td>
                                <td class="px-4 py-3 text-right text-sm space-x-2">
                                    <a
                                        v-if="book.file_path"
                                        :href="route('administrator-books.read', book.id)"
                                        class="text-green-600 hover:text-green-800"
                                    >Read</a>
                                    <Link :href="route('administrator-books.edit', book.id)" class="text-blue-600 hover:text-blue-800">Edit</Link>
                                    <button @click="deleteBook(book.id)" class="text-red-600 hover:text-red-800">Delete</button>
                                </td>
                            </tr>

                            <tr v-if="books.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                    <p class="text-sm">No books found. Create your first book!</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <p class="text-sm text-gray-600">
                        Page {{ pagination.current_page }} of {{ pagination.last_page }}
                    </p>
                    <div class="flex gap-2">
                        <button
                            :disabled="pagination.current_page <= 1"
                            @click="goToPage(pagination.current_page - 1)"
                            class="px-3 py-1.5 text-sm rounded-lg bg-white border border-gray-300 disabled:opacity-40 hover:bg-gray-100"
                        >
                            Previous
                        </button>
                        <button
                            :disabled="pagination.current_page >= pagination.last_page"
                            @click="goToPage(pagination.current_page + 1)"
                            class="px-3 py-1.5 text-sm rounded-lg bg-white border border-gray-300 disabled:opacity-40 hover:bg-gray-100"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
