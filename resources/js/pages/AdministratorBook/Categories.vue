<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { computed, reactive, ref } from 'vue'
import type { BreadcrumbItem } from '@/types'

interface BookCategory {
    id: number
    name: string
    description: string | null
    books_count: number
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrator Book', href: route('administrator-books.index') },
    { title: 'Categories', href: route('administrator-book-categories.index') },
]

const page = usePage()
const categories = (page.props.categories ?? []) as BookCategory[]
const pagination = page.props.pagination as Pagination
const searchProp = (page.props.search ?? '') as string

const search = ref(searchProp)
const editing = reactive<{ id: number | null; name: string; description: string }>({
    id: null,
    name: '',
    description: '',
})

const flashSuccess = computed(() => (page.props.flash as { success?: string })?.success)
const flashError = computed(() => (page.props.flash as { error?: string })?.error)

const createForm = reactive({ name: '', description: '' })

const submitFilter = () => {
    router.get(route('administrator-book-categories.index'), {
        search: search.value || undefined,
    }, { preserveState: true })
}

const createCategory = () => {
    router.post(route('administrator-book-categories.store'), {
        name: createForm.name,
        description: createForm.description,
    }, {
        onSuccess: () => {
            createForm.name = ''
            createForm.description = ''
        },
    })
}

const startEdit = (cat: BookCategory) => {
    editing.id = cat.id
    editing.name = cat.name
    editing.description = cat.description ?? ''
}

const cancelEdit = () => {
    editing.id = null
    editing.name = ''
    editing.description = ''
}

const updateCategory = () => {
    if (editing.id === null) return
    router.put(route('administrator-book-categories.update', editing.id), {
        name: editing.name,
        description: editing.description,
    }, {
        onSuccess: cancelEdit,
    })
}

const deleteCategory = (id: number) => {
    if (confirm('Delete this category? (Categories with books cannot be deleted)' )) {
        router.delete(route('administrator-book-categories.destroy', id))
    }
}

const goToPage = (pageNo: number) => {
    router.get(route('administrator-book-categories.index'), {
        page: pageNo,
        search: search.value || undefined,
    }, { preserveState: true })
}
</script>

<template>
    <Head title="Administrator Book Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Book Categories</h1>
                    <p class="text-sm text-gray-500 mt-1">Total {{ pagination.total }} categories</p>
                </div>
                <Link
                    :href="route('administrator-books.index')"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                >
                    ← Back to books
                </Link>
            </div>

            <!-- Flash messages -->
            <div v-if="flashSuccess" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ flashSuccess }}
            </div>
            <div v-if="flashError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ flashError }}
            </div>

            <!-- Create -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">New Category</h2>
                <form class="flex flex-col sm:flex-row gap-3" @submit.prevent="createCategory">
                    <input
                        v-model="createForm.name"
                        type="text"
                        required
                        placeholder="Category name"
                        class="flex-1 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <input
                        v-model="createForm.description"
                        type="text"
                        placeholder="Description (optional)"
                        class="flex-1 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700"
                    >
                        + Add
                    </button>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search categories..."
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        @keyup.enter="submitFilter"
                        @input="submitFilter"
                    />
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Books</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50 transition-colors">
                                <template v-if="editing.id === cat.id">
                                    <td class="px-4 py-3">
                                        <input
                                            v-model="editing.name"
                                            type="text"
                                            required
                                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input
                                            v-model="editing.description"
                                            type="text"
                                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-500">{{ cat.books_count }}</td>
                                    <td class="px-4 py-3 text-right text-sm space-x-2">
                                        <button @click="updateCategory" class="text-green-600 hover:text-green-800">Save</button>
                                        <button @click="cancelEdit" class="text-gray-500 hover:text-gray-700">Cancel</button>
                                    </td>
                                </template>
                                <template v-else>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ cat.name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ cat.description ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-500">{{ cat.books_count }}</td>
                                    <td class="px-4 py-3 text-right text-sm space-x-2">
                                        <button @click="startEdit(cat)" class="text-blue-600 hover:text-blue-800">Edit</button>
                                        <button
                                            :disabled="cat.books_count > 0"
                                            class="text-red-600 hover:text-red-800 disabled:opacity-40 disabled:cursor-not-allowed"
                                            @click="deleteCategory(cat.id)"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </template>
                            </tr>

                            <tr v-if="categories.length === 0">
                                <td colspan="4" class="px-4 py-12 text-center text-gray-500">
                                    <p class="text-sm">No categories yet. Create one above!</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <p class="text-sm text-gray-600">Page {{ pagination.current_page }} of {{ pagination.last_page }}</p>
                    <div class="flex gap-2">
                        <button
                            :disabled="pagination.current_page <= 1"
                            class="px-3 py-1.5 text-sm rounded-lg bg-white border border-gray-300 disabled:opacity-40 hover:bg-gray-100"
                            @click="goToPage(pagination.current_page - 1)"
                        >
                            Previous
                        </button>
                        <button
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="px-3 py-1.5 text-sm rounded-lg bg-white border border-gray-300 disabled:opacity-40 hover:bg-gray-100"
                            @click="goToPage(pagination.current_page + 1)"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
