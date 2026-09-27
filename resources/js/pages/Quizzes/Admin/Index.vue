<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { Plus, Pencil, Trash2, ClipboardList, Users } from '@lucide/vue'
import { type BreadcrumbItem } from '@/types'

interface Quiz {
    id: number
    title: string
    is_active: boolean
    passing_score: number
    category: { id: number; title: string }
    creator: { id: number; name: string }
    questions_count: number
    attempts_count: number
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quiz Management', href: '/quiz-management' },
]

const props = defineProps<{
    quizzes: { data: Quiz[] } & Pagination
    search: string
}>()

function deleteQuiz(id: number) {
    if (confirm('Delete this quiz? This action cannot be undone.')) {
        router.delete(route('quizzes-management.destroy', id))
    }
}

function changePage(page: number) {
    router.get(route('quizzes-management.index'), { page, search: props.search }, { preserveState: true })
}
</script>

<template>
    <Head title="Quiz Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <ClipboardList class="w-6 h-6 text-blue-600" />
                    <h1 class="text-2xl font-bold text-gray-900">Quiz Management</h1>
                </div>
                <Link
                    :href="route('quizzes-management.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                >
                    <Plus class="w-4 h-4" /> Create Quiz
                </Link>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Title</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Category</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Questions</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Attempts</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Pass Score</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="quiz in quizzes.data" :key="quiz.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ quiz.title }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ quiz.category.title }}</td>
                            <td class="px-4 py-3 text-center text-sm text-gray-500">{{ quiz.questions_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <Link :href="route('quizzes-management.attempts', quiz.id)" class="text-blue-600 hover:underline text-sm">
                                    {{ quiz.attempts_count }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-500">{{ quiz.passing_score }}%</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="[
                                    'text-xs font-medium px-2 py-1 rounded-full',
                                    quiz.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'
                                ]">
                                    {{ quiz.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-2">
                                    <Link
                                        :href="route('quizzes-management.edit', quiz.id)"
                                        class="p-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </Link>
                                    <button
                                        @click="deleteQuiz(quiz.id)"
                                        class="p-2 bg-red-500 text-white rounded hover:bg-red-600"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="quizzes.data.length === 0" class="text-center py-12">
                    <ClipboardList class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">No quizzes found.</p>
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
