<script setup lang="ts">
defineProps<{
    modelValue: boolean
    label: string
}>()

const emit = defineEmits<{
    'update:modelValue': [value: boolean]
}>()
</script>

<template>
    <label class="flex items-center gap-2 cursor-pointer">
        <input
            type="checkbox"
            :checked="modelValue"
            @change="emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
            class="w-4 h-4 rounded-[5px] border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-slate-900 transition-colors focus:ring-2 focus:ring-slate-900/20 dark:text-white dark:focus:ring-white/20"
        />
        <span class="text-sm text-slate-700 dark:text-slate-300">{{ label }}</span>
    </label>
</template>
