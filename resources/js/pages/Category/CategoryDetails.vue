<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Pencil, Trash2, Plus, FolderTree } from 'lucide-vue-next';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
    DialogClose
} from '@/components/ui/dialog';

interface Category {
    id: number;
    title: string;
    description: string | null;
}

interface Subcategory {
    id: number;
    title: string;
    description: string | null;
    documents_count: number;
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
    subcategories?: Subcategory[];
    pagination: Pagination;
}>();

const isEditOpen = ref(false);
const isSubcategoryOpen = ref(false);
const editingId = ref<number | null>(null);

const editForm = useForm({
    doc_name: '',
    doc_title: '',
    description: '',
    doc_upload: null as File | null,
    image: null as File | null,
    category_id: '' as string | number,
});

const subcategoryForm = useForm({
    title: '',
    description: '',
    parent_id: null as number | null,
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

function openSubcategoryDialog() {
    subcategoryForm.reset();
    subcategoryForm.parent_id = props.category.id;
    isSubcategoryOpen.value = true;
}

function submitSubcategory() {
    subcategoryForm.post(route('categories.store'), {
        onSuccess: () => {
            isSubcategoryOpen.value = false;
            subcategoryForm.reset();
            router.reload({ only: ['subcategories'] });
        }
    });
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
        <div class="mx-auto max-w-6xl p-4 sm:p-6">
            <Link :href="route('categories.index')" class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300">
                ត្រឡប់ក្រោយ
            </Link>

            <div class="mb-6 mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="bg-gradient-to-r from-blue-700 to-indigo-700 p-6 text-white">
                    <div class="flex items-start gap-4"><div class="rounded-xl bg-white/15 p-3"><FolderTree class="h-6 w-6" /></div><div><p class="text-sm text-blue-100">ព័ត៌មានប្រភេទ</p><h1 class="text-2xl font-bold">{{ category.title }}</h1><p v-if="category.description" class="mt-2 text-sm text-blue-100">{{ category.description }}</p></div></div>
                </div>
            </div>

            <!-- Subcategories -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold">ប្រភេទរង</h3>
                    <button
                        @click="openSubcategoryDialog"
                        class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 transition"
                    >
                        <Plus class="w-4 h-4" />
                        បង្កើតប្រភេទរង
                    </button>
                </div>
                <div v-if="subcategories && subcategories.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="sub in subcategories" :key="sub.id"
                        class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ sub.title }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" v-if="sub.description">
                                    {{ sub.description }}
                                </p>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ sub.documents_count }} ឯកសារ
                            </span>
                        </div>
                        <Link :href="route('categories.show', sub.id)"
                            class="mt-3 inline-block text-xs text-blue-600 hover:underline">
                            មើលឯកសារ
                        </Link>
                    </div>
                </div>
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
                        <DialogDescription>
                            កែសម្រួលពត៌មានឯកសារក្នុងប្រភេទនេះ
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
                            <select
                                v-model="editForm.category_id"
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                            >
                                <option value="">Select Category</option>
                                <template v-for="cat in props.categories" :key="cat.id">
                                    <option :value="cat.id">
                                        {{ cat.title }}
                                    </option>
                                    <optgroup v-if="cat.children && cat.children.length" :label="cat.title + ' (subcategories)'">
                                        <option v-for="sub in cat.children" :key="sub.id" :value="sub.id">
                                            — {{ sub.title }}
                                        </option>
                                    </optgroup>
                                </template>
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

            <!-- Create Subcategory Dialog -->
            <Dialog v-model:open="isSubcategoryOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>បង្កើតប្រភេទរង</DialogTitle>
                        <DialogDescription>
                            បង្កើតប្រភេទរងក្រោយប្រភេទមេ
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitSubcategory" class="space-y-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium">ឈ្មោះប្រភេទរង</label>
                            <input
                                type="text"
                                v-model="subcategoryForm.title"
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                                placeholder="ដាក់ឈ្មោះប្រភេទរង"
                            />
                            <p v-if="subcategoryForm.errors.title" class="text-red-500 text-sm mt-1">
                                {{ subcategoryForm.errors.title }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">រៀបរាប់</label>
                            <input
                                type="text"
                                v-model="subcategoryForm.description"
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                                placeholder="រៀបរាប់"
                            />
                            <p v-if="subcategoryForm.errors.description" class="text-red-500 text-sm mt-1">
                                {{ subcategoryForm.errors.description }}
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
                                :disabled="subcategoryForm.processing"
                                class="px-3 py-2 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 disabled:opacity-50"
                            >
                                បង្កើត
                            </button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
