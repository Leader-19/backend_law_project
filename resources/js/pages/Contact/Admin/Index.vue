<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { MessageSquare, Trash2 } from '@lucide/vue'
import DataTable from '@/components/ui/data-table/DataTable.vue'
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
            <DataTable
                :data="messages.data"
                :pagination="{ current_page: messages.current_page, last_page: messages.last_page, per_page: messages.per_page || 15, total: messages.total }"
                :columns="['subject', 'user', 'message', 'status', 'date', 'actions']"
                @page-change="changePage"
            >
                <template #header-subject>Subject</template>
                <template #header-user>User</template>
                <template #header-message>Message</template>
                <template #header-status>Status</template>
                <template #header-date>Date</template>
                <template #header-actions>Actions</template>

                <template #subject="{ item }">
                    <span class="font-medium text-gray-900">{{ item.subject }}</span>
                </template>

                <template #user="{ item }">
                    <p class="text-sm text-gray-900">{{ item.user.name }}</p>
                    <p class="text-xs text-gray-500">{{ item.user.email }}</p>
                </template>

                <template #message="{ item }">
                    <span class="text-sm text-gray-500 max-w-xs truncate block">{{ item.message }}</span>
                </template>

                <template #status="{ item }">
                    <span :class="['text-xs font-medium px-2 py-1 rounded-full', getStatusColor(item.status)]">
                        {{ item.status }}
                    </span>
                </template>

                <template #date="{ item }">
                    <span class="text-sm text-gray-500">{{ item.created_at }}</span>
                </template>

                <template #actions="{ item }">
                    <div class="flex justify-center gap-2">
                        <Link
                            :href="route('contact-messages.show', item.id)"
                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded"
                            title="View & Reply"
                        >
                            <MessageSquare class="w-4 h-4" />
                        </Link>
                        <button @click="deleteMessage(item.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded" title="Delete">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </template>

                <template #empty>
                    <MessageSquare class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">No messages found.</p>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
