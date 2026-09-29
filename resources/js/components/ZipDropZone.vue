<script setup lang="ts">
import { ref } from 'vue'
import { FileArchive, CheckCircle2 } from '@lucide/vue'

defineProps<{
    modelValue: File | null
    error?: string
}>()

defineEmits<{
    (e: 'update:modelValue', value: File | null): void
}>()

const zipInput = ref<HTMLInputElement | null>(null)
</script>

<template>
    <div>
        <div
            class="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors"
            :class="modelValue ? 'border-green-500 bg-green-50' : 'border-gray-300 hover:border-gray-400'"
            @click="zipInput?.click()"
        >
            <FileArchive class="w-12 h-12 mx-auto text-gray-400 mb-3" />
            <p class="text-sm text-gray-600 mb-1">
                Click to select a ZIP file
            </p>
            <p class="text-xs text-gray-400">
                Upload a ZIP archive containing your documents. Max size: 10 GB.
            </p>

            <input
                ref="zipInput"
                type="file"
                accept=".zip,application/zip,application/x-zip-compressed"
                @change="(e) => {
                    const target = e.target as HTMLInputElement
                    if (target.files && target.files.length > 0) {
                        $emit('update:modelValue', target.files[0])
                    }
                    target.value = ''
                }"
                class="hidden"
            />
        </div>

        <div v-if="modelValue" class="mt-3 border rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <CheckCircle2 class="w-5 h-5 text-green-600" />
                    <span class="font-medium text-sm">{{ modelValue.name }}</span>
                </div>
                <button
                    type="button"
                    @click="$emit('update:modelValue', null)"
                    class="text-sm text-red-600 hover:text-red-700"
                >
                    Remove
                </button>
            </div>
        </div>

        <p v-if="error" class="text-red-500 text-sm mt-1">
            {{ error }}
        </p>
    </div>
</template>
