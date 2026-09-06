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
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
        />
        <span class="text-sm text-gray-700">{{ label }}</span>
    </label>
</template>
