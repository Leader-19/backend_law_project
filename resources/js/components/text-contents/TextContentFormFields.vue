<script setup lang="ts">
import CategoryPicker from '@/components/CategoryPicker.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { type Form } from '@inertiajs/vue3'

interface Category {
    id: number
    title: string
    parent_id: number | null
}

defineProps<{
    form: Form<any>
    categories: Category[]
    processing: boolean
    submitLabel: string
    isEdit?: boolean
}>()
</script>

<template>
    <div class="space-y-4">
        <!-- Title -->
        <div>
            <label class="block text-sm font-medium">ចំណងជើង (Title)</label>
            <input
                type="text"
                v-model="form.title"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                placeholder="Enter title"
            />
            <p v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</p>
        </div>

        <!-- Category -->
        <div>
            <label class="block text-sm font-medium">ប្រភេទ (Category)</label>
            <CategoryPicker v-model="form.category_id" :categories="categories" input-id="text-content-category" />
            <p v-if="form.errors.category_id" class="text-red-500 text-sm mt-1">{{ form.errors.category_id }}</p>
        </div>

        <!-- Body - Rich Text Editor -->
        <div>
            <label class="block text-sm font-medium">អត្ថបទ (Body Text)</label>
            <div class="mt-1">
                <RichTextEditor v-model="form.body" placeholder="Enter the text content here..." />
            </div>
            <p class="text-xs text-gray-400 mt-1">Use the toolbar to format text with headers, bold, italic, colors, etc.</p>
            <p v-if="form.errors.body" class="text-red-500 text-sm mt-1">{{ form.errors.body }}</p>
        </div>
    </div>
</template>
