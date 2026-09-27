<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft } from '@lucide/vue'
import { ref, onMounted, computed } from 'vue'
import { type BreadcrumbItem } from '@/types'
import QuizSettingsForm from './components/QuizSettingsForm.vue'
import QuestionForm from './components/QuestionForm.vue'
import QuestionsList from './components/QuestionsList.vue'

interface Category {
    id: number
    title: string
    parent_id: number | null
}

interface QuizOption {
    id?: number
    option_text: string
    is_correct: boolean
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
    description: string | null
    passing_score: number
    time_limit_minutes: number | null
    max_attempts: number
    is_active: boolean
    category_id: number
    questions: QuizQuestion[]
}

function getCsrfToken(): string {
    return decodeURIComponent(
        (document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
    )
}

async function apiPost(url: string, data: any) {
    const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify(data),
    })
    const text = await res.text()
    let payload
    try { payload = JSON.parse(text) } catch { payload = text }
    if (!res.ok) throw new Error(typeof payload === 'object' ? (payload.message || JSON.stringify(payload)) : String(payload))
    return typeof payload === 'object' ? payload : JSON.parse(payload)
}

async function apiPut(url: string, data: any) {
    const res = await fetch(url, {
        method: 'PUT',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify(data),
    })
    const text = await res.text()
    let payload
    try { payload = JSON.parse(text) } catch { payload = text }
    if (!res.ok) throw new Error(typeof payload === 'object' ? (payload.message || JSON.stringify(payload)) : String(payload))
    return typeof payload === 'object' ? payload : JSON.parse(payload)
}

async function apiDelete(url: string) {
    const res = await fetch(url, {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
    })
    const text = await res.text()
    let payload
    try { payload = JSON.parse(text) } catch { payload = text }
    if (!res.ok) throw new Error(typeof payload === 'object' ? (payload.message || JSON.stringify(payload)) : String(payload))
    return typeof payload === 'object' ? payload : JSON.parse(payload)
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quiz Management', href: '/quiz-management' },
    { title: 'Create Quiz', href: '#' },
]

const props = defineProps<{
    categories: Category[]
}>()

const draftQuiz = ref<Quiz | null>(null)
const draftLoading = ref(true)
const draftError = ref('')

const questions = ref<QuizQuestion[]>([])
const showQuestionForm = ref(false)
const editingQuestionId = ref<number | null>(null)
const questionForm = ref<{
    question: string
    type: string
    options: QuizOption[]
}>({
    question: '',
    type: 'multiple_choice',
    options: [
        { option_text: '', is_correct: false },
        { option_text: '', is_correct: false },
    ],
})

const settingsForm = computed({
    get: () => draftQuiz.value ? {
        title: draftQuiz.value.title,
        description: draftQuiz.value.description || '',
        category_id: String(draftQuiz.value.category_id),
        passing_score: draftQuiz.value.passing_score,
        time_limit_minutes: draftQuiz.value.time_limit_minutes ?? '',
        max_attempts: draftQuiz.value.max_attempts,
        is_active: draftQuiz.value.is_active,
    } : {
        title: '',
        description: '',
        category_id: '',
        passing_score: 70,
        time_limit_minutes: '',
        max_attempts: 0,
        is_active: true,
    },
    set: (val) => {
        if (!draftQuiz.value) return
        draftQuiz.value.title = val.title
        draftQuiz.value.description = val.description
        draftQuiz.value.category_id = Number(val.category_id)
        draftQuiz.value.passing_score = val.passing_score
        draftQuiz.value.time_limit_minutes = val.time_limit_minutes ? Number(val.time_limit_minutes) : null
        draftQuiz.value.max_attempts = val.max_attempts
        draftQuiz.value.is_active = val.is_active
    },
})

const savingSettings = ref(false)
const submittingQuestion = ref(false)

async function createDraftQuiz() {
    draftLoading.value = true
    draftError.value = ''
    try {
        const data = await apiPost(route('quizzes-management.store'), {
            title: 'Untitled Quiz',
            description: '',
            category_id: props.categories[0]?.id || '',
            passing_score: 70,
            time_limit_minutes: null,
            max_attempts: 0,
            is_active: false,
        })

        draftQuiz.value = data.quiz
    } catch (err) {
        draftError.value = typeof err === 'string' ? err : (err.message || 'Could not start a new quiz. Please try again.')
        console.error(err)
    } finally {
        draftLoading.value = false
    }
}

