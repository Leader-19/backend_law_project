<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import {
    FileText,
    Plus,
    Pencil,
    Trash2,
    Search
} from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'
import { ref } from 'vue'
import { can } from '@/lib/can'
import DataTable from '@/components/ui/data-table/DataTable.vue'
import {
    Dialog,
    DialogTrigger,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogClose
} from '@/components/ui/dialog'

interface Document {
    id: number
    doc_name: string
    doc_title: string
    description: string | null
    doc_upload: string
    image: string | null
    category_id: number
}

interface Category {
    id: number
    title: string
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'ឯកសារ',
        href: '/documents',
    },
]

const props = defineProps<{
    documents: Document[],
    categories: Category[],
    pagination: Pagination
}>()

const selectedCategory = ref<number | null>(null)
const searchQuery = ref<string>('')
const isCreateOpen = ref(false)
const isEditOpen = ref(false)
const editingId = ref<number | null>(null)

const createForm = useForm({
    doc_name: '',
    doc_title: '',
    description: '',
    doc_upload: null as File | null,
    image: null as File | null,
    category_id: '',
})

const editForm = useForm({
    doc_name: '',
    doc_title: '',
    description: '',
    doc_upload: null as File | null,
    image: null as File | null,
    category_id: '' as string | number,
    _method: 'put',
})

function handleCreateFileUpload(e: Event) {
    const input = e.target as HTMLInputElement
    if (input.files && input.files.length > 0) {
        createForm.doc_upload = input.files[0]
    }
}

function handleCreateImageUpload(e: Event) {
    const input = e.target as HTMLInputElement
    if (input.files && input.files.length > 0) {
        createForm.image = input.files[0]
    }
}

function submitCreate() {
    createForm.post(route('documents.store'), {
        forceFormData: true,
        onSuccess: () => {
            isCreateOpen.value = false
            createForm.reset()
        }
    })
}

function handleEditFileUpload(e: Event) {
    const input = e.target as HTMLInputElement
    if (input.files && input.files.length > 0) {
        editForm.doc_upload = input.files[0]
    }
}

function handleEditImageUpload(e: Event) {
    const input = e.target as HTMLInputElement
    if (input.files && input.files.length > 0) {
        editForm.image = input.files[0]
    }
}

function openEditDialog(item: Document) {
    editingId.value = item.id
    editForm.clearErrors()
    editForm.doc_name = item.doc_name
    editForm.doc_title = item.doc_title
    editForm.description = item.description ?? ''
    editForm.category_id = item.category_id
    editForm.doc_upload = null
    editForm.image = null
    isEditOpen.value = true
}

function submitEdit() {
    if (editingId.value === null) return

    editForm.post(route('documents.update', editingId.value), {
        forceFormData: true,
        onSuccess: () => {
            isEditOpen.value = false
            editingId.value = null
            editForm.reset()
        }
    })
}

function deleteDocument(id: number) {
    if (confirm("Are you want to delete this Final Slide")) {
        router.delete(route('documents.destroy', id));
    }
}

function changeItemsPerPage(perPage: number) {
    router.get(route('documents.index'), {
        per_page: perPage,
        category_id: selectedCategory.value,
        search: searchQuery.value,
        page: 1
    }, { preserveState: true })
}

function changePage(page: number) {
    if (page < 1 || page > props.pagination.last_page) return
    router.get(route('documents.index'), {
        per_page: props.pagination.per_page,
        category_id: selectedCategory.value,
        search: searchQuery.value,
        page: page
    }, { preserveState: true })
}

function filterByCategory(categoryId: number | null) {
    selectedCategory.value = categoryId
    router.get(route('documents.index'), {
        category_id: categoryId,
        search: searchQuery.value,
        per_page: props.pagination.per_page,
        page: 1
    }, { preserveState: true })
}

function handleSearch() {
    router.get(route('documents.index'), {
        search: searchQuery.value,
        category_id: selectedCategory.value,
        per_page: props.pagination.per_page,
        page: 1
    }, { preserveState: true })
}
</script>

