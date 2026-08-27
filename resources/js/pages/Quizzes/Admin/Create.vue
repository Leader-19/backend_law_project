<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft } from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'

interface Category {
    id: number
    title: string
    parent_id: number | null
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

function submit() {
    form.post(route('quizzes-management.store'))
}
</script>

<template>
    <Head title="Create Quiz" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-2xl mx-auto">
            <Link :href="route('quizzes-management.index')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <ArrowLeft class="w-4 h-4" /> Back to Quizzes
            </Link>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h1 class="text-xl font-bold text-gray-900 mb-6">Create New Quiz</h1>

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
        </div>
    </AppLayout>
</template>
