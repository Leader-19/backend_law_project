<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft, CheckCircle, XCircle, Trophy } from '@lucide/vue'
import { type BreadcrumbItem } from '@/types'

interface Quiz {
    id: number
    title: string
    description: string | null
    passing_score: number
    time_limit_minutes: number | null
    max_attempts: number
    is_active: boolean
    category: { id: number; title: string }
    questions: Array<{
        id: number
        question: string
        type: string
        sort_order: number
    }>
}

interface Attempt {
    id: number
    score: number
    total_questions: number
    correct_answers: number
    passed: boolean
    started_at: string | null
    completed_at: string | null
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quizzes', href: '/quizzes' },
    { title: 'Quiz Details', href: '#' },
]

defineProps<{
    quiz: Quiz
    attempts: Attempt[]
    canAttempt: boolean
}>()
</script>

<template>
    <Head :title="quiz.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-8xl mx-auto">
            <Link :href="route('quizzes.index')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <ArrowLeft class="w-4 h-4" /> Back to Quizzes
            </Link>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ quiz.title }}</h1>
                        <p class="text-sm text-blue-600 mt-1">{{ quiz.category.title }}</p>
                    </div>
                    <span
                        v-if="quiz.is_active"
                        class="text-xs font-medium text-green-700 bg-green-100 px-2 py-1 rounded-full"
                    >
                        Active
                    </span>
                    <span v-else class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                        Inactive
                    </span>
                </div>

                <p v-if="quiz.description" class="text-gray-600 mb-6">{{ quiz.description }}</p>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ quiz.questions.length }}</p>
                        <p class="text-xs text-gray-500">Questions</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ quiz.passing_score }}%</p>
                        <p class="text-xs text-gray-500">Pass Score</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-gray-900">
                            {{ quiz.time_limit_minutes ? `${quiz.time_limit_minutes}m` : '∞' }}
                        </p>
                        <p class="text-xs text-gray-500">Time Limit</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-gray-900">
                            {{ quiz.max_attempts > 0 ? quiz.max_attempts : '∞' }}
                        </p>
                        <p class="text-xs text-gray-500">Max Attempts</p>
                    </div>
                </div>

                <div v-if="canAttempt" class="mb-6">
                    <Link
                        :href="route('quizzes.take', quiz.id)"
                        class="block w-full text-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
                    >
                        Start Quiz
                    </Link>
                </div>
                <div v-else class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                    <p class="text-yellow-800">You have reached the maximum number of attempts for this quiz.</p>
                </div>

                <!-- Previous Attempts -->
                <div v-if="attempts.length > 0">
                    <h2 class="text-lg font-semibold text-gray-900 mb-3">Your Attempts</h2>
                    <div class="space-y-2">
                        <Link
                            v-for="attempt in attempts"
                            :key="attempt.id"
                            :href="route('quizzes.result', attempt.id)"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
                        >
                            <div class="flex items-center gap-3">
                                <CheckCircle v-if="attempt.passed" class="w-5 h-5 text-green-500" />
                                <XCircle v-else class="w-5 h-5 text-red-500" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ attempt.correct_answers }}/{{ attempt.total_questions }} correct
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ attempt.completed_at }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Trophy class="w-4 h-4 text-yellow-500" />
                                <span class="font-semibold text-gray-900">{{ attempt.score }}%</span>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
