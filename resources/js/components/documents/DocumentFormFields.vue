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
    categories: Category[]
    processing: boolean
    submitLabel: string
    isEdit?: boolean
}>()

const form = defineModel<Form<any>>('form', { required: true })

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
            form.value.doc_upload = null
            target.value = ''
            isLargeFile.value = false
            return
        }
        docError.value = null
        form.value.doc_upload = file
        isLargeFile.value = file.size > CHUNK_UPLOAD_THRESHOLD
    }
}

function handleImageUpload(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files && target.files.length > 0) {
        const file = target.files[0]
        if (file.size > MAX_IMAGE_BYTES) {
            imageError.value = `Image is too large (${(file.size / 1024 / 1024).toFixed(1)} MB). Maximum allowed is 5 MB.`
            form.value.image = null
            imagePreview.value = null
            target.value = ''
            return
        }
        imageError.value = null
        form.value.image = file
        imagePreview.value = URL.createObjectURL(file)
    }
}
</script>

<template>
    <div class="space-y-5">
        <!-- Document Name -->
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                Document Name <span class="text-red-500">*</span>
            </label>
            <input
                type="text"
                v-model="form.doc_name"
                class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                placeholder="e.g. Law on Education"
            />
            <p v-if="form.errors.doc_name" class="text-red-500 text-xs mt-1.5">{{ form.errors.doc_name }}</p>
        </div>

        <!-- Title -->
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                Title <span class="text-red-500">*</span>
            </label>
            <input
                type="text"
                v-model="form.doc_title"
                class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                placeholder="e.g. Education Law No. 138"
            />
            <p v-if="form.errors.doc_title" class="text-red-500 text-xs mt-1.5">{{ form.errors.doc_title }}</p>
        </div>

        <!-- Category -->
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                Category <span class="text-red-500">*</span>
            </label>
            <CategoryPicker v-model="form.category_id" :categories="categories" input-id="document-form-category" />
            <p v-if="form.errors.category_id" class="text-red-500 text-xs mt-1.5">{{ form.errors.category_id }}</p>
        </div>

        <!-- File Upload -->
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                Upload File <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input
                    type="file"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                    required
                    @change="handleFileUpload"
                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 dark:file:bg-blue-950 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900 cursor-pointer"
                />
            </div>
            <div v-if="form.doc_upload && !docError" class="mt-2 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 font-medium">
                    {{ formatFileSize(form.doc_upload.size) }}
                </span>
                <span v-if="isLargeFile" class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-950 px-2.5 py-0.5 font-medium text-blue-700 dark:text-blue-300">
                    ⚡ Chunked upload
                </span>
            </div>
            <p v-if="docError" class="text-red-500 text-xs mt-1.5">{{ docError }}</p>
            <p v-else-if="form.errors.doc_upload" class="text-red-500 text-xs mt-1.5">{{ form.errors.doc_upload }}</p>
        </div>

        <!-- Image Upload -->
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                Thumbnail Image <span class="text-slate-400 font-normal normal-case">(optional)</span>
            </label>
            <input
                type="file"
                accept="image/*"
                @change="handleImageUpload"
                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 dark:file:bg-purple-950 dark:file:text-purple-300 hover:file:bg-purple-100 dark:hover:file:bg-purple-900 cursor-pointer"
            />
            <div v-if="imagePreview" class="mt-3 relative inline-block">
                <img :src="imagePreview" class="w-32 h-32 object-cover rounded-xl border border-slate-200 dark:border-slate-700" />
                <button
                    type="button"
                    @click="imagePreview = null; form.image = null"
                    class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center text-xs hover:bg-red-600 transition-colors"
                >✕</button>
            </div>
            <p v-if="imageError" class="text-red-500 text-xs mt-1.5">{{ imageError }}</p>
            <p v-else-if="form.errors.image" class="text-red-500 text-xs mt-1.5">{{ form.errors.image }}</p>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                Description <span class="text-slate-400 font-normal normal-case">(optional)</span>
            </label>
            <textarea
                v-model="form.description"
                rows="3"
                class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-sm text-slate-90 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors resize-none"
                placeholder="Brief description of this document..."
            ></textarea>
            <p v-if="form.errors.description" class="text-red-500 text-xs mt-1.5">{{ form.errors.description }}</p>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button
                type="submit"
                :disabled="processing"
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
                <svg v-if="processing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ processing ? 'Uploading...' : submitLabel }}
            </button>
        </div>
    </div>
</template>