async function updateSettings() {
    if (!draftQuiz.value) return
    savingSettings.value = true
    try {
        const data = await apiPut(route('quizzes-management.update', draftQuiz.value.id), {
            title: settingsForm.value.title,
            description: settingsForm.value.description,
            category_id: Number(settingsForm.value.category_id),
            passing_score: settingsForm.value.passing_score,
            time_limit_minutes: settingsForm.value.time_limit_minutes ? Number(settingsForm.value.time_limit_minutes) : null,
            max_attempts: settingsForm.value.max_attempts,
            is_active: settingsForm.value.is_active,
        })

        if (draftQuiz.value) {
            draftQuiz.value.title = data.quiz?.title ?? draftQuiz.value.title
            draftQuiz.value.description = data.quiz?.description ?? draftQuiz.value.description
            draftQuiz.value.category_id = data.quiz?.category_id ?? draftQuiz.value.category_id
            draftQuiz.value.passing_score = data.quiz?.passing_score ?? draftQuiz.value.passing_score
            draftQuiz.value.time_limit_minutes = data.quiz?.time_limit_minutes ?? draftQuiz.value.time_limit_minutes
            draftQuiz.value.max_attempts = data.quiz?.max_attempts ?? draftQuiz.value.max_attempts
            draftQuiz.value.is_active = data.quiz?.is_active ?? draftQuiz.value.is_active
        }
    } catch (err) {
        console.error(err)
    } finally {
        savingSettings.value = false
    }
}

function openNewQuestion() {
    editingQuestionId.value = null
    questionForm.value = {
        question: '',
        type: 'multiple_choice',
        options: [
            { option_text: '', is_correct: false },
            { option_text: '', is_correct: false },
        ],
    }
    showQuestionForm.value = true
}

function openEditQuestion(q: QuizQuestion) {
    editingQuestionId.value = q.id
    questionForm.value = {
        question: q.question,
        type: q.type,
        options: q.options.map(o => ({
            id: o.id,
            option_text: o.option_text,
            is_correct: o.is_correct,
        })),
    }
    showQuestionForm.value = true
}

async function submitQuestion() {
    if (!draftQuiz.value) return
    if (!questionForm.value.question.trim() || questionForm.value.options.some(o => !o.option_text.trim())) return

    submittingQuestion.value = true
    try {
        const url = editingQuestionId.value
            ? route('quizzes-management.questions.update', [draftQuiz.value.id, editingQuestionId.value])
            : route('quizzes-management.questions.store', draftQuiz.value.id)

        const data = editingQuestionId.value
            ? await apiPut(url, {
                question: questionForm.value.question,
                type: questionForm.value.type,
                options: questionForm.value.options,
            })
            : await apiPost(url, {
                question: questionForm.value.question,
                type: questionForm.value.type,
                options: questionForm.value.options,
            })

        if (editingQuestionId.value) {
            const idx = questions.value.findIndex(q => q.id === editingQuestionId.value)
            if (idx !== -1) {
                questions.value[idx] = data.question
            }
        } else {
            questions.value.push(data.question)
        }

        showQuestionForm.value = false
        editingQuestionId.value = null
        questionForm.value = {
            question: '',
            type: 'multiple_choice',
            options: [
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
            ],
        }
    } catch (err) {
        console.error(err)
        alert('Failed to save question. Please try again.')
    } finally {
        submittingQuestion.value = false
    }
}

async function deleteQuestion(questionId: number) {
    if (!confirm('Delete this question?')) return
    if (!draftQuiz.value) return

    try {
        await apiDelete(route('quizzes-management.questions.destroy', [draftQuiz.value.id, questionId]))
        questions.value = questions.value.filter(q => q.id !== questionId)
    } catch (err) {
        console.error(err)
        alert('Failed to delete question.')
    }
}

function finishQuiz() {
    if (!draftQuiz.value) return
    window.location.href = route('quizzes-management.index')
}

onMounted(async () => {
    await createDraftQuiz()
})
</script>

<template>
    <Head title="Create Quiz" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-8xl mx-auto">
            <Link :href="route('quizzes-management.index')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <ArrowLeft class="w-4 h-4" /> Back to Quizzes
            </Link>

            <!-- Draft Loading / Error -->
            <div v-if="draftLoading" class="flex items-center justify-center py-20">
                <div class="w-8 h-8 border-4 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>
            </div>
            <div v-else-if="draftError" class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 mb-6">
                {{ draftError }}
                <button @click="createDraftQuiz" class="ml-2 underline">Retry</button>
            </div>

            <template v-else-if="draftQuiz">
                <!-- Quiz Settings -->
                <QuizSettingsForm
                    :form="settingsForm"
                    :categories="categories"
                    :saving="savingSettings"
                    @submit="updateSettings"
                />

                <!-- Questions Section -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Questions ({{ questions.length }})
                        </h2>
                        <button @click="openNewQuestion" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Question
                        </button>
                    </div>

                    <QuestionForm
                        v-if="showQuestionForm"
                        v-model:questionForm="questionForm"
                        :editing="editingQuestionId !== null"
                        :submitting="submittingQuestion"
                        @submit="submitQuestion"
                        @cancel="showQuestionForm = false"
                    />

                    <QuestionsList
                        :questions="questions"
                        :show-form="showQuestionForm"
                        @edit="openEditQuestion"
                        @delete="deleteQuestion"
                    />
                </div>

                <!-- Finish -->
                <div class="mt-6 flex justify-end">
                    <button
                        @click="finishQuiz"
                        class="px-6 py-2.5 text-sm font-semibold bg-brand-600 text-white rounded-xl hover:bg-brand-700 transition-colors"
                    >
                        Finish & View Quizzes
                    </button>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
