<script setup lang="ts">
import { computed, ref, watch } from 'vue'

interface Category {
    id: number
    title: string
    parent_id?: number | null
    children?: Category[]
}

const props = defineProps<{
    categories: Category[]
    inputId: string
}>()

const model = defineModel<number | string | null>({ required: true })
const search = ref('')
const isOpen = ref(false)

function collectCategories(categories: Category[]): Category[] {
    return categories.flatMap((category) => [category, ...collectCategories(category.children ?? [])])
}

const options = computed(() => {
    const categories = collectCategories(props.categories)
    const categoriesById = new Map(categories.map((category) => [category.id, category]))

    function labelFor(category: Category, visited: number[] = []): string {
        if (category.parent_id === null || category.parent_id === undefined || visited.includes(category.id)) {
            return category.title
        }

        const parent = categoriesById.get(category.parent_id)

        return parent ? `${labelFor(parent, [...visited, category.id])} — ${category.title}` : category.title
    }

    return categories.map((category) => ({ id: category.id, label: labelFor(category) }))
})
const filteredOptions = computed(() => {
    const query = search.value.trim().toLocaleLowerCase()

    if (!query) {
        return options.value
    }

    return options.value.filter((option) => option.label.toLocaleLowerCase().includes(query))
})

function syncSearch() {
    search.value = options.value.find((option) => String(option.id) === String(model.value))?.label ?? ''
}

watch([options, model], syncSearch, { immediate: true })

function selectCategory(option: { id: number; label: string }) {
    model.value = option.id
    search.value = option.label
    isOpen.value = false
}

function handleInput() {
    // A typed value is not a valid category until the user selects a result.
    model.value = null
    isOpen.value = true
}

function clearSelection() {
    if (search.value === '') {
        model.value = null
    }
}
</script>

<template>
    <div class="relative mt-1">
        <input
            :id="inputId"
            v-model="search"
            type="search"
            autocomplete="off"
            placeholder="Search and select a category"
            class="block w-full rounded-md border border-gray-300 px-3 py-2"
            role="combobox"
            :aria-expanded="isOpen"
            :aria-controls="`${inputId}-options`"
            @focus="isOpen = true"
            @input="handleInput"
            @keyup.esc="isOpen = false"
            @blur="clearSelection"
        />

        <div
            v-if="isOpen"
            :id="`${inputId}-options`"
            role="listbox"
            class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg"
        >
            <button
                v-for="option in filteredOptions"
                :key="option.id"
                type="button"
                role="option"
                class="block w-full px-3 py-2 text-left text-sm hover:bg-blue-50"
                @mousedown.prevent="selectCategory(option)"
            >
                {{ option.label }}
            </button>
            <p v-if="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">
                No categories found.
            </p>
        </div>
    </div>
</template>