<template>
    <Head title="ឯកសារ" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">

            <!-- Create Button and Search -->
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-4">
                    <Dialog v-model:open="isCreateOpen">
                        <DialogTrigger as-child>
                            <button
                                v-if="can('document.create')"
                                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 transition"
                            >
                                <Plus class="w-4 h-4" />
                                បង្កើត​ ឯកសារ
                            </button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-lg">
                            <DialogHeader>
                                <DialogTitle>បង្កើតឯកសារ</DialogTitle>
                            </DialogHeader>
                            <form @submit.prevent="submitCreate" class="space-y-4 mt-4">
                                <div>
                                    <label class="block text-sm font-medium">ឈ្មោះឯកសារ</label>
                                    <input
                                        type="text"
                                        v-model="createForm.doc_name"
                                        class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                                        placeholder="Enter document name"
                                    />
                                    <p v-if="createForm.errors.doc_name" class="text-red-500 text-sm mt-1">
                                        {{ createForm.errors.doc_name }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">ចំណងជើង</label>
                                    <input
                                        type="text"
                                        v-model="createForm.doc_title"
                                        class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                                        placeholder="Enter title"
                                    />
                                    <p v-if="createForm.errors.doc_title" class="text-red-500 text-sm mt-1">
                                        {{ createForm.errors.doc_title }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">ប្រភេទ</label>
                                    <select
                                        v-model="createForm.category_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                                    >
                                        <option value="">Select Category</option>
                                        <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">
                                            {{ cat.title }}
                                        </option>
                                    </select>
                                    <p v-if="createForm.errors.category_id" class="text-red-500 text-sm mt-1">
                                        {{ createForm.errors.category_id }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Upload File</label>
                                    <input
                                        type="file"
                                        @change="handleCreateFileUpload"
                                        class="mt-1 block w-full text-sm border border-gray-300 rounded-md p-2"
                                    />
                                    <p v-if="createForm.errors.doc_upload" class="text-red-500 text-sm mt-1">
                                        {{ createForm.errors.doc_upload }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Image</label>
                                    <input
                                        type="file"
                                        @change="handleCreateImageUpload"
                                        accept="image/*"
                                        class="mt-1 block w-full text-sm border border-gray-300 rounded-md p-2"
                                    />
                                    <p v-if="createForm.errors.image" class="text-red-500 text-sm mt-1">
                                        {{ createForm.errors.image }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">រៀបរាប់</label>
                                    <input
                                        type="text"
                                        v-model="createForm.description"
                                        class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                                        placeholder="Optional description"
                                    />
                                    <p v-if="createForm.errors.description" class="text-red-500 text-sm mt-1">
                                        {{ createForm.errors.description }}
                                    </p>
                                </div>
                                <DialogFooter>
                                    <DialogClose as-child>
                                        <button
                                            type="button"
                                            class="px-3 py-2 text-xs font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300"
                                        >
                                            បោះបង់
                                        </button>
                                    </DialogClose>
                                    <button
                                        type="submit"
                                        :disabled="createForm.processing"
                                        class="px-3 py-2 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 disabled:opacity-50"
                                    >
                                        បង្កើត
                                    </button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <!-- Search Input -->
                    <div class="relative flex items-center">
                        <input
                            v-model="searchQuery"
                            @keyup.enter="handleSearch"
                            type="text"
                            placeholder="ស្វែងរក..."
                            class="w-64 pl-9 pr-3 py-1.5 rounded-l border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <button
                            @click="handleSearch"
                            class="px-3 py-1.5 rounded-r border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-600"
                        >
                            ស្វែងរក
                        </button>
                    </div>
                </div>
            </div>

            <!-- Edit Dialog (no visible trigger button here; opened programmatically from the table row) -->
            <Dialog v-model:open="isEditOpen">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>កែសម្រួលឯកសារ</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="submitEdit" class="space-y-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium">ឈ្មោះឯកសារ</label>
                            <input
                                type="text"
                                v-model="editForm.doc_name"
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                                placeholder="Enter document name"
                            />
                            <p v-if="editForm.errors.doc_name" class="text-red-500 text-sm mt-1">
                                {{ editForm.errors.doc_name }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">ចំណងជើង</label>
                            <input
                                type="text"
                                v-model="editForm.doc_title"
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                                placeholder="Enter title"
                            />
                            <p v-if="editForm.errors.doc_title" class="text-red-500 text-sm mt-1">
                                {{ editForm.errors.doc_title }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">ប្រភេទ</label>
                            <select
                                v-model="editForm.category_id"
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                            >
                                <option value="">Select Category</option>
                                <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">
                                    {{ cat.title }}
                                </option>
                            </select>
                            <p v-if="editForm.errors.category_id" class="text-red-500 text-sm mt-1">
                                {{ editForm.errors.category_id }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Upload File (leave empty to keep current)</label>
                            <input
                                type="file"
                                @change="handleEditFileUpload"
                                class="mt-1 block w-full text-sm border border-gray-300 rounded-md p-2"
                            />
                            <p v-if="editForm.errors.doc_upload" class="text-red-500 text-sm mt-1">
                                {{ editForm.errors.doc_upload }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Image (leave empty to keep current)</label>
                            <input
                                type="file"
                                @change="handleEditImageUpload"
                                accept="image/*"
                                class="mt-1 block w-full text-sm border border-gray-300 rounded-md p-2"
                            />
                            <p v-if="editForm.errors.image" class="text-red-500 text-sm mt-1">
                                {{ editForm.errors.image }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">រៀបរាប់</label>
                            <input
                                type="text"
                                v-model="editForm.description"
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                                placeholder="Optional description"
                            />
                            <p v-if="editForm.errors.description" class="text-red-500 text-sm mt-1">
                                {{ editForm.errors.description }}
                            </p>
                        </div>
                        <DialogFooter>
                            <DialogClose as-child>
                                <button
                                    type="button"
                                    class="px-3 py-2 text-xs font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300"
                                >
                                    បោះបង់
                                </button>
                            </DialogClose>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50"
                            >
                                រក្សាទុក
                            </button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Category Row -->
            <div class="flex flex-wrap gap-2 mt-4 mb-3">

                <!-- All -->
                <button
                    @click="filterByCategory(null)"
                    :class="[
                        'px-4 py-1 text-xs font-medium rounded transition',
                        selectedCategory === null
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-200 text-gray-700 hover:bg-blue-500 hover:text-white'
                    ]"
                >
                    All
                </button>

                <!-- Categories -->
                <button
                    v-for="cat in props.categories"
                    :key="cat.id"
                    @click="filterByCategory(cat.id)"
                    :class="[
                        'px-4 py-1 text-xs font-medium rounded transition',
                        selectedCategory === cat.id
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-200 text-gray-700 hover:bg-blue-500 hover:text-white'
                    ]"
                >
                    {{ cat.title }}
                </button>

            </div>

            <DataTable
                :data="props.documents"
                :pagination="props.pagination"
                :columns="['stt', 'doc_name', 'doc_title', 'image', 'doc_upload', 'description', 'actions']"
                class="mt-3"
                @page-change="changePage"
                @per-page-change="changeItemsPerPage"
            >
                <template #header-stt>លរ</template>
                <template #header-doc_name>ឈ្មោះ​ ឯកសារ</template>
                <template #header-doc_title>ចំណងជើង</template>
                <template #header-image>រូបភាព</template>
                <template #header-doc_upload>File</template>
                <template #header-description>រៀបរាប់</template>
                <template #header-actions>សកម្មភាព</template>

                <template #stt="{ index }">
                    {{ (props.pagination.current_page - 1) * props.pagination.per_page + index + 1 }}
                </template>

                <template #image="{ item }">
                    <div v-if="item.image" class="w-16 h-16 overflow-hidden rounded border border-gray-200">
                        <a
                            :href="`/storage/${item.image}`"
                            target="_blank"
                            class="block w-full h-full"
                        >
                            <img
                                :src="`/storage/${item.image}`"
                                :alt="item.doc_name"
                                class="w-full h-full object-cover hover:scale-105 transition-transform"
                            />
                        </a>
                    </div>
                    <span v-else class="text-gray-400 text-xs">-</span>
                </template>

                <template #doc_upload="{ item }">
                    <a
                        :href="`/storage/${item.doc_upload}`"
                        target="_blank"
                        class="text-blue-600 hover:underline"
                    >
                        View
                    </a>
                </template>

                <template #actions="{ item }">
                    <div class="flex justify-center gap-2">

                        <Link
                            :href="route('documents.show', item.id)"
                            class="p-2 bg-gray-800 rounded hover:bg-gray-700"
                        >
                            <FileText class="w-4 h-4 text-white" />
                        </Link>

                        <button
                            @click="openEditDialog(item)"
                            class="p-2 bg-blue-500 rounded hover:bg-blue-900"
                        >
                            <Pencil class="w-4 h-4 text-white" />
                        </button>

                        <button
                            @click="deleteDocument(item.id)"
                            class="p-2 bg-red-500 rounded hover:bg-red-700"
                        >
                            <Trash2 class="w-4 h-4 text-white" />
                        </button>

                    </div>
                </template>
            </DataTable>

        </div>
    </AppLayout>
</template>
