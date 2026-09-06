<script setup lang="ts">
import { Trash2, X } from 'lucide-vue-next'
import { computed } from 'vue'
import { type QuizOption } from '@/types'

interface Props {
    questionForm: {
        question: string
        type: string
        options: QuizOption[]
    }
    editing: boolean
    submitting: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
    'update:questionForm': [value: Props['questionForm']]
    submit: []
    cancel: []
}>()

const questionForm = computed({
    get: () => props.questionForm,
    set: (val) => emit('update:questionForm', val),
})

function updateOption(index: number, field: keyof QuizOption, value: any) {
    const updated = [...questionForm.value.options]
    updated[index] = { ...updated[index], [field]: value }
    questionForm.value = { ...questionForm.value, options: updated }
}

function addOption() {
    questionForm.value = {
        ...questionForm.value,
        options: [...questionForm.value.options, { option_text: '', is_correct: false }],
    }
}

function removeOption(index: number) {
    if (questionForm.value.options.length <= 2) return
    const updated = questionForm.value.options.filter((_, i) => i !== index)
    questionForm.value = { ...questionForm.value, options: updated }
}
</script>

<template>
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-900">
                {{ editing ? 'Edit' : 'New' }} Question
            </h3>
            <button @click="$emit('cancel')" class="text-gray-400 hover:text-gray-600">
                <X class="w-5 h-5" />
            </button>
        </div>

        <form @submit.prevent="$emit('submit')" class="space-y-3">
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Options (click circles to mark correct)</label>
                <div v-for="(option, index) in questionForm.options" :key="index" class="flex items-center gap-2 mb-2">
                    <button type="button" @click="updateOption(index, 'is_correct', !option.is_correct)" :class="[
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
                <button type="submit" :disabled="submitting" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                    {{ submitting ? 'Saving...' : (editing ? 'Update' : 'Add') + ' Question' }}
                </button>
                <button type="button" @click="$emit('cancel')" class="px-4 py-2 text-sm bg-gray-100 rounded hover:bg-gray-200">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</template>
