<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import {
    FileText,
    Plus,
    Pencil,
    Trash2,
    Upload
} from '@lucide/vue'
import { type BreadcrumbItem } from '@/types'
import { computed, ref } from 'vue'
import { can } from '@/lib/can'
import DataTable from '@/components/ui/data-table/DataTable.vue'
import DocumentFormDialog from '@/components/DocumentFormDialog.vue'
import DocumentEditDialog from '@/components/DocumentEditDialog.vue'
import DocumentSearchBar from '@/components/documents/DocumentSearchBar.vue'
import CategoryPicker from '@/components/CategoryPicker.vue'

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
    documents: Document[]
    categories: Category[]
    selectedCategoryIds: number[]
    pagination: Pagination
    searchType: string
}>()

const selectedCategoryIds = ref<number[]>([...props.selectedCategoryIds])
const categoryFilterSearch = ref('')
const isCategoryFilterOpen = ref(false)
const searchQuery = ref<string>('')
const searchType = ref<string>(props.searchType || 'all')
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

function handleSubmitCreate() {
    createForm.post(route('documents.store'), {
        forceFormData: true,
        onSuccess: () => {
            isCreateOpen.value = false
            createForm.reset()
        }
    })
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

function handleSubmitEdit() {
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
        search_type: searchType.value,
        page: 1
    }, { preserveState: true })
}

function changePage(page: number) {
    if (page < 1 || page > props.pagination.last_page) return
    router.get(route('documents.index'), {
        per_page: props.pagination.per_page,
        category_ids: selectedCategoryIds.value,
        search: searchQuery.value,
        search_type: searchType.value,
        page: page
    }, { preserveState: true })
}

function applyCategoryFilter() {
    router.get(route('documents.index'), {
        category_ids: selectedCategoryIds.value,
        search: searchQuery.value,
        search_type: searchType.value,
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
        search_type: searchType.value,
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
                    <button
                        v-if="can('document.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 transition"
                        @click="isCreateOpen = true"
                    >
                        <Plus class="w-4 h-4" />
                        បង្កើត​ ឯកសារ
                    </button>

                    <Link
                        v-if="can('document.create')"
                        :href="route('documents.batch.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-purple-600 rounded-md hover:bg-purple-700 transition"
                    >
                        <Upload class="w-4 h-4" />
                        Batch Import
                    </Link>

                    <div v-if="selectedIds.length && can('document.delete')" class="inline-flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-md">
                        <span class="text-sm text-red-700">{{ selectedIds.length }} selected</span>
                        <button @click="deleteSelected" class="inline-flex items-center gap-1 rounded bg-red-600 px-2 py-1 text-xs font-semibold text-white hover:bg-red-700">
                            <Trash2 class="w-3 h-3" /> Delete selected
                        </button>
                    </div>

                     <DocumentSearchBar
                         v-model:searchQuery="searchQuery"
                         v-model:searchType="searchType"
                         v-model:categoryFilterSearch="categoryFilterSearch"
                         v-model:isCategoryFilterOpen="isCategoryFilterOpen"
                         :categoryOptions="categoryOptions"
                         :filteredCategoryOptions="filteredCategoryOptions"
                         :allCategoriesSelected="allCategoriesSelected"
                         v-model:selectedCategoryIds="selectedCategoryIds"
                         @search="handleSearch"
                         @toggle-category-filter="isCategoryFilterOpen = !isCategoryFilterOpen"
                         @toggle-all-categories="toggleAllCategories"
                         @clear-category-filter="clearCategoryFilter"
                         @filter-category="applyCategoryFilter"
                     />
                </div>
            </div>

            <!-- Edit Dialog -->
            <DocumentEditDialog
                :open="isEditOpen"
                :form="editForm"
                :processing="editForm.processing"
                @update:open="isEditOpen = $event"
                @submit="handleSubmitEdit"
            >
                <template #category>
                    <CategoryPicker v-model="editForm.category_id" :categories="props.categories" input-id="edit-document-category" />
                </template>
            </DocumentEditDialog>

            <!-- Create Dialog -->
            <DocumentFormDialog
                :open="isCreateOpen"
                title="បង្កើតឯកសារ"
                description="បញ្ចូលពត៌មានឯកសារថ្មី"
                submit-label="បង្កើត"
                :processing="createForm.processing"
                :form="createForm"
                @update:open="isCreateOpen = $event"
                @submit="handleSubmitCreate"
            >
                <template #category>
                    <CategoryPicker v-model="createForm.category_id" :categories="props.categories" input-id="create-document-category" />
                </template>
            </DocumentFormDialog>

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

        </div>
    </AppLayout>
</template>