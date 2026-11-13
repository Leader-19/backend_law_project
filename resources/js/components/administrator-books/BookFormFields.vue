<script setup lang="ts">
import type { Form } from '@inertiajs/vue3'

interface Category {
    id: number
    name: string
}

interface Plan {
    id: number
    name: string
    price: string
    currency: string
}

defineProps<{
    form: Form<Record<string, unknown>>
    categories: Category[]
    plans: Plan[]
    processing: boolean
    isEdit?: boolean
    submitLabel?: string
}>()

const emit = defineEmits<{ submit: []; 'file-change': [File | null] }>()

const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement
    emit('file-change', input.files?.[0] ?? null)
}
</script>

<template>
    <div class="space-y-5">
        <!-- Title -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="title">
                Title <span class="text-red-500">*</span>
            </label>
            <input
                id="title"
                v-model="form.title"
                type="text"
                required
                placeholder="Book title"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
        </div>

        <!-- Administrator Name -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="name">
                Administrator Name <span class="text-red-500">*</span>
            </label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                required
                placeholder="Administrator name"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
        </div>

        <!-- Category -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="category">
                Category <span class="text-red-500">*</span>
            </label>
            <select
                id="category"
                v-model="form.administrator_book_category_id"
                required
                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
            >
                <option value="" disabled>— Select a category —</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
            <p v-if="form.errors.administrator_book_category_id" class="mt-1 text-xs text-red-600">
                {{ form.errors.administrator_book_category_id }}
            </p>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="description">
                Description
            </label>
            <textarea
                id="description"
                v-model="form.description"
                rows="4"
                placeholder="Short description of the book..."
                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
            ></textarea>
            <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
        </div>

        <!-- File -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="file">
                Upload File <span v-if="!isEdit" class="text-gray-400">(PDF, Word, ...)</span>
            </label>
            <input
                id="file"
                type="file"
                :required="!isEdit"
                class="w-full text-sm file:mr-3 file:px-3 file:py-1.5 file:text-sm file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                @change="handleFileChange"
            />
            <p v-if="form.errors.file" class="mt-1 text-xs text-red-600">{{ form.errors.file }}</p>
        </div>

        <!-- Subscription Plans -->
        <div>
            <span class="block text-sm font-medium text-gray-700 mb-1">
                Subscription plans allowed to read this book
            </span>
            <span class="block text-xs text-gray-400 mb-2">
                Select at least one plan to restrict access. If none is selected, every subscribed user can read it (Admins always can).
            </span>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label
                    v-for="plan in plans"
                    :key="plan.id"
                    class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50"
                >
                    <input
                        v-model="form.plan_ids"
                        type="checkbox"
                        :value="plan.id"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                    <span>{{ plan.name }}</span>
                    <span class="ml-auto text-xs text-gray-500">
                        {{ plan.price }} {{ plan.currency }}
                    </span>
                </label>
            </div>
            <p v-if="form.errors.plan_ids" class="mt-1 text-xs text-red-600">{{ form.errors.plan_ids }}</p>
        </div>
    </div>
</template>
