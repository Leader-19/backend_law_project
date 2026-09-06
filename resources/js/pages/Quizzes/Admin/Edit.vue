<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft, Plus, Trash2, Pencil, Save, X } from 'lucide-vue-next'
import { ref } from 'vue'
import { type BreadcrumbItem } from '@/types'

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

interface Category {
    id: number
    title: string
    parent_id: number | null
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quiz Management', href: '/quiz-management' },
    { title: 'Edit Quiz', href: '#' },
]

const props = defineProps<{
    quiz: Quiz
    categories: Category[]
}>()

const form = useForm({
    title: props.quiz.title,
    description: props.quiz.description ?? '',
    category_id: props.quiz.category_id,
    passing_score: props.quiz.passing_score,
    time_limit_minutes: props.quiz.time_limit_minutes ?? '',
    max_attempts: props.quiz.max_attempts,
    is_active: props.quiz.is_active,
})

// Question form
const showQuestionForm = ref(false)
const editingQuestionId = ref<number | null>(null)
const questionForm = useForm({
    question: '',
    type: 'multiple_choice' as string,
    options: [
        { option_text: '', is_correct: false },
        { option_text: '', is_correct: false },
    ] as QuizOption[],
})

function openNewQuestion() {
    editingQuestionId.value = null
    questionForm.reset()
    questionForm.question = ''
    questionForm.type = 'multiple_choice'
    questionForm.options = [
        { option_text: '', is_correct: false },
        { option_text: '', is_correct: false },
    ]
    showQuestionForm.value = true
}

function openEditQuestion(q: QuizQuestion) {
    editingQuestionId.value = q.id
    questionForm.question = q.question
    questionForm.type = q.type
    questionForm.options = q.options.map(o => ({
        id: o.id,
        option_text: o.option_text,
        is_correct: o.is_correct,
    }))
    showQuestionForm.value = true
}

function addOption() {
    questionForm.options.push({ option_text: '', is_correct: false })
}

function removeOption(index: number) {
    if (questionForm.options.length > 2) {
        questionForm.options.splice(index, 1)
    }
}

function toggleCorrectOption(index: number) {
    questionForm.options[index].is_correct = !questionForm.options[index].is_correct
}

function submitQuiz() {
    form.put(route('quizzes-management.update', props.quiz.id))
}

function submitQuestion() {
    if (editingQuestionId.value) {
        questionForm.put(route('quizzes-management.questions.update', [props.quiz.id, editingQuestionId.value]), {
            onSuccess: () => { showQuestionForm.value = false }
        })
    } else {
        questionForm.post(route('quizzes-management.questions.store', props.quiz.id), {
            onSuccess: () => { showQuestionForm.value = false }
        })
    }
}

function deleteQuestion(id: number) {
    if (confirm('Delete this question?')) {
        router.delete(route('quizzes-management.questions.destroy', [props.quiz.id, id]))
    }
}
</script>

<template>
    <Head :title="`Edit: ${quiz.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-8xl mx-auto">
            <Link :href="route('quizzes-management.index')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <ArrowLeft class="w-4 h-4" /> Back to Quizzes
            </Link>

            <!-- Quiz Settings -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <h1 class="text-xl font-bold text-gray-900 mb-4">Quiz Settings</h1>

                <form @submit.prevent="submitQuiz" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                        <input v-model="form.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="form.description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select v-model="form.category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.title }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pass Score (%)</label>
                            <input v-model.number="form.passing_score" type="number" min="0" max="100" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Time Limit (min)</label>
                            <input v-model="form.time_limit_minutes" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="No limit" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max Attempts</label>
                            <input v-model.number="form.max_attempts" type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.is_active" type="checkbox" class="w-4 h-4 text-blue-600 rounded" />
                        <label class="text-sm text-gray-700">Active</label>
                    </div>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                        <Save class="w-4 h-4 inline mr-1" /> Save Changes
                    </button>
                </form>
            </div>

            <!-- Questions -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Questions ({{ quiz.questions.length }})</h2>
                    <button @click="openNewQuestion" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                        <Plus class="w-4 h-4" /> Add Question
                    </button>
                </div>

                <!-- Question Form -->
                <div v-if="showQuestionForm" class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-900">{{ editingQuestionId ? 'Edit' : 'New' }} Question</h3>
                        <button @click="showQuestionForm = false" class="text-gray-400 hover:text-gray-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitQuestion" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Question *</label>
                            <textarea v-model="questionForm.question" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Enter question..." required></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select v-model="questionForm.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Options (click circles to mark correct — multiple allowed)</label>
                            <div v-for="(option, index) in questionForm.options" :key="index" class="flex items-center gap-2 mb-2">
                                <button type="button" @click="toggleCorrectOption(index)" :class="[
                                    'w-6 h-6 rounded-full border-2 flex-shrink-0 transition-colors',
                                    option.is_correct ? 'bg-green-500 border-green-500' : 'border-gray-300 hover:border-gray-400'
                                ]">
                                    <span v-if="option.is_correct" class="text-white text-xs">✓</span>
                                </button>
                                <input
                                    v-model="option.option_text"
                                    type="text"
                                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                    :placeholder="`Option ${index + 1}`"
                                    required
                                />
                                <button type="button" @click="removeOption(index)" v-if="questionForm.options.length > 2" class="text-red-400 hover:text-red-600">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                            <button type="button" @click="addOption" class="text-sm text-blue-600 hover:underline">
                                + Add Option
                            </button>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" :disabled="questionForm.processing" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                                {{ editingQuestionId ? 'Update' : 'Add' }} Question
                            </button>
                            <button type="button" @click="showQuestionForm = false" class="px-4 py-2 text-sm bg-gray-100 rounded hover:bg-gray-200">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Questions List -->
                <div v-if="quiz.questions.length === 0" class="text-center py-8">
                    <p class="text-gray-500">No questions yet. Add your first question above.</p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="(question, index) in quiz.questions"
                        :key="question.id"
                        class="border border-gray-200 rounded-lg p-4"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-3">
                                <span class="text-sm font-bold text-gray-400 mt-1">{{ index + 1 }}.</span>
                                <div>
                                    <p class="font-medium text-gray-900">{{ question.question }}</p>
                                    <p class="text-xs text-gray-400 capitalize mt-1">{{ question.type.replace('_', ' ') }}</p>
                                    <div class="mt-2 space-y-1">
                                        <div
                                            v-for="option in question.options"
                                            :key="option.id"
                                            class="flex items-center gap-2 text-sm"
                                        >
                                            <span :class="option.is_correct ? 'text-green-600 font-bold' : 'text-gray-500'">
                                                {{ option.is_correct ? '✓' : '○' }} {{ option.option_text }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <button @click="openEditQuestion(question)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Edit question">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button @click="deleteQuestion(question.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
