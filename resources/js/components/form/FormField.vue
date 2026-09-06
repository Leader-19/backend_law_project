<script setup lang="ts">
defineProps<{
    label?: string
    error?: string
    required?: boolean
}>()
</script>

<template>
    <div>
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}<span v-if="required" class="text-red-500">*</span>
        </label>
        <slot />
        <p v-if="error" class="text-red-500 text-xs mt-1">{{ error }}</p>
    </div>
</template>
