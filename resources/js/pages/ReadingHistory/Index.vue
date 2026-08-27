<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { Clock, FileText } from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'

interface HistoryItem {
    id: number
    last_page: number
    total_pages: number
    progress_percent: number
    last_opened_at: string | null
    document: {
        id: number
        doc_name: string
        doc_title: string
        image: string | null
        category: { id: number; title: string } | null
    }
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reading History', href: '/reading-history' },
]

const props = defineProps<{
    history: { data: HistoryItem[] } & Pagination
}>()

function changePage(page: number) {
    router.get(route('reading-history.index'), { page }, { preserveState: true })
}
</script>

<template>
    <Head title="Reading History" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">
            <div class="flex items-center gap-3 mb-6">
                <Clock class="w-6 h-6 text-blue-600" />
                <h1 class="text-2xl font-bold text-gray-900">Reading History</h1>
                <span class="text-sm text-gray-500">({{ history.total }} items)</span>
            </div>

            <div v-if="history.data.length === 0" class="text-center py-12">
                <Clock class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500 text-lg">No reading history yet.</p>
                <Link :href="route('documents.index')" class="mt-4 inline-block text-blue-600 hover:underline">
                    Start reading documents
                </Link>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="item in history.data"
                    :key="item.id"
                    class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition"
                >
                    <div class="flex items-start gap-4">
                        <div v-if="item.document.image" class="w-16 h-16 rounded overflow-hidden flex-shrink-0">
                            <img :src="`/storage/${item.document.image}`" :alt="item.document.doc_name" class="w-full h-full object-cover" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <Link :href="route('documents.show', item.document.id)" class="font-semibold text-gray-900 hover:text-blue-600">
                                {{ item.document.doc_title }}
                            </Link>
                            <p class="text-sm text-gray-500">{{ item.document.doc_name }}</p>
                            <p v-if="item.document.category" class="text-xs text-blue-600 bg-blue-50 inline-block px-2 py-1 rounded mt-1">
                                {{ item.document.category.title }}
                            </p>

                            <!-- Progress Bar -->
                            <div class="mt-3">
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Page {{ item.last_page }} of {{ item.total_pages }}</span>
                                    <span class="font-medium text-gray-700">{{ item.progress_percent }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div
                                        class="bg-blue-600 h-2 rounded-full transition-all"
                                        :style="{ width: `${Math.min(item.progress_percent, 100)}%` }"
                                    ></div>
                                </div>
                            </div>

                            <p v-if="item.last_opened_at" class="text-xs text-gray-400 mt-2">
                                Last opened: {{ item.last_opened_at }}
                            </p>
                        </div>
                        <Link
                            :href="route('documents.show', item.document.id)"
                            class="flex-shrink-0 p-2 bg-blue-50 text-blue-600 rounded hover:bg-blue-100"
                        >
                            <FileText class="w-5 h-5" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="history.last_page > 1" class="mt-6 flex justify-center gap-2">
                <button
                    v-for="page in history.last_page"
                    :key="page"
                    @click="changePage(page)"
                    :class="[
                        'px-3 py-1 rounded text-sm',
                        page === history.current_page
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
