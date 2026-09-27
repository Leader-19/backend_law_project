<script setup lang="ts">
import { Save, CheckCircle2 } from '@lucide/vue'
import { useForm } from '@inertiajs/vue3'
import FormField from '@/components/form/FormField.vue'
import FormCheckbox from '@/components/form/FormCheckbox.vue'

interface Category {
    id: number
    title: string
    parent_id: number | null
}

const props = defineProps<{
    form: ReturnType<typeof useForm>
    categories: Category[]
    saving: boolean
}>()

defineEmits<{
    submit: []
}>()

const inputClass = 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
</script>

<template>
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h1 class="text-xl font-bold text-gray-900 mb-4">Quiz Settings</h1>

        <form @submit.prevent="$emit('submit')" class="space-y-5">
            <FormField label="Title" :error="form.errors.title" required>
                <input
                    v-model="form.title"
                    type="text"
                    :class="inputClass"
                    placeholder="Quiz title..."
                />
            </FormField>

            <FormField label="Description">
                <textarea
                    v-model="form.description"
                    rows="2"
                    :class="inputClass"
                    placeholder="Quiz description..."
                ></textarea>
            </FormField>

            <FormField label="Category" :error="form.errors.category_id" required>
                <select
                    v-model="form.category_id"
                    :class="inputClass"
                >
                    <option value="">Select category</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.title }}</option>
                </select>
            </FormField>

            <div class="grid grid-cols-2 gap-4">
                <FormField label="Passing Score (%)" required>
                    <input
                        v-model.number="form.passing_score"
                        type="number"
                        min="0"
                        max="100"
                        :class="inputClass"
                    />
                </FormField>

                <FormField label="Time Limit (minutes)">
                    <input
                        v-model.number="form.time_limit_minutes"
                        type="number"
                        min="1"
                        :class="inputClass"
                        placeholder="No limit"
                    />
                </FormField>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <FormField label="Max Attempts (0 = unlimited)">
                    <input
                        v-model.number="form.max_attempts"
                        type="number"
                        min="0"
                        :class="inputClass"
                    />
                </FormField>

                <div class="flex items-center pt-6">
                    <FormCheckbox v-model="form.is_active" label="Active" />
                </div>
            </div>

            <button
                type="submit"
                :disabled="saving"
                class="px-4 py-2 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition flex items-center gap-2"
            >
                <Save v-if="saving" class="w-4 h-4 animate-spin" />
                <CheckCircle2 v-else class="w-4 h-4" />
                {{ saving ? 'Saving...' : 'Save Settings' }}
            </button>
        </form>
    </div>
</template>
