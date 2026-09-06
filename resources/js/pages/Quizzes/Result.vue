<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft, CheckCircle, XCircle, Trophy, Award } from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'

interface Answer {
    id: number
    question_id: number
    option_id: number
    is_correct: boolean
    question: { id: number; question: string; type: string }
    option: { id: number; option_text: string }
}

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
        passing_score: number
        category: { id: number; title: string }
    }
    answers: Answer[]
    certificate: { id: number; certificate_number: string } | null
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quizzes', href: '/quizzes' },
    { title: 'Result', href: '#' },
]

const props = defineProps<{
    attempt: Attempt
}>()
</script>

<template>
    <Head title="Quiz Result" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-8xl mx-auto">
            <Link :href="route('quizzes.show', attempt.quiz.id)" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <ArrowLeft class="w-4 h-4" /> Back to Quiz
            </Link>

            <!-- Result Header -->
            <div :class="[
                'border rounded-lg p-6 mb-6 text-center',
                attempt.passed ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'
            ]">
                <component
                    :is="attempt.passed ? CheckCircle : XCircle"
                    :class="['w-16 h-16 mx-auto mb-3', attempt.passed ? 'text-green-500' : 'text-red-500']"
                />
                <h1 class="text-2xl font-bold mb-1" :class="attempt.passed ? 'text-green-800' : 'text-red-800'">
                    {{ attempt.passed ? 'Congratulations! You Passed!' : 'Not Passed' }}
                </h1>
                <p class="text-sm" :class="attempt.passed ? 'text-green-600' : 'text-red-600'">
                    {{ attempt.quiz.title }}
                </p>

                <div class="flex justify-center gap-8 mt-6">
                    <div>
                        <Trophy class="w-6 h-6 text-yellow-500 mx-auto mb-1" />
                        <p class="text-2xl font-bold text-gray-900">{{ attempt.score }}%</p>
                        <p class="text-xs text-gray-500">Score</p>
                    </div>
                    <div>
                        <CheckCircle class="w-6 h-6 text-green-500 mx-auto mb-1" />
                        <p class="text-2xl font-bold text-gray-900">{{ attempt.correct_answers }}/{{ attempt.total_questions }}</p>
                        <p class="text-xs text-gray-500">Correct</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Pass: {{ attempt.quiz.passing_score }}%</p>
                    </div>
                </div>

                <!-- Certificate -->
                <div v-if="attempt.certificate" class="mt-6 p-4 bg-white rounded-lg border border-green-200">
                    <Award class="w-8 h-8 text-yellow-500 mx-auto mb-2" />
                    <p class="font-semibold text-gray-900">Certificate Earned!</p>
                    <p class="text-sm text-gray-500">Number: {{ attempt.certificate.certificate_number }}</p>
                    <Link
                        :href="route('certificates.show', attempt.certificate.id)"
                        class="mt-2 inline-block text-sm text-blue-600 hover:underline"
                    >
                        View Certificate →
                    </Link>
                </div>
            </div>

            <!-- Answers Review -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Review Answers</h2>

                <div class="space-y-4">
                    <div
                        v-for="(answer, index) in attempt.answers"
                        :key="answer.id"
                        :class="[
                            'p-4 rounded-lg border',
                            answer.is_correct ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'
                        ]"
                    >
                        <div class="flex items-start gap-2 mb-2">
                            <component
                                :is="answer.is_correct ? CheckCircle : XCircle"
                                :class="['w-5 h-5 mt-0.5 flex-shrink-0', answer.is_correct ? 'text-green-500' : 'text-red-500']"
                            />
                            <div>
                                <p class="font-medium text-gray-900">Q{{ index + 1 }}. {{ answer.question.question }}</p>
                                <p class="text-sm mt-1" :class="answer.is_correct ? 'text-green-700' : 'text-red-700'">
                                    Your answer: {{ answer.option.option_text }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 text-center">
                <Link
                    :href="route('quizzes.take', attempt.quiz.id)"
                    class="inline-block px-6 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700"
                >
                    Retake Quiz
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
