<script setup lang="ts">
interface Category {
    id: number
    title: string
    parent_id?: number | null
}

const selectedCategoryIds = defineModel<number[]>('selectedCategoryIds')
const categoryFilterSearch = defineModel<string>('categoryFilterSearch')

defineProps<{
    categories: Category[]
    isCategoryFilterOpen: boolean
}>()

defineEmits<{
    (e: 'update:isCategoryFilterOpen', value: boolean): void
    (e: 'toggle-all-categories', event: Event): void
    (e: 'filter-category', categoryId: number): void
    (e: 'clear-category-filter'): void
}>()
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
            @click="$emit('update:isCategoryFilterOpen', !isCategoryFilterOpen)"
        >
            <Filter class="h-4 w-4" /> Filter categories
            <span v-if="selectedCategoryIds.length" class="rounded-full bg-blue-100 px-1.5 py-0.5 text-xs text-blue-700">
                {{ selectedCategoryIds.length }}
            </span>
        </button>
        <div v-if="isCategoryFilterOpen" class="absolute left-0 z-40 mt-2 w-80 rounded-lg border border-gray-200 bg-white p-3 shadow-lg">
            <input
                v-model="categoryFilterSearch"
                @input="$emit('update:categoryFilterSearch', categoryFilterSearch)"
                type="search"
                placeholder="Search categories"
                class="mb-3 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
            />
            <label class="flex cursor-pointer items-center gap-2 border-b border-gray-200 pb-2 text-sm font-semibold">
                <input
                    type="checkbox"
                    :checked="selectedCategoryIds.length === categories.length"
                    @change="$emit('toggle-all-categories', $event)"
                />
                Select all categories
            </label>
            <div class="max-h-64 overflow-y-auto py-2">
                <label
                    v-for="category in categories"
                    :key="category.id"
                    class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-gray-50"
                >
                    <input
                        v-model="selectedCategoryIds"
                        type="checkbox"
                        :value="category.id"
                        @change="$emit('filter-category', category.id)"
                    />
                    <span>{{ category.title }}</span>
                </label>
                <p v-if="categories.length === 0" class="px-2 py-3 text-sm text-gray-500">No categories found.</p>
            </div>
            <div class="flex justify-between border-t border-gray-200 pt-2">
                <button type="button" class="text-sm text-blue-600 hover:underline" @click="$emit('clear-category-filter')">Clear filter</button>
                <button type="button" class="text-sm text-gray-600 hover:underline" @click="$emit('update:isCategoryFilterOpen', false)">Close</button>
            </div>
        </div>
    </div>
</template>