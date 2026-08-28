<script setup lang="ts">
import CategoryPicker from '@/components/CategoryPicker.vue'
import { ref } from 'vue'
import { type Form } from '@inertiajs/vue3'
import { CHUNK_UPLOAD_THRESHOLD } from '@/composables/useChunkUpload'

interface Category {
    id: number
    title: string
    children?: Category[]
}

defineProps<{
    form: Form<any>
    categories: Category[]
    processing: boolean
    submitLabel: string
    isEdit?: boolean
}>()

const MAX_IMAGE_BYTES = 5 * 1024 * 1024
const MAX_DOC_BYTES = 2 * 1024 * 1024 * 1024 // 2 GB hard limit

const docError = ref<string | null>(null)
const imageError = ref<string | null>(null)
const imagePreview = ref<string | null>(null)
const isLargeFile = ref(false)

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
    if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
    return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB'
}

function handleFileUpload(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files && target.files.length > 0) {
        const file = target.files[0]
        if (file.size > MAX_DOC_BYTES) {
            docError.value = `File is too large (${formatFileSize(file.size)}). Maximum allowed is 2 GB.`
            form.doc_upload = null
            target.value = ''
            isLargeFile.value = false
            return
        }
        docError.value = null
        form.doc_upload = file
        isLargeFile.value = file.size > CHUNK_UPLOAD_THRESHOLD
    }
}

function handleImageUpload(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files && target.files.length > 0) {
        const file = target.files[0]
        if (file.size > MAX_IMAGE_BYTES) {
            imageError.value = `Image is too large (${(file.size / 1024 / 1024).toFixed(1)} MB). Maximum allowed is 5 MB.`
            form.image = null
            imagePreview.value = null
            target.value = ''
            return
        }
        imageError.value = null
        form.image = file
        imagePreview.value = URL.createObjectURL(file)
    }
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium">Document Name</label>
            <input
                type="text"
                v-model="form.doc_name"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                placeholder="Enter document name"
            />
            <p v-if="form.errors.doc_name" class="text-red-500 text-sm mt-1">{{ form.errors.doc_name }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium">Title</label>
            <input
                type="text"
                v-model="form.doc_title"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                placeholder="Enter title"
            />
            <p v-if="form.errors.doc_title" class="text-red-500 text-sm mt-1">{{ form.errors.doc_title }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium">Category</label>
            <CategoryPicker v-model="form.category_id" :categories="categories" input-id="document-form-category" />
            <p v-if="form.errors.category_id" class="text-red-500 text-sm mt-1">{{ form.errors.category_id }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium">Upload File *</label>
            <input
                type="file"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                required
                @change="handleFileUpload"
                class="mt-1 block w-full text-sm border border-gray-300 rounded-md p-2"
            />
            <p v-if="docError" class="text-red-500 text-sm mt-1">{{ docError }}</p>
            <p v-else-if="isLargeFile" class="text-blue-600 dark:text-blue-400 text-sm mt-1">
                ⚡ Large file detected — upload uses chunked transfer for reliability.
            </p>
            <p v-else-if="form.errors.doc_upload" class="text-red-500 text-sm mt-1">{{ form.errors.doc_upload }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium">Image</label>
            <input
                type="file"
                accept="image/*"
                @change="handleImageUpload"
                class="mt-1 block w-full text-sm border border-gray-300 rounded-md p-2"
            />
            <img v-if="imagePreview" :src="imagePreview" class="mt-4 w-40 rounded border" />
            <p v-if="imageError" class="text-red-500 text-sm mt-1">{{ imageError }}</p>
            <p v-else-if="form.errors.image" class="text-red-500 text-sm mt-1">{{ form.errors.image }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium">Description</label>
            <textarea
                v-model="form.description"
                rows="3"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                placeholder="Optional description"
            ></textarea>
            <p v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</p>
        </div>

        <button
            type="submit"
            :disabled="processing"
            class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded disabled:opacity-50"
        >
            {{ processing ? 'Saving...' : submitLabel }}
        </button>
    </div>
</template>