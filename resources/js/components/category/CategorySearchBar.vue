<script setup lang="ts">
import { Search } from '@lucide/vue'

const searchQuery = defineModel<string>('searchQuery', { default: '' })

defineEmits<{
    (e: 'search'): void
}>()
</script>

<template>
    <div class="relative flex items-center">
        <input
            v-model="searchQuery"
            @keyup.enter="$emit('search')"
            type="text"
            placeholder="Search..."
            class="w-64 pl-9 pr-3 py-1.5 rounded-l border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <button
            @click="$emit('search')"
            class="px-3 py-1.5 rounded-r border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-600"
        >
            Search
        </button>
    </div>
</template>