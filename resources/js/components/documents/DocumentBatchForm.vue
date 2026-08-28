<script setup lang="ts">
import CategoryPicker from '@/components/CategoryPicker.vue'
import BatchTabSwitcher from '@/components/BatchTabSwitcher.vue'
import FileDropZone from '@/components/FileDropZone.vue'
import ZipDropZone from '@/components/ZipDropZone.vue'
import BatchErrorAlert from '@/components/BatchErrorAlert.vue'

interface Category {
    id: number
    title: string
    children?: Category[]
}

const activeTab = defineModel<'files' | 'zip'>('activeTab', { required: true })
const selectedFiles = defineModel<File[]>('selectedFiles', { required: true })
const errors = defineModel<string[]>('errors', { required: true })
const fileErrors = defineModel<Record<number, string>>('fileErrors', { required: true })
const isDragging = defineModel<boolean>('isDragging', { required: true })
const form = defineModel<any>('form', { required: true })
const zipForm = defineModel<any>('zipForm', { required: true })

defineProps<{
    categories: Category[]
}>()
</script>

<template>
    <BatchTabSwitcher v-model:activeTab="activeTab" />

    <div class="space-y-6">
        <div>
            <label class="block font-medium mb-1">
                Category <span class="text-red-500">*</span>
            </label>
            <CategoryPicker v-model="form.category_id" :categories="categories" input-id="batch-document-category" />
            <p class="mt-1 text-xs text-gray-500">Search by category name; subcategories are shown with their full path.</p>
            <p v-if="activeTab === 'files' && form.errors.category_id" class="text-red-500 text-sm mt-1">
                {{ form.errors.category_id }}
            </p>
            <p v-if="activeTab === 'zip' && zipForm.errors.category_id" class="text-red-500 text-sm mt-1">
                {{ zipForm.errors.category_id }}
            </p>
        </div>

        <div>
            <label class="block font-medium mb-1">Description</label>
            <textarea
                :value="activeTab === 'files' ? form.description : zipForm.description"
                @input="(e) => {
                    const target = e.target as HTMLTextAreaElement
                    if (activeTab === 'files') form.description = target.value
                    else zipForm.description = target.value
                }"
                rows="3"
                class="w-full border rounded px-3 py-2"
                placeholder="Optional description applied to all imported documents..."
            ></textarea>
            <p class="mt-1 text-xs text-gray-500">This description will be applied to every document in this batch.</p>
            <p v-if="activeTab === 'files' && form.errors.description" class="text-red-500 text-sm mt-1">
                {{ form.errors.description }}
            </p>
            <p v-if="activeTab === 'zip' && zipForm.errors.description" class="text-red-500 text-sm mt-1">
                {{ zipForm.errors.description }}
            </p>
        </div>

        <div v-if="activeTab === 'files'">
            <FileDropZone
                v-model="selectedFiles"
                v-model:errors="errors"
                v-model:fileErrors="fileErrors"
                v-model:isDragging="isDragging"
            >
                <template #file-list>
                    <BatchErrorAlert :errors="errors" />
                </template>
            </FileDropZone>
        </div>

        <div v-if="activeTab === 'zip'">
            <ZipDropZone
                v-model="zipForm.zip_file"
                :error="zipForm.errors.zip_file"
            />
        </div>
    </div>
</template>