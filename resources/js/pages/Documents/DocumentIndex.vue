<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import {
    FileText,
    Plus,
    Pencil,
    Trash2,
    Search,
    Filter
} from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'
import { computed, ref } from 'vue'
import { can } from '@/lib/can'
import DataTable from '@/components/ui/data-table/DataTable.vue'
import CategoryPicker from '@/components/CategoryPicker.vue'
import {
    Dialog,
    DialogTrigger,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
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
    parent_id: number | null
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
    selectedCategoryIds: number[],
    pagination: Pagination
}>()

const selectedCategoryIds = ref<number[]>([...props.selectedCategoryIds])
const categoryFilterSearch = ref('')
const isCategoryFilterOpen = ref(false)
const searchQuery = ref<string>('')
const isCreateOpen = ref(false)
const isEditOpen = ref(false)
const editingId = ref<number | null>(null)
const selectedIds = ref<number[]>([])
const isAllSelected = computed(() => props.documents.length > 0 && props.documents.every((document) => selectedIds.value.includes(document.id)))
const allCategoriesSelected = computed(() => props.categories.length > 0 && props.categories.every((category) => selectedCategoryIds.value.includes(category.id)))
const categoryOptions = computed(() => {
    const categoriesById = new Map(props.categories.map((category) => [category.id, category]))

    function labelFor(category: Category, visited: number[] = []): string {
        if (category.parent_id === null || visited.includes(category.id)) return category.title

        const parent = categoriesById.get(category.parent_id)
        return parent ? `${labelFor(parent, [...visited, category.id])} — ${category.title}` : category.title
    }

    return props.categories.map((category) => ({ id: category.id, label: labelFor(category) }))
})
const filteredCategoryOptions = computed(() => {
    const query = categoryFilterSearch.value.trim().toLocaleLowerCase()
    return query ? categoryOptions.value.filter((category) => category.label.toLocaleLowerCase().includes(query)) : categoryOptions.value
})

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

function toggleSelectAll() {
    selectedIds.value = isAllSelected.value ? [] : props.documents.map((document) => document.id)
}

function deleteSelected() {
    if (selectedIds.value.length === 0 || !confirm(`Delete ${selectedIds.value.length} selected documents?`)) return

    router.delete(route('documents.bulk-destroy'), {
        data: { ids: selectedIds.value },
        onSuccess: () => { selectedIds.value = [] },
    })
}

function changeItemsPerPage(perPage: number) {
    router.get(route('documents.index'), {
        per_page: perPage,
        category_ids: selectedCategoryIds.value,
        search: searchQuery.value,
        page: 1
    }, { preserveState: true })
}

function changePage(page: number) {
    if (page < 1 || page > props.pagination.last_page) return
    router.get(route('documents.index'), {
        per_page: props.pagination.per_page,
        category_ids: selectedCategoryIds.value,
        search: searchQuery.value,
        page: page
    }, { preserveState: true })
}

function applyCategoryFilter() {
    router.get(route('documents.index'), {
        category_ids: selectedCategoryIds.value,
        search: searchQuery.value,
        per_page: props.pagination.per_page,
        page: 1
    }, { preserveState: true })
}

function toggleAllCategories(event: Event) {
    const checked = (event.target as HTMLInputElement).checked
    selectedCategoryIds.value = checked ? props.categories.map((category) => category.id) : []
    applyCategoryFilter()
}

function clearCategoryFilter() {
    selectedCategoryIds.value = []
    applyCategoryFilter()
}

