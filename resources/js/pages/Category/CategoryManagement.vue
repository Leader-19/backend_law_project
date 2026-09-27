<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Pencil, Trash2, Plus } from '@lucide/vue';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { ref } from 'vue';
import { can } from '@/lib/can';
import { type BreadcrumbItem } from '@/types';
import DocumentFormDialog from '@/components/DocumentFormDialog.vue';
import DocumentEditDialog from '@/components/DocumentEditDialog.vue';
import CategoryFilterChips from '@/components/category/CategoryFilterChips.vue';
import CategorySearchBar from '@/components/category/CategorySearchBar.vue';
import CategoryPicker from '@/components/CategoryPicker.vue';

interface Category {
    id: number;
    title: string;
    description: string | null;
    documents_count: number;
    children?: {
        id: number;
        title: string;
        documents_count: number;
    }[];
}

interface Document {
    id: number;
    doc_name: string;
    doc_title: string;
    description: string | null;
    doc_upload: string;
    image: string | null;
    category_id: number;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'គ្របគ្រងប្រភេទ',
        href: '/category-management',
    },
];

const props = defineProps<{
    categories: Category[];
    documents: Document[];
    pagination: Pagination;
}>();

const isEditOpen = ref(false);
const isCreateOpen = ref(false);
const editingId = ref<number | null>(null);
const selectedCategory = ref<number | null>(null);
const searchQuery = ref<string>('');
const isDeleteOpen = ref(false);
const deletingId = ref<number | null>(null);

const createForm = useForm({
    doc_name: '',
    doc_title: '',
    description: '',
    doc_upload: null as File | null,
    image: null as File | null,
    category_id: '' as string | number,
});

const editForm = useForm({
    _method: 'put',
    doc_name: '',
    doc_title: '',
    description: '',
    doc_upload: null as File | null,
    image: null as File | null,
    category_id: '' as string | number,
});

function handleSubmitCreate() {
    createForm.post(route('documents.store'), {
        forceFormData: true,
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
        }
    });
}

function openEditDialog(item: Document) {
    editingId.value = item.id;
    editForm.clearErrors();
    editForm.doc_name = item.doc_name;
    editForm.doc_title = item.doc_title;
    editForm.description = item.description ?? '';
    editForm.category_id = item.category_id;
    editForm.doc_upload = null;
    editForm.image = null;
    isEditOpen.value = true;
}

function handleSubmitEdit() {
    if (editingId.value === null) return;

    editForm.post(route('documents.update', editingId.value), {
        forceFormData: true,
        onSuccess: () => {
            isEditOpen.value = false;
            editingId.value = null;
            editForm.reset();
        }
    });
}

function deleteDocument(id: number) {
    deletingId.value = id
    isDeleteOpen.value = true
}

function confirmDeleteDocument() {
    if (deletingId.value === null) return
    router.delete(route('documents.destroy', deletingId.value), {
        onSuccess: () => {
            isDeleteOpen.value = false
            deletingId.value = null
        }
    })
}

function changePage(page: number) {
    router.get(route('category-management.index'), {
        category_id: selectedCategory.value,
        search: searchQuery.value,
        page: page
    }, { preserveState: true });
}

function changeItemsPerPage(perPage: number) {
    router.get(route('category-management.index'), {
        category_id: selectedCategory.value,
        search: searchQuery.value,
        per_page: perPage,
        page: 1
    }, { preserveState: true });
}

function filterByCategory(categoryId: number | null) {
    selectedCategory.value = categoryId;
    router.get(route('category-management.index'), {
        category_id: categoryId,
        search: searchQuery.value,
        per_page: props.pagination.per_page,
        page: 1
    }, { preserveState: true });
}

function handleSearch() {
    router.get(route('category-management.index'), {
        search: searchQuery.value,
        category_id: selectedCategory.value,
        per_page: props.pagination.per_page,
        page: 1
    }, { preserveState: true });
}
</script>

<template>
    <Head title="គ្របគ្រងប្រភេទ" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-4">
                    <button
                        v-if="can('document.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 transition"
                        @click="isCreateOpen = true"
                    >
                        <Plus class="w-4 h-4" />
                        បង្កើតឯកសារថ្មី
                    </button>

                    <DocumentFormDialog
                            :open="isCreateOpen"
                            title="បង្កើតឯកសារថ្មី"
                            description="បញ្ចូលពត៌មានឯកសារថ្មីក្នុងប្រភេទនេះ"
                            submit-label="បង្កើត"
                            :processing="createForm.processing"
                            v-model:form="createForm"
                            @update:open="isCreateOpen = $event"
                            @submit="handleSubmitCreate"
                        >
                            <template #category>
                                <CategoryPicker v-model="createForm.category_id" :categories="props.categories" input-id="create-document-category-management" />
                            </template>
                        </DocumentFormDialog>

                    <CategorySearchBar
                        v-model:searchQuery="searchQuery"
                        @search="handleSearch"
                    />
                </div>
            </div>

            <CategoryFilterChips
                :categories="props.categories"
                :selected-category="selectedCategory"
                @filter="filterByCategory"
            />

            <DataTable
                :data="props.documents"
                :pagination="props.pagination"
                :columns="['stt', 'doc_name', 'doc_title', 'category', 'image', 'doc_upload', 'description', 'actions']"
                @page-change="changePage"
                @per-page-change="changeItemsPerPage"
            >
                <template #header-stt>លរ</template>
                <template #header-doc_name>ឈ្មោះ​ ឯកសារ</template>
                <template #header-doc_title>ចំណងជើង</template>
                <template #header-category>ប្រភេទ</template>
                <template #header-image>រូបភាព</template>
                <template #header-doc_upload>File</template>
                <template #header-description>រៀបរាប់</template>
                <template #header-actions>សកម្មភាព</template>

                <template #stt="{ index }">
                    {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                </template>

                <template #doc_name="{ item }">
                    {{ item.doc_name }}
                </template>

                <template #doc_title="{ item }">
                    {{ item.doc_title }}
                </template>

                <template #category="{ item }">
                    {{ props.categories.find(c => c.id === item.category_id)?.title ?? '-' }}
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

                <template #description="{ item }">
                    {{ item.description ?? '-' }}
                </template>

                <template #actions="{ item }">
                    <div class="flex justify-center gap-2">
                        <button
                            @click="openEditDialog(item)"
                            class="p-2 bg-blue-500 rounded hover:bg-blue-700"
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

            <div v-if="documents.length === 0" class="text-center py-8 text-gray-400">
                មិនមានឯកសារ
            </div>

            <DocumentEditDialog
                :open="isEditOpen"
                v-model:form="editForm"
                :processing="editForm.processing"
                @update:open="isEditOpen = $event"
                @submit="handleSubmitEdit"
            >
                <template #category>
                    <CategoryPicker v-model="editForm.category_id" :categories="props.categories" input-id="edit-document-category-management" />
                </template>
            </DocumentEditDialog>

            <!-- Delete Document Confirmation -->
            <ConfirmModal
                :open="isDeleteOpen"
                title="Delete Document"
                description="Are you sure you want to delete this document? This action cannot be undone."
                confirm-label="Delete"
                @confirm="confirmDeleteDocument"
                @update:open="isDeleteOpen = $event"
            />
        </div>
    </AppLayout>
</template>
