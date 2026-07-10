<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Pencil, Trash2 } from 'lucide-vue-next';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogClose
} from '@/components/ui/dialog';

interface Category {
    id: number;
    title: string;
    description: string | null;
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
        title: 'ប្រភេទ',
        href: '/categories',
    },
];

const props = defineProps<{
    category: Category;
    categories?: Category[];
    documents: Document[];
    pagination: Pagination;
}>();

const isEditOpen = ref(false);
const editingId = ref<number | null>(null);

const editForm = useForm({
    doc_name: '',
    doc_title: '',
    description: '',
    doc_upload: null as File | null,
    image: null as File | null,
    category_id: '' as string | number,
});

function handleEditFileUpload(e: Event) {
    const input = e.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        editForm.doc_upload = input.files[0];
    }
}

function handleEditImageUpload(e: Event) {
    const input = e.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        editForm.image = input.files[0];
    }
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

function submitEdit() {
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
    if (confirm("តើអ្នកចង់លុបឯកសារនេះមែនទេ?")) {
        router.delete(route('documents.destroy', id));
    }
}

function changePage(page: number) {
    router.get(route('categories.show', props.category.id), {
        page: page
    }, { preserveState: true });
}

function changeItemsPerPage(perPage: number) {
    router.get(route('categories.show', props.category.id), {
        per_page: perPage,
        page: 1
    }, { preserveState: true });
}
</script>

<template>
    <Head title="ព័ត៌មានប្រភេទ" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">
            <Link :href="route('categories.index')"
                class="cursor-pointer px-3 py-2 text-xs mb-3 font-medium text-white bg-blue-500 rounded">
                ត្រឡប់ក្រោយ
            </Link>

            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">ព័ត៌មានប្រភេទ</h3>
                <p><strong>{{ category.title }}</strong> </p>
                <!-- <p><strong>ការរៀបរាប់:</strong> {{ category.description ?? '-' }}</p> -->
            </div>

            <h3 class="text-lg font-semibold mb-3">ឯកសារក្នុងប្រភេទ</h3>

            <DataTable
                :data="documents"
                :pagination="pagination"
                :columns="['stt', 'doc_name', 'doc_title', 'image', 'doc_upload', 'description', 'actions']"
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
                    {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                </template>

                <template #doc_name="{ item }">
                    {{ item.doc_name }}
                </template>

                <template #doc_title="{ item }">
                    {{ item.doc_title }}
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
                មិនមានឯកសារក្នុងប្រភេទនេះ
            </div>

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
                            <select
                                v-model="editForm.category_id"
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                            >
                                <option value="">Select Category</option>
                                <option v-for="cat in props.categories || []" :key="cat.id" :value="cat.id">
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
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
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
        </div>
    </AppLayout>
</template>