function handleSearch() {
    router.get(route('documents.index'), {
        search: searchQuery.value,
        category_ids: selectedCategoryIds.value,
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
                <div class="flex flex-wrap items-center gap-3">
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
                                <DialogDescription>
                                    បញ្ចូលពត៌មានឯកសារថ្មី
                                </DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="submitCreate" class="space-y-4 mt-4">
                                <div>
                                    <label class="block text-sm font-medium">ឈ្មោះឯកសារ</label>
                                    <input
                                        type="text"
                                        v-model="createForm.doc_name"
                                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
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
                                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                        placeholder="Enter title"
                                    />
                                    <p v-if="createForm.errors.doc_title" class="text-red-500 text-sm mt-1">
                                        {{ createForm.errors.doc_title }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">ប្រភេទ</label>
                                    <CategoryPicker v-model="createForm.category_id" :categories="props.categories" input-id="quick-create-document-category" />
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
                                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
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

                    <div class="relative">
                        <button type="button" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50" @click="isCategoryFilterOpen = !isCategoryFilterOpen">
                            <Filter class="h-4 w-4" /> Filter categories
                            <span v-if="selectedCategoryIds.length" class="rounded-full bg-blue-100 px-1.5 py-0.5 text-xs text-blue-700">{{ selectedCategoryIds.length }}</span>
                        </button>
                        <div v-if="isCategoryFilterOpen" class="absolute left-0 z-40 mt-2 w-80 rounded-lg border border-gray-200 bg-white p-3 shadow-lg">
                            <input v-model="categoryFilterSearch" type="search" placeholder="Search categories" class="mb-3 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                            <label class="flex cursor-pointer items-center gap-2 border-b border-gray-200 pb-2 text-sm font-semibold"><input type="checkbox" :checked="allCategoriesSelected" @change="toggleAllCategories" /> Select all categories</label>
                            <div class="max-h-64 overflow-y-auto py-2">
                                <label v-for="category in filteredCategoryOptions" :key="category.id" class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-gray-50"><input v-model="selectedCategoryIds" type="checkbox" :value="category.id" @change="applyCategoryFilter" /><span>{{ category.label }}</span></label>
                                <p v-if="filteredCategoryOptions.length === 0" class="px-2 py-3 text-sm text-gray-500">No categories found.</p>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 pt-2"><button type="button" class="text-sm text-blue-600 hover:underline" @click="clearCategoryFilter">Clear filter</button><button type="button" class="text-sm text-gray-600 hover:underline" @click="isCategoryFilterOpen = false">Close</button></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Dialog (no visible trigger button here; opened programmatically from the table row) -->
            <Dialog v-model:open="isEditOpen">
                    <DialogContent class="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>កែសម្រួលឯកសារ</DialogTitle>
                            <DialogDescription>
                                កែសម្រួលពត៌មានឯកសារ
                            </DialogDescription>
                        </DialogHeader>
                    <form @submit.prevent="submitEdit" class="space-y-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium">ឈ្មោះឯកសារ</label>
                            <input
                                type="text"
                                v-model="editForm.doc_name"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
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
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                placeholder="Enter title"
                            />
                            <p v-if="editForm.errors.doc_title" class="text-red-500 text-sm mt-1">
                                {{ editForm.errors.doc_title }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">ប្រភេទ</label>
                            <CategoryPicker v-model="editForm.category_id" :categories="props.categories" input-id="quick-edit-document-category" />
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
                                class="mt-1 block w-full border text-sm border border-gray-300 rounded-md p-2"
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
                                class="mt-1 block w-full border rounded-md border-gray-300 px-3 py-2"
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

            <DataTable
                :data="props.documents"
                :pagination="props.pagination"
                :columns="['select', 'stt', 'doc_name', 'doc_title', 'image', 'doc_upload', 'description', 'actions']"
                class="mt-3"
                @page-change="changePage"
                @per-page-change="changeItemsPerPage"
            >
                <template #header-select>
                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" aria-label="Select all documents" />
                </template>
                <template #select="{ item }">
                    <input v-model="selectedIds" type="checkbox" :value="item.id" :aria-label="`Select ${item.doc_name}`" />
                </template>
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

            <div v-if="selectedIds.length && can('document.delete')" class="mt-3 flex items-center gap-3">
                <span class="text-sm text-gray-600">{{ selectedIds.length }} selected</span>
                <button @click="deleteSelected" class="inline-flex items-center gap-2 rounded bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">
                    <Trash2 class="w-4 h-4" /> Delete selected
                </button>
            </div>

        </div>
    </AppLayout>
</template>
