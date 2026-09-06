<script setup lang="ts">
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'
import { type QuizQuestion } from '@/types'

defineProps<{
    questions: QuizQuestion[]
    showForm: boolean
}>()

defineEmits<{
    'update:showForm': [value: boolean]
    edit: [question: QuizQuestion]
    delete: [id: number]
    add: []
}>()
</script>

<template>
    <div v-if="questions.length === 0 && !showForm" class="text-center py-8">
        <p class="text-gray-500">No questions yet. Click "Add Question" to start building your quiz.</p>
    </div>

    <div v-else class="space-y-3">
        <div
            v-for="(question, index) in questions"
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
                    <button @click="$emit('edit', question)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Edit question">
                        <Pencil class="w-4 h-4" />
                    </button>
                    <button @click="$emit('delete', question.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded">
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
