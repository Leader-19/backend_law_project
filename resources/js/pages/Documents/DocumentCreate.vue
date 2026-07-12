<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref } from 'vue'
import { type BreadcrumbItem } from '@/types'
import CategoryPicker from '@/components/CategoryPicker.vue'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Create Document',
        href: '/documents',
    },
]

const page = usePage()
const categories = page.props.categories as any[]

const imagePreview = ref<string | null>(null)

const form = useForm({
    doc_name: '',
    doc_title: '',
    description: '',
    category_id: '',
    doc_upload: null as File | null,
    image: null as File | null,
})

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement

    if (target.files && target.files.length > 0) {
        form.doc_upload = target.files[0]
    }
}

const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement

    if (target.files && target.files.length > 0) {
        const file = target.files[0]

        form.image = file
        imagePreview.value = URL.createObjectURL(file)
    }
}

const submit = () => {
    form.post(route('documents.store'), {
        forceFormData: true,

        onSuccess: () => {
            form.reset()
            imagePreview.value = null
        },
    })
}
</script>

<template>
    <Head title="Create Document" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow">

            <div class="mb-6">
                <Link
                    :href="route('documents.index')"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    ← Back
                </Link>
            </div>

            <form
                @submit.prevent="submit"
                class="space-y-5"
            >
                <!-- Document Name -->
                <div>
                    <label class="block font-medium mb-1">
                        Document Name
                    </label>

                    <input
                        v-model="form.doc_name"
                        type="text"
                        class="w-full border rounded px-3 py-2"
                        placeholder="Document name"
                    />

                    <p
                        v-if="form.errors.doc_name"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.doc_name }}
                    </p>
                </div>

                <!-- Document Title -->
                <div>
                    <label class="block font-medium mb-1">
                        Document Title
                    </label>

                    <input
                        v-model="form.doc_title"
                        type="text"
                        class="w-full border rounded px-3 py-2"
                        placeholder="Title"
                    />

                    <p
                        v-if="form.errors.doc_title"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.doc_title }}
                    </p>
                </div>

                <!-- Category -->
                <div>
                    <label class="block font-medium mb-1">
                        Category
                    </label>

                    <CategoryPicker v-model="form.category_id" :categories="categories" input-id="create-document-category" />
                    <p class="mt-1 text-xs text-gray-500">Search by category name; subcategories are shown with their full path.</p>

                    <p
                        v-if="form.errors.category_id"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.category_id }}
                    </p>
                </div>

                <!-- Document Upload -->
                <div>
                    <label class="block font-medium mb-1">
                        Upload Document *
                    </label>

                    <input
                        type="file"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                        required
                        @change="handleFileUpload"
                        class="block w-full border rounded px-3 py-2"
                    />

                    <p class="mt-1 text-xs text-gray-500">
                        Required when creating a document.
                    </p>

                    <p
                        v-if="form.errors.doc_upload"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.doc_upload }}
                    </p>
                </div>

                <!-- Image Upload -->
                <div>
                    <label class="block font-medium mb-1">
                        Image
                    </label>

                    <input
                        type="file"
                        accept="image/*"
                        @change="handleImageUpload"
                        class="block w-full border rounded px-3 py-2"
                    />

                    <img
                        v-if="imagePreview"
                        :src="imagePreview"
                        class="mt-4 w-40 rounded border"
                    />

                    <p
                        v-if="form.errors.image"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.image }}
                    </p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block font-medium mb-1">
                        Description
                    </label>

                    <textarea
                        v-model="form.description"
                        rows="4"
                        class="w-full border rounded px-3 py-2"
                        placeholder="Description..."
                    ></textarea>

                    <p
                        v-if="form.errors.description"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Create Document' }}
                </button>
            </form>
        </div>
    </AppLayout>
</template>
