<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { Award, ExternalLink } from '@lucide/vue'
import { type BreadcrumbItem } from '@/types'

interface Certificate {
    id: number
    certificate_number: string
    score: number
    created_at: string
    quiz: {
        id: number
        title: string
        category: { id: number; title: string }
    }
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Certificates', href: '/certificates' },
]

defineProps<{
    certificates: { data: Certificate[] } & Pagination
}>()

function changePage(page: number) {
    router.get(route('certificates.index'), { page }, { preserveState: true })
}
</script>

<template>
    <Head title="My Certificates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">
            <div class="flex items-center gap-3 mb-6">
                <Award class="w-6 h-6 text-yellow-500" />
                <h1 class="text-2xl font-bold text-gray-900">My Certificates</h1>
                <span class="text-sm text-gray-500">({{ certificates.total }})</span>
            </div>

            <div v-if="certificates.data.length === 0" class="text-center py-12">
                <Award class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500 text-lg">No certificates yet.</p>
                <Link :href="route('quizzes.index')" class="mt-4 inline-block text-blue-600 hover:underline">
                    Take a quiz to earn certificates
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    v-for="cert in certificates.data"
                    :key="cert.id"
                    :href="route('certificates.show', cert.id)"
                    class="bg-white border border-yellow-200 rounded-lg p-5 hover:shadow-md transition"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                            <Award class="w-6 h-6 text-yellow-500" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ cert.quiz.title }}</p>
                            <p class="text-xs text-gray-500">{{ cert.quiz.category.title }}</p>
                        </div>
                    </div>

                    <div class="space-y-1 text-sm">
                        <p class="text-gray-500">
                            <span class="font-medium">Number:</span>
                            <span class="font-mono text-xs">{{ cert.certificate_number }}</span>
                        </p>
                        <p class="text-gray-500">
                            <span class="font-medium">Score:</span>
                            <span class="text-green-600 font-bold">{{ cert.score }}%</span>
                        </p>
                        <p class="text-gray-500">
                            <span class="font-medium">Date:</span>
                            {{ cert.created_at }}
                        </p>
                    </div>

                    <div class="mt-3 flex items-center gap-1 text-sm text-blue-600">
                        <ExternalLink class="w-4 h-4" /> View Certificate
                    </div>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="certificates.last_page > 1" class="mt-6 flex justify-center gap-2">
                <button
                    v-for="page in certificates.last_page"
                    :key="page"
                    @click="changePage(page)"
                    :class="[
                        'px-3 py-1 rounded text-sm',
                        page === certificates.current_page
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
