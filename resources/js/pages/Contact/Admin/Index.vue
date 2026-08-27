<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { MessageSquare, CheckCircle, Clock, XCircle, Trash2 } from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'

interface ContactMessage {
    id: number
    subject: string
    message: string
    status: string
    admin_reply: string | null
    created_at: string
    user: { id: number; name: string; email: string }
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contact Messages', href: '/contact-messages' },
]

const props = defineProps<{
    messages: { data: ContactMessage[] } & Pagination
    currentStatus: string
    counts: { all: number; open: number; replied: number; closed: number }
    search: string
}>()

function filterByStatus(status: string) {
    router.get(route('contact-messages.index'), { status, search: props.search }, { preserveState: true })
}

function deleteMessage(id: number) {
    if (confirm('Delete this message?')) {
        router.delete(route('contact-messages.destroy', id))
    }
}

function changePage(page: number) {
    router.get(route('contact-messages.index'), { status: props.currentStatus, page, search: props.search }, { preserveState: true })
}

function getStatusColor(status: string): string {
    return ({ open: 'text-yellow-600 bg-yellow-100', replied: 'text-green-600 bg-green-100', closed: 'text-gray-600 bg-gray-100' } as Record<string, string>)[status] || ''
}
</script>

<template>
    <Head title="Contact Messages" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">
            <div class="flex items-center gap-3 mb-6">
                <MessageSquare class="w-6 h-6 text-blue-600" />
                <h1 class="text-2xl font-bold text-gray-900">Contact Messages</h1>
            </div>

            <!-- Status Tabs -->
            <div class="flex gap-2 mb-6 flex-wrap">
                <button
                    v-for="(count, status) in counts"
                    :key="status"
                    @click="filterByStatus(status)"
                    :class="[
                        'px-4 py-2 text-sm rounded-lg capitalize transition flex items-center gap-2',
                        currentStatus === status
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                >
                    {{ status }}
                    <span :class="[
                        'text-xs px-1.5 py-0.5 rounded-full',
                        currentStatus === status ? 'bg-white/20' : 'bg-gray-200'
                    ]">
                        {{ count }}
                    </span>
                </button>
            </div>

            <!-- Messages Table -->
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Message</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Date</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="msg in messages.data" :key="msg.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ msg.subject }}</td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-gray-900">{{ msg.user.name }}</p>
                                <p class="text-xs text-gray-500">{{ msg.user.email }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ msg.message }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="['text-xs font-medium px-2 py-1 rounded-full', getStatusColor(msg.status)]">
                                    {{ msg.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ msg.created_at }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-2">
                                    <Link
                                        :href="route('contact-messages.show', msg.id)"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded"
                                        title="View & Reply"
                                    >
                                        <MessageSquare class="w-4 h-4" />
                                    </Link>
                                    <button @click="deleteMessage(msg.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded" title="Delete">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="messages.data.length === 0" class="text-center py-12">
                    <MessageSquare class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">No messages found.</p>
                </div>
            </div>

            <div v-if="messages.last_page > 1" class="mt-6 flex justify-center gap-2">
                <button
                    v-for="page in messages.last_page"
                    :key="page"
                    @click="changePage(page)"
                    :class="[
                        'px-3 py-1 rounded text-sm',
                        page === messages.current_page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                >
                    {{ page }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
