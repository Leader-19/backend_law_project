<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft, CheckCircle, XCircle, Trophy } from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'

interface Attempt {
    id: number
    score: number
    total_questions: number
    correct_answers: number
    passed: boolean
    started_at: string | null
    completed_at: string | null
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
    { title: 'Quizzes', href: '/quizzes' },
    { title: 'My Attempts', href: '#' },
]

const props = defineProps<{
    attempts: { data: Attempt[] } & Pagination
}>()

function changePage(page: number) {
    router.get(route('quizzes.my-attempts'), { page }, { preserveState: true })
}
</script>

<template>
    <Head title="My Quiz Attempts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-8xl mx-auto">
            <Link :href="route('quizzes.index')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <ArrowLeft class="w-4 h-4" /> Back to Quizzes
            </Link>

            <h1 class="text-2xl font-bold text-gray-900 mb-6">My Quiz Attempts</h1>

            <div v-if="attempts.data.length === 0" class="text-center py-12">
                <p class="text-gray-500 text-lg">No quiz attempts yet.</p>
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="attempt in attempts.data"
                    :key="attempt.id"
                    :href="route('quizzes.result', attempt.id)"
                    class="block bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <CheckCircle v-if="attempt.passed" class="w-6 h-6 text-green-500" />
                            <XCircle v-else class="w-6 h-6 text-red-500" />
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ attempt.quiz.title }}</h3>
                                <p class="text-sm text-gray-500">{{ attempt.quiz.category.title }}</p>
                                <p class="text-xs text-gray-400">{{ attempt.completed_at }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-500">{{ attempt.correct_answers }}/{{ attempt.total_questions }}</span>
                            <div class="flex items-center gap-1">
                                <Trophy class="w-4 h-4 text-yellow-500" />
                                <span class="font-bold text-lg" :class="attempt.passed ? 'text-green-600' : 'text-red-600'">
                                    {{ attempt.score }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </Link>
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
