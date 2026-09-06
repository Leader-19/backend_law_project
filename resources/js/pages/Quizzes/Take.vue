<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft, Clock, CheckCircle } from 'lucide-vue-next'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { type BreadcrumbItem } from '@/types'

interface QuizOption {
    id: number
    option_text: string
}

interface QuizQuestion {
    id: number
    question: string
    type: string
    sort_order: number
    options: QuizOption[]
}

interface Quiz {
    id: number
    title: string
    time_limit_minutes: number | null
    questions: QuizQuestion[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quizzes', href: '/quizzes' },
    { title: 'Take Quiz', href: '#' },
]

const props = defineProps<{
    quiz: Quiz
}>()

const answers = ref<Record<number, number[]>>({})
const currentQuestion = ref(0)
const timeRemaining = ref<number | null>(null)
const timerInterval = ref<number | null>(null)
const isSubmitting = ref(false)

const totalQuestions = computed(() => props.quiz.questions.length)
const question = computed(() => props.quiz.questions[currentQuestion.value])
const isLastQuestion = computed(() => currentQuestion.value === totalQuestions.value - 1)
const allAnswered = computed(() => props.quiz.questions.every(q => answers.value[q.id]?.length > 0))

function selectOption(questionId: number, optionId: number) {
    if (!answers.value[questionId]) {
        answers.value[questionId] = []
    }
    const idx = answers.value[questionId].indexOf(optionId)
    if (idx > -1) {
        answers.value[questionId].splice(idx, 1)
    } else {
        answers.value[questionId].push(optionId)
    }
}

function isSelected(questionId: number, optionId: number): boolean {
    return answers.value[questionId]?.includes(optionId) ?? false
}

function nextQuestion() {
    if (currentQuestion.value < totalQuestions.value - 1) {
        currentQuestion.value++
    }
}

function prevQuestion() {
    if (currentQuestion.value > 0) {
        currentQuestion.value--
    }
}

function goToQuestion(index: number) {
    currentQuestion.value = index
}

function formatTime(seconds: number): string {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

function submitQuiz() {
    const answeredCount = Object.keys(answers.value).length
    const unansweredCount = totalQuestions.value - answeredCount

    if (unansweredCount > 0) {
        if (!confirm(`You have ${unansweredCount} unanswered question(s). Submit anyway?`)) return
    }

    isSubmitting.value = true

    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }

    const answersArray = Object.entries(answers.value).flatMap(([questionId, optionIds]) =>
        optionIds.map(optionId => ({
            question_id: parseInt(questionId),
            option_id: optionId,
        }))
    )

    router.post(route('quizzes.submit', props.quiz.id), {
        answers: answersArray,
    })
}

function handleBack() {
    if (Object.keys(answers.value).length > 0) {
        if (!confirm('Are you sure you want to leave? Your progress will be lost.')) return
    }
    window.location.href = route('quizzes.show', props.quiz.id)
}

onMounted(() => {
    if (props.quiz.time_limit_minutes) {
        timeRemaining.value = props.quiz.time_limit_minutes * 60
        timerInterval.value = window.setInterval(() => {
            if (timeRemaining.value !== null && timeRemaining.value > 0) {
                timeRemaining.value--
                if (timeRemaining.value <= 0) {
                    submitQuiz()
                }
            }
        }, 1000)
    }
})

onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }
})
</script>

<template>
    <Head :title="`Take Quiz: ${quiz.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-8xl mx-auto">
            <!-- Header -->
            <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4 flex items-center justify-between">
                <button @click="handleBack" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                    <ArrowLeft class="w-4 h-4" /> Exit Quiz
                </button>
                <h1 class="text-lg font-bold text-gray-900">{{ quiz.title }}</h1>
                <div v-if="timeRemaining !== null" class="flex items-center gap-2 text-lg font-mono" :class="timeRemaining < 60 ? 'text-red-600' : 'text-gray-900'">
                    <Clock class="w-5 h-5" />
                    {{ formatTime(timeRemaining) }}
                    <span v-if="timeRemaining !== null && timeRemaining <= 60" class="text-xs text-red-500 font-normal">⚠ Low time!</span>
                </div>
            </div>

            <!-- Progress -->
            <div class="mb-4">
                <div class="flex items-center justify-between text-sm text-gray-500 mb-1">
                    <span>Question {{ currentQuestion + 1 }} of {{ totalQuestions }}</span>
                    <span>{{ Math.round(((currentQuestion + 1) / totalQuestions) * 100) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div
                        class="bg-blue-600 h-2 rounded-full transition-all"
                        :style="{ width: `${((currentQuestion + 1) / totalQuestions) * 100}%` }"
                    ></div>
                </div>
            </div>

            <!-- Question Navigation Dots -->
            <div class="flex flex-wrap gap-2 mb-6">
                <button
                    v-for="(q, index) in quiz.questions"
                    :key="q.id"
                    @click="goToQuestion(index)"
                    :class="[
                        'w-8 h-8 rounded-full text-xs font-medium transition',
                        index === currentQuestion
                            ? 'bg-blue-600 text-white'                        :answers[q.id]?.length > 0
                                ? 'bg-green-100 text-green-700 border border-green-300'
                                : 'bg-gray-100 text-gray-500 border border-gray-300 hover:bg-gray-200'
                    ]"
                >
                    {{ index + 1 }}
                </button>
            </div>

            <!-- Question -->
            <div v-if="question" class="bg-white border border-gray-200 rounded-lg p-6 mb-4">
                <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ question.question }}</h2>
                <p class="text-xs text-gray-400 mb-1 capitalize">{{ question.type.replace('_', ' ') }}</p>
                <p class="text-xs text-blue-500 mb-4">Select all that apply</p>

                <div class="space-y-3">
                    <button
                        v-for="option in question.options"
                        :key="option.id"
                        @click="selectOption(question.id, option.id)"
                        :class="[
                            'w-full text-left p-4 rounded-lg border-2 transition',
                            isSelected(question.id, option.id)
                                ? 'border-blue-600 bg-blue-50 text-blue-900'
                                : 'border-gray-200 hover:border-gray-300 text-gray-700'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0',
                                    isSelected(question.id, option.id)
                                        ? 'border-blue-600 bg-blue-600'
                                        : 'border-gray-300'
                                ]"
                            >
                                <CheckCircle
                                    v-if="isSelected(question.id, option.id)"
                                    class="w-3 h-3 text-white"
                                />
                            </div>
                            <span>{{ option.option_text }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-between">
                <button
                    @click="prevQuestion"
                    :disabled="currentQuestion === 0"
                    class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    ← Previous
                </button>

                <div v-if="isLastQuestion">
                    <button
                        @click="submitQuiz"
                        :disabled="isSubmitting"
                        class="px-6 py-2 text-sm bg-green-600 text-white font-semibold rounded hover:bg-green-700 disabled:opacity-50"
                    >
                        {{ isSubmitting ? 'Submitting...' : 'Submit Quiz' }}
                    </button>
                </div>
                <button
                    v-else
                    @click="nextQuestion"
                    class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    Next →
                </button>
            </div>
        </div>
    </AppLayout>
</template>
