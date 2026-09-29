<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, FileText, Plus, Pencil, Trash2 } from 'lucide-vue-next'
import { route } from 'ziggy-js'
import ConfirmModal from '@/components/ConfirmModal.vue'
import { ref } from 'vue'

const props = defineProps<{
    category: {
        id: number
        title: string
        description: string | null
        parent_id: number | null
        documents_count: number
    }
    documents: Array<{
        id: number
        doc_name: string
        doc_title: string
        description: string
        doc_upload: string
        image: string
        created_at: string
    }>
    pagination: {
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    user_permissions: string[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: props.category.title, href: `/categories/${props.category.id}/dashboard` },
]

const isDeleteOpen = ref(false)
const deletingId = ref<number | null>(null)

function deleteDocument() {
    if (deletingId.value === null) return
    router.delete(route('documents.destroy', deletingId.value), {
        onSuccess: () => {
            isDeleteOpen.value = false
            deletingId.value = null
        },
    })
}

function confirmDeleteDocument(id: number) {
    deletingId.value = id
    isDeleteOpen.value = true
}

const canManage = props.user_permissions.some(p => {
    const perms = p.split(',').map(s => s.trim())
    return perms.includes('manage') || perms.includes('edit') || perms.includes('delete')
})
</script>

<template>
    <Head :title="`${props.category.title} - Dashboard`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto max-w-8xl space-y-6 p-4 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <Link v-if="props.category.parent_id" :href="`/categories/${props.category.parent_id}/dashboard`" class="rounded-lg p-2 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <ArrowLeft class="h-5 w-5 text-slate-500" />
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ props.category.title }}</h1>
                            <p v-if="props.category.description" class="mt-1 text-sm text-slate-500">{{ props.category.description }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-[5px] border border-slate-200 bg-white px-4 py-2 text-sm font-medium shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <FileText class="h-4 w-4 text-blue-500" />
                        {{ props.category.documents_count }} items
                    </span>
                    <Link v-if="canManage" :href="`/documents/create?category_id=${props.category.id}`" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        <Plus class="h-4 w-4" />
                        Add Item
                    </Link>
                </div>
            </div>

            <section class="overflow-hidden rounded-[5px] border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead class="border-b text-xs uppercase text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Title</th>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3">Created</th>
                                <th v-if="canManage" class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr v-for="doc in documents" :key="doc.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                            <FileText class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <span class="font-medium text-slate-900 dark:text-white">{{ doc.doc_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">{{ doc.doc_title }}</td>
                                <td class="px-6 py-4">
                                    <span class="line-clamp-1">{{ doc.description || '-' }}</span>
                                </td>
                                <td class="px-6 py-4">{{ doc.created_at }}</td>
                                <td v-if="canManage" class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/documents/${doc.id}`" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700" title="View">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </Link>
                                        <Link :href="`/documents/${doc.id}/edit`" class="rounded-lg p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/30" title="Edit">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button type="button" @click="confirmDeleteDocument(doc.id)" class="rounded-lg p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30" title="Delete">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!documents.length">
                                <td :colspan="canManage ? 5 : 4" class="px-6 py-8 text-center text-sm text-slate-400">
                                    No items found in this category.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="pagination.last_page > 1" class="flex items-center justify-between border-t border-slate-200 px-6 py-4 dark:border-slate-700 text-sm">
                    <span class="text-slate-500">{{ pagination.total }} items</span>
                    <div class="flex gap-2">
                        <Link :href="`/categories/${category.id}/dashboard?page=${pagination.current_page - 1}`" :class="['rounded-lg border border-slate-300 px-3 py-1.5', { 'opacity-50 pointer-events-none': pagination.current_page <= 1 }]" class="hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Previous</Link>
                        <span class="px-3 py-1.5 text-slate-600 dark:text-slate-300">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
                        <Link :href="`/categories/${category.id}/dashboard?page=${pagination.current_page + 1}`" :class="['rounded-lg border border-slate-300 px-3 py-1.5', { 'opacity-50 pointer-events-none': pagination.current_page >= pagination.last_page }]" class="hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Next</Link>
                    </div>
                </div>
            </section>

            <ConfirmModal
                :open="isDeleteOpen"
                title="Delete Item"
                description="Are you sure you want to delete this item? This action cannot be undone."
                confirm-label="Delete"
                @confirm="deleteDocument"
                @update:open="isDeleteOpen = $event"
            />
        </main>
    </AppLayout>
</template>
