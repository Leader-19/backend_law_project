<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft, Plus, Trash2, X } from 'lucide-vue-next'
import { ref } from 'vue'
import { type BreadcrumbItem } from '@/types'

interface Category {
    id: number
    title: string
    parent_id: number | null
}

interface QuizOption {
    option_text: string
    is_correct: boolean
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quiz Management', href: '/quiz-management' },
    { title: 'Create Quiz', href: '#' },
]

const props = defineProps<{
    categories: Category[]
}>()

const form = useForm({
    title: '',
    description: '',
    category_id: '',
    passing_score: 70,
    time_limit_minutes: '',
    max_attempts: 0,
    is_active: true,
})

// Question form
const showQuestionForm = ref(false)
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

const savedQuizId = ref<number | null>(null)
const quizSaved = ref(false)

function openNewQuestion() {
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

function addOption() {
    questionForm.value.options.push({ option_text: '', is_correct: false })
}

function removeOption(index: number) {
    if (questionForm.value.options.length > 2) {
        questionForm.value.options.splice(index, 1)
    }
}

function toggleCorrectOption(index: number) {
    questionForm.value.options[index].is_correct = !questionForm.value.options[index].is_correct
}

function submit() {
    form.post(route('quizzes-management.store'), {
        onSuccess: (page) => {
            // After quiz is created, we need the quiz ID to add questions
            // The redirect goes to index, so we store the ID from flash
            const flash = (page.props as any).flash
            if (flash?.quiz_id) {
                savedQuizId.value = flash.quiz_id
                quizSaved.value = true
            }
        },
    })
}

function submitQuestion() {
    if (!savedQuizId.value) return

    useForm({
        question: questionForm.value.question,
        type: questionForm.value.type,
        options: questionForm.value.options,
    }).post(route('quizzes-management.questions.store', savedQuizId.value), {
        onSuccess: () => {
            showQuestionForm.value = false
            // Reload to get updated questions
            window.location.reload()
        },
    })
}
</script>

<template>
    <Head title="Create Quiz" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-3xl mx-auto">
            <Link :href="route('quizzes-management.index')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <ArrowLeft class="w-4 h-4" /> Back to Quizzes
            </Link>

            <!-- Quiz Settings -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <h1 class="text-xl font-bold text-gray-900 mb-4">Create New Quiz</h1>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Quiz title..."
                        />
                        <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Quiz description..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select
                            v-model="form.category_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Select category</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.title }}</option>
                        </select>
                        <p v-if="form.errors.category_id" class="text-red-500 text-xs mt-1">{{ form.errors.category_id }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Passing Score (%) *</label>
                            <input
                                v-model.number="form.passing_score"
                                type="number"
                                min="0"
                                max="100"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Time Limit (minutes)</label>
                            <input
                                v-model="form.time_limit_minutes"
                                type="number"
                                min="1"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="No limit"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max Attempts (0 = unlimited)</label>
                            <input
                                v-model.number="form.max_attempts"
                                type="number"
                                min="0"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            />
                            <label class="text-sm text-gray-700">Active</label>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full px-4 py-2 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
                    >
                        {{ form.processing ? 'Creating...' : 'Create Quiz' }}
                    </button>
                </form>
            </div>

            <!-- Questions Section (shown after quiz is saved) -->
            <div v-if="quizSaved" class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Add Questions</h2>
                    <button @click="openNewQuestion" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                        <Plus class="w-4 h-4" /> Add Question
                    </button>
                </div>

                <!-- Question Form -->
                <div v-if="showQuestionForm" class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-900">New Question</h3>
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
                            <button type="submit" :disabled="!questionForm.question.trim() || questionForm.options.some(o => !o.option_text.trim())" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                                Add Question
                            </button>
                            <button type="button" @click="showQuestionForm = false" class="px-4 py-2 text-sm bg-gray-100 rounded hover:bg-gray-200">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <div v-if="!showQuestionForm" class="text-center py-6 text-gray-400 text-sm">
                    Click "Add Question" to start adding questions to this quiz.
                </div>
            </div>

            <!-- Pre-save hint -->
            <div v-if="!quizSaved" class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-700">
                💡 Save the quiz settings first, then you can add questions and answers.
            </div>
        </div>
    </AppLayout>
</template>
