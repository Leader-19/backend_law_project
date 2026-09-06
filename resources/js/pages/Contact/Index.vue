<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { MessageSquare, Send, CheckCircle, Clock, XCircle } from 'lucide-vue-next'
import { ref } from 'vue'
import { type BreadcrumbItem } from '@/types'

interface ContactMessage {
    id: number
    subject: string
    message: string
    status: string
    admin_reply: string | null
    replied_at: string | null
    created_at: string
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contact Admin', href: '/contact' },
]

const props = defineProps<{
    messages: { data: ContactMessage[] } & Pagination
}>()

const showForm = ref(false)

const form = useForm({
    subject: '',
    message: '',
})

function submitMessage() {
    form.post(route('contact.store'), {
        onSuccess: () => {
            form.reset()
            showForm.value = false
        }
    })
}

function getStatusIcon(status: string): typeof Clock {
    return ({ open: Clock, replied: CheckCircle, closed: XCircle } as Record<string, typeof Clock>)[status] || Clock
}

function getStatusColor(status: string) {
    return { open: 'text-yellow-600 bg-yellow-100', replied: 'text-green-600 bg-green-100', closed: 'text-gray-600 bg-gray-100' }[status] || ''
}

function changePage(page: number) {
    router.get(route('contact.index'), { page }, { preserveState: true })
}
</script>

<template>
    <Head title="Contact Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-8xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <MessageSquare class="w-6 h-6 text-blue-600" />
                    <h1 class="text-2xl font-bold text-gray-900">Contact Admin</h1>
                </div>
                <button
                    @click="showForm = !showForm"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                >
                    <Send class="w-4 h-4" /> New Message
                </button>
            </div>

            <!-- New Message Form -->
            <div v-if="showForm" class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Send a Message</h2>
                <form @submit.prevent="submitMessage" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <input
                            v-model="form.subject"
                            type="text"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter subject..."
                        />
                        <p v-if="form.errors.subject" class="text-red-500 text-xs mt-1">{{ form.errors.subject }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea
                            v-model="form.message"
                            rows="4"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Write your message..."
                        ></textarea>
                        <p v-if="form.errors.message" class="text-red-500 text-xs mt-1">{{ form.errors.message }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Sending...' : 'Send Message' }}
                        </button>
                        <button
                            type="button"
                            @click="showForm = false"
                            class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Messages List -->
            <div v-if="messages.data.length === 0" class="text-center py-12">
                <MessageSquare class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500 text-lg">No messages yet.</p>
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="msg in messages.data"
                    :key="msg.id"
                    :href="route('contact.show', msg.id)"
                    class="block bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900">{{ msg.subject }}</h3>
                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ msg.message }}</p>
                            <p class="text-xs text-gray-400 mt-2">{{ msg.created_at }}</p>
                        </div>
                        <span :class="['text-xs font-medium px-2 py-1 rounded-full flex-shrink-0 ml-3', getStatusColor(msg.status)]">
                            <component :is="getStatusIcon(msg.status)" class="w-3 h-3 inline" />
                            {{ msg.status }}
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="messages.last_page > 1" class="mt-6 flex justify-center gap-2">
                <button
                    v-for="page in messages.last_page"
                    :key="page"
                    @click="changePage(page)"
                    :class="[
                        'px-3 py-1 rounded text-sm',
                        page === messages.current_page
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
