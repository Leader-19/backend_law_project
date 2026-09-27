<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { BookMarked, Trash2, FileText } from '@lucide/vue'
import { type BreadcrumbItem } from '@/types'

interface LibraryItem {
    id: number
    doc_name: string
    doc_title: string
    description: string | null
    doc_upload: string
    image: string | null
    category: { id: number; title: string } | null
    created_at: string
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Library', href: '/library' },
]

const props = defineProps<{
    libraryItems: { data: LibraryItem[] } & Pagination
}>()

function removeFromLibrary(documentId: number) {
    if (confirm('Remove this document from your library?')) {
        router.delete(route('library.destroy', documentId))
    }
}

function changePage(page: number) {
    router.get(route('library.index'), { page }, { preserveState: true })
}
</script>

<template>
    <Head title="My Library" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">
            <div class="flex items-center gap-3 mb-6">
                <BookMarked class="w-6 h-6 text-blue-600" />
                <h1 class="text-2xl font-bold text-gray-900">My Library</h1>
                <span class="text-sm text-gray-500">({{ libraryItems.total }} items)</span>
            </div>

            <div v-if="libraryItems.data.length === 0" class="text-center py-12">
                <BookMarked class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500 text-lg">Your library is empty.</p>
                <Link :href="route('documents.index')" class="mt-4 inline-block text-blue-600 hover:underline">
                    Browse documents to add
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="item in libraryItems.data"
                    :key="item.id"
                    class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition"
                >
                    <div v-if="item.image" class="h-40 overflow-hidden bg-gray-100">
                        <img :src="`/storage/${item.image}`" :alt="item.doc_name" class="w-full h-full object-cover" />
                    </div>
                    <div v-else class="h-40 bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center">
                        <FileText class="w-12 h-12 text-blue-200" />
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 mb-1">{{ item.doc_title }}</h3>
                        <p class="text-sm text-gray-500 mb-2">{{ item.doc_name }}</p>
                        <p v-if="item.category" class="text-xs text-blue-600 bg-blue-50 inline-block px-2 py-1 rounded mb-2">
                            {{ item.category.title }}
                        </p>
                        <p v-if="item.description" class="text-sm text-gray-600 mb-3 line-clamp-2">
                            {{ item.description }}
                        </p>
                        <div class="flex justify-between items-center mt-3">
                            <Link
                                :href="route('documents.show', item.id)"
                                class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline"
                            >
                                <FileText class="w-4 h-4" /> Read
                            </Link>
                            <button
                                @click="removeFromLibrary(item.id)"
                                class="inline-flex items-center gap-1 text-sm text-red-600 hover:text-red-800"
                            >
                                <Trash2 class="w-4 h-4" /> Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="libraryItems.last_page > 1" class="mt-6 flex justify-center gap-2">
                <button
                    v-for="page in libraryItems.last_page"
                    :key="page"
                    @click="changePage(page)"
                    :class="[
                        'px-3 py-1 rounded text-sm',
                        page === libraryItems.current_page
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                >
                    {{ page }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
