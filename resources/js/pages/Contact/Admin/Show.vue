<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft, Send, CheckCircle, Clock, XCircle } from '@lucide/vue'
import { type BreadcrumbItem } from '@/types'

interface ContactMessage {
    id: number
    subject: string
    message: string
    status: string
    admin_reply: string | null
    replied_at: string | null
    created_at: string
    user: { id: number; name: string; email: string }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contact Messages', href: '/contact-messages' },
    { title: 'Message', href: '#' },
]

const props = defineProps<{
    message: ContactMessage
}>()

const replyForm = useForm({
    admin_reply: props.message.admin_reply ?? '',
})

function submitReply() {
    replyForm.post(route('contact-messages.reply', props.message.id))
}

function closeMessage() {
    if (confirm('Close this message?')) {
        useForm({}).post(route('contact-messages.close', props.message.id))
    }
}

function reopenMessage() {
    useForm({}).post(route('contact-messages.reopen', props.message.id))
}

function getStatusColor(status: string): string {
    return ({ open: 'text-yellow-600 bg-yellow-100', replied: 'text-green-600 bg-green-100', closed: 'text-gray-600 bg-gray-100' } as Record<string, string>)[status] || ''
}
</script>

<template>
    <Head :title="`Message: ${message.subject}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-8xl mx-auto">
            <Link :href="route('contact-messages.index')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <ArrowLeft class="w-4 h-4" /> Back to Messages
            </Link>

            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ message.subject }}</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            From: <span class="font-medium">{{ message.user.name }}</span> ({{ message.user.email }})
                        </p>
                        <p class="text-xs text-gray-400 mt-1">{{ message.created_at }}</p>
                    </div>
                    <span :class="['text-xs font-medium px-2 py-1 rounded-full', getStatusColor(message.status)]">
                        {{ message.status }}
                    </span>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-700 whitespace-pre-wrap">{{ message.message }}</p>
                </div>

                <!-- Action Buttons -->
                <div class="mt-4 flex gap-2">
                    <button
                        v-if="message.status !== 'closed'"
                        @click="closeMessage"
                        class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
                    >
                        Close Message
                    </button>
                    <button
                        v-if="message.status === 'closed'"
                        @click="reopenMessage"
                        class="px-3 py-1.5 text-sm bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200"
                    >
                        Reopen
                    </button>
                </div>
            </div>

            <!-- Reply Section -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ message.admin_reply ? 'Edit Reply' : 'Send Reply' }}
                </h2>

                <form @submit.prevent="submitReply" class="space-y-4">
                    <div>
                        <textarea
                            v-model="replyForm.admin_reply"
                            rows="5"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Type your reply..."
                            required
                        ></textarea>
                    </div>
                    <button
                        type="submit"
                        :disabled="replyForm.processing"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                    >
                        <Send class="w-4 h-4" />
                        {{ replyForm.processing ? 'Sending...' : 'Send Reply' }}
                    </button>
                </form>

                <!-- Previous Reply -->
                <div v-if="message.admin_reply && message.replied_at" class="mt-6 pt-4 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                        <CheckCircle class="w-4 h-4 text-green-500" />
                        Previous Reply
                    </h3>
                    <p class="text-xs text-gray-400 mb-2">{{ message.replied_at }}</p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ message.admin_reply }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
