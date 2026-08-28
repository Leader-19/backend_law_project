<script setup lang="ts">
import { Search, Filter } from 'lucide-vue-next'

const searchQuery = defineModel<string>('searchQuery', { required: true })
const searchType = defineModel<string>('searchType', { required: true })
const categoryFilterSearch = defineModel<string>('categoryFilterSearch', { required: true })
const selectedCategoryIds = defineModel<number[]>('selectedCategoryIds', { required: true })

defineProps<{
    isCategoryFilterOpen: boolean
    categoryOptions: { id: number; label: string }[]
    filteredCategoryOptions: { id: number; label: string }[]
    allCategoriesSelected: boolean
}>()

defineEmits<{
    (e: 'update:isCategoryFilterOpen', value: boolean): void
    (e: 'search'): void
    (e: 'toggle-category-filter'): void
    (e: 'toggle-all-categories', event: Event): void
    (e: 'clear-category-filter'): void
    (e: 'filter-category', categoryId: number): void
}>()
</script>

<template>
    <div class="relative flex items-center">
        <select
            v-model="searchType"
            @change="$emit('search')"
            class="h-full rounded-l border border-r-0 border-gray-300 bg-gray-50 px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
            <option value="all">All</option>
            <option value="name">Name</option>
            <option value="title">Title</option>
            <option value="description">Description</option>
        </select>
        <input
            v-model="searchQuery"
            @keyup.enter="$emit('search')"
            type="text"
            placeholder="Search..."
            class="w-64 pl-9 pr-3 py-1.5 border-l-0 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <Search class="absolute left-9 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <button
            @click="$emit('search')"
            class="px-3 py-1.5 rounded-r border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-600"
        >
            Search
        </button>

        <div class="relative ml-2">
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                @click="$emit('toggle-category-filter')"
            >
                <Filter class="h-4 w-4" /> Filter categories
                <span v-if="selectedCategoryIds.length" class="rounded-full bg-blue-100 px-1.5 py-0.5 text-xs text-blue-700">
                    {{ selectedCategoryIds.length }}
                </span>
            </button>
            <div v-if="isCategoryFilterOpen" class="absolute left-0 z-40 mt-2 w-80 rounded-lg border border-gray-200 bg-white p-3 shadow-lg">
                <input
                    v-model="categoryFilterSearch"
                    type="search"
                    placeholder="Search categories"
                    class="mb-3 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                />
                <label class="flex cursor-pointer items-center gap-2 border-b border-gray-200 pb-2 text-sm font-semibold">
                    <input type="checkbox" :checked="allCategoriesSelected" @change="$emit('toggle-all-categories', $event)" />
                    Select all categories
                </label>
                <div class="max-h-64 overflow-y-auto py-2">
                    <label
                        v-for="category in filteredCategoryOptions"
                        :key="category.id"
                        class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-gray-50"
                    >
                        <input
                            v-model="selectedCategoryIds"
                            type="checkbox"
                            :value="category.id"
                            @change="$emit('filter-category', category.id)"
                        />
                        <span>{{ category.label }}</span>
                    </label>
                    <p v-if="filteredCategoryOptions.length === 0" class="px-2 py-3 text-sm text-gray-500">No categories found.</p>
                </div>
                <div class="flex justify-between border-t border-gray-200 pt-2">
                    <button type="button" class="text-sm text-blue-600 hover:underline" @click="$emit('clear-category-filter')">Clear filter</button>
                    <button type="button" class="text-sm text-gray-600 hover:underline" @click="$emit('update:isCategoryFilterOpen', false)">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>