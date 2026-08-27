<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft, MessageSquare, CheckCircle, Clock, XCircle } from 'lucide-vue-next'
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contact Admin', href: '/contact' },
    { title: 'Message', href: '#' },
]

const props = defineProps<{
    message: ContactMessage
}>()

function getStatusIcon(status: string) {
    return { open: Clock, replied: CheckCircle, closed: XCircle }[status] || Clock
}

function getStatusColor(status: string) {
    return { open: 'text-yellow-600 bg-yellow-100', replied: 'text-green-600 bg-green-100', closed: 'text-gray-600 bg-gray-100' }[status] || ''
}
</script>

<template>
    <Head :title="message.subject" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-3xl mx-auto">
            <Link :href="route('contact.index')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <ArrowLeft class="w-4 h-4" /> Back to Messages
            </Link>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-start justify-between mb-4">
                    <h1 class="text-xl font-bold text-gray-900">{{ message.subject }}</h1>
                    <span :class="['text-xs font-medium px-2 py-1 rounded-full', getStatusColor(message.status)]">
                        <component :is="getStatusIcon(message.status)" class="w-3 h-3 inline" />
                        {{ message.status }}
                    </span>
                </div>

                <p class="text-xs text-gray-400 mb-4">{{ message.created_at }}</p>

                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <p class="text-gray-700 whitespace-pre-wrap">{{ message.message }}</p>
                </div>

                <!-- Admin Reply -->
                <div v-if="message.admin_reply" class="mt-6">
                    <h2 class="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                        <MessageSquare class="w-4 h-4 text-blue-600" />
                        Admin Reply
                    </h2>
                    <p v-if="message.replied_at" class="text-xs text-gray-400 mb-2">{{ message.replied_at }}</p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ message.admin_reply }}</p>
                    </div>
                </div>

                <div v-else class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                    <p class="text-yellow-800 text-sm">Waiting for admin reply...</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
