<script setup lang="ts">
import { useLoadingStore } from '@/pages/stores/LoadingPage';

const loading = useLoadingStore()
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="loading.isLoading"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
        >
            <div class="flex flex-col items-center gap-4 bg-white dark:bg-slate-900 px-8 py-6 rounded-[5px] shadow-2xl border border-slate-200 dark:border-slate-700">
                <div class="relative w-10 h-10">
                    <div class="absolute inset-0 border-4 border-slate-200 dark:border-slate-700 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-blue-600 rounded-full animate-spin border-t-transparent"></div>
                </div>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Loading...</p>
            </div>
        </div>
    </Transition>
</template>