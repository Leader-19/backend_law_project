<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft, CheckCircle, XCircle } from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'

interface Quiz {
    id: number
    title: string
    category: { id: number; title: string }
}

interface Attempt {
    id: number
    score: number
    total_questions: number
    correct_answers: number
    passed: boolean
    completed_at: string | null
    user: { id: number; name: string; email: string }
}

interface Stats {
    total_attempts: number
    average_score: number
    pass_rate: number
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quiz Management', href: '/quiz-management' },
    { title: 'Attempts', href: '#' },
]

const props = defineProps<{
    quiz: Quiz
    attempts: { data: Attempt[] } & Pagination
    stats: Stats
}>()

function changePage(page: number) {
    router.get(route('quizzes-management.attempts', props.quiz.id), { page }, { preserveState: true })
}
</script>

<template>
    <Head :title="`Attempts: ${quiz.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-8xl mx-auto">
            <Link :href="route('quizzes-management.index')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <ArrowLeft class="w-4 h-4" /> Back to Quizzes
            </Link>

            <h1 class="text-xl font-bold text-gray-900 mb-1">{{ quiz.title }}</h1>
            <p class="text-sm text-gray-500 mb-6">{{ quiz.category.title }}</p>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ stats.total_attempts }}</p>
                    <p class="text-sm text-gray-500">Total Attempts</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ stats.average_score }}%</p>
                    <p class="text-sm text-gray-500">Average Score</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ stats.pass_rate }}%</p>
                    <p class="text-sm text-gray-500">Pass Rate</p>
                </div>
            </div>

            <!-- Attempts Table -->
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">User</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Score</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Correct</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Result</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="attempt in attempts.data" :key="attempt.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ attempt.user.name }}</p>
                                <p class="text-xs text-gray-500">{{ attempt.user.email }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold" :class="attempt.passed ? 'text-green-600' : 'text-red-600'">
                                    {{ attempt.score }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-500">
                                {{ attempt.correct_answers }}/{{ attempt.total_questions }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <CheckCircle v-if="attempt.passed" class="w-5 h-5 text-green-500 mx-auto" />
                                <XCircle v-else class="w-5 h-5 text-red-500 mx-auto" />
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ attempt.completed_at }}</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="attempts.data.length === 0" class="text-center py-12">
                    <p class="text-gray-500">No attempts yet.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="attempts.last_page > 1" class="mt-6 flex justify-center gap-2">
                <button
                    v-for="page in attempts.last_page"
                    :key="page"
                    @click="changePage(page)"
                    :class="[
                        'px-3 py-1 rounded text-sm',
                        page === attempts.current_page
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
