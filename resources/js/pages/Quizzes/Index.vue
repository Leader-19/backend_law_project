<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ClipboardList, CheckCircle, XCircle, Clock, Trophy } from '@lucide/vue'
import { type BreadcrumbItem } from '@/types'

interface Quiz {
    id: number
    title: string
    description: string | null
    passing_score: number
    time_limit_minutes: number | null
    category: { id: number; title: string }
    questions_count: number
    attempts_count: number
    user_attempts: number
    user_best_score: number | null
    user_passed: boolean
    can_attempt: boolean
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quizzes', href: '/quizzes' },
]

defineProps<{
    quizzes: { data: Quiz[] } & Pagination
}>()

function changePage(page: number) {
    router.get(route('quizzes.index'), { page }, { preserveState: true })
}
</script>

<template>
    <Head title="Quizzes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">
            <div class="flex items-center gap-3 mb-6">
                <ClipboardList class="w-6 h-6 text-blue-600" />
                <h1 class="text-2xl font-bold text-gray-900">Quizzes</h1>
            </div>

            <div class="flex gap-3 mb-6">
                <Link :href="route('quizzes.my-attempts')" class="text-sm text-blue-600 hover:underline">
                    View My Attempts →
                </Link>
            </div>

            <div v-if="quizzes.data.length === 0" class="text-center py-12">
                <ClipboardList class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500 text-lg">No quizzes available.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="quiz in quizzes.data"
                    :key="quiz.id"
                    class="bg-white border border-gray-200 rounded-lg p-5 hover:shadow-md transition"
                >
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="font-semibold text-gray-900 text-lg">{{ quiz.title }}</h3>
                        <span
                            v-if="quiz.user_passed"
                            class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-100 px-2 py-1 rounded-full"
                        >
                            <CheckCircle class="w-3 h-3" /> Passed
                        </span>
                        <span
                            v-else-if="quiz.user_attempts > 0"
                            class="inline-flex items-center gap-1 text-xs font-medium text-red-700 bg-red-100 px-2 py-1 rounded-full"
                        >
                            <XCircle class="w-3 h-3" /> Failed
                        </span>
                    </div>

                    <p v-if="quiz.description" class="text-sm text-gray-600 mb-3 line-clamp-2">{{ quiz.description }}</p>

                    <div class="space-y-2 text-sm text-gray-500 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">Category:</span>
                            <span class="text-blue-600">{{ quiz.category.title }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium">Questions:</span>
                            <span>{{ quiz.questions_count }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium">Pass Score:</span>
                            <span>{{ quiz.passing_score }}%</span>
                        </div>
                        <div v-if="quiz.time_limit_minutes" class="flex items-center gap-2">
                            <Clock class="w-4 h-4" />
                            <span>{{ quiz.time_limit_minutes }} minutes</span>
                        </div>
                        <div v-if="quiz.user_attempts > 0" class="flex items-center gap-2">
                            <Trophy class="w-4 h-4 text-yellow-500" />
                            <span>Best: {{ quiz.user_best_score }}% ({{ quiz.user_attempts }} attempts)</span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <Link
                            :href="route('quizzes.show', quiz.id)"
                            class="flex-1 text-center px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition"
                        >
                            View Details
                        </Link>
                        <Link
                            v-if="quiz.can_attempt"
                            :href="route('quizzes.take', quiz.id)"
                            class="flex-1 text-center px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                        >
                            Take Quiz
                        </Link>
                        <span
                            v-else
                            class="flex-1 text-center px-3 py-2 text-sm bg-gray-300 text-gray-500 rounded cursor-not-allowed"
                        >
                            Max Attempts
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="quizzes.last_page > 1" class="mt-6 flex justify-center gap-2">
                <button
                    v-for="page in quizzes.last_page"
                    :key="page"
                    @click="changePage(page)"
                    :class="[
                        'px-3 py-1 rounded text-sm',
                        page === quizzes.current_page
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
