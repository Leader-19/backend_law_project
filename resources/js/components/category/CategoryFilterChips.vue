<script setup lang="ts">
interface Category {
    id: number
    title: string
    documents_count: number
    children?: {
        id: number
        title: string
        documents_count: number
    }[]
}

defineProps<{
    categories: Category[]
    selectedCategory: number | null
}>()

defineEmits<{
    (e: 'filter', categoryId: number | null): void
}>()
</script>

<template>
    <div class="flex flex-wrap gap-2 mt-4 mb-3">
        <button
            @click="$emit('filter', null)"
            :class="[
                'px-4 py-1 text-xs font-medium rounded transition',
                selectedCategory === null
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-200 text-gray-700 hover:bg-blue-500 hover:text-white'
            ]"
        >
            All
        </button>

        <template v-for="cat in categories" :key="cat.id">
            <button
                @click="$emit('filter', cat.id)"
                :class="[
                    'px-4 py-1 text-xs font-medium rounded transition',
                    selectedCategory === cat.id
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-200 text-gray-700 hover:bg-blue-500 hover:text-white'
                ]"
            >
                {{ cat.title }} ({{ cat.documents_count }})
            </button>

            <button
                v-for="sub in cat.children"
                :key="sub.id"
                @click="$emit('filter', sub.id)"
                :class="[
                    'px-4 py-1 ml-4 text-xs font-medium rounded transition',
                    selectedCategory === sub.id
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-200 text-gray-700 hover:bg-blue-500 hover:text-white'
                ]"
            >
                — {{ sub.title }} ({{ sub.documents_count }})
            </button>
        </template>
    </div>
</template>