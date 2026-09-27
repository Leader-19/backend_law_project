<script setup lang="ts">
import { Search } from '@lucide/vue'

const searchQuery = defineModel<string>('searchQuery', { required: true })
const selectedCategoryId = defineModel<number | null>('selectedCategoryId', { required: true })

defineProps<{
    categories: { id: number; title: string }[]
}>()

defineEmits<{
    (e: 'search'): void
}>()
</script>

<template>
    <div class="flex items-center gap-2">
        <select
            v-model="selectedCategoryId"
            class="rounded-lg border border-gray-300 px-3 py-2 text-xs"
            @change="$emit('search')"
        >
            <option :value="null">All Categories</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.title }}
            </option>
        </select>

        <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Search title or body..."
                class="rounded-lg border border-gray-300 pl-9 pr-3 py-2 text-xs w-56"
                @keyup.enter="$emit('search')"
            />
        </div>

        <button
            @click="$emit('search')"
            class="px-3 py-2 text-xs font-semibold bg-gray-800 text-white rounded-lg hover:bg-gray-700"
        >
            Search
        </button>
    </div>
</template>
