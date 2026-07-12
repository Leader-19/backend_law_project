<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { Plus, Pencil, Trash2, Eye, Filter } from 'lucide-vue-next'
import DataTable from '@/components/ui/data-table/DataTable.vue'
import { can } from '@/lib/can'
import { type BreadcrumbItem } from '@/types'
import CategoryPicker from '@/components/CategoryPicker.vue'
import { computed, ref } from 'vue'
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

interface Category {
    id: number
    title: string
    description: string | null
    parent_id: number | null
    parent_title: string | null
    documents_count: number
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'ប្រភេទ',
        href: '/categories',
    },
]

const props = defineProps<{
    categories: Category[]
    parents: { id: number; title: string }[]
    selectedParentIds: number[]
    pagination: Pagination
}>()

const isCreateOpen = ref(false)
const isEditOpen = ref(false)
const editingId = ref<number | null>(null)
const selectedIds = ref<number[]>([])
const selectedParentIds = ref<number[]>([...props.selectedParentIds])
const parentFilterSearch = ref('')
const isParentFilterOpen = ref(false)
const isAllSelected = computed(() => props.categories.length > 0 && props.categories.every((category) => selectedIds.value.includes(category.id)))
const filteredParents = computed(() => {
    const query = parentFilterSearch.value.trim().toLocaleLowerCase()
    return query ? props.parents.filter((parent) => parent.title.toLocaleLowerCase().includes(query)) : props.parents
})

const createForm = useForm({
    title: '',
    description: '',
    parent_id: null as number | null,
})

const editForm = useForm({
    title: '',
    description: '',
    parent_id: null as number | null,
})

function submitCreate() {
    createForm.post(route('categories.store'), {
        onSuccess: () => {
            isCreateOpen.value = false
            createForm.reset()
        }
    })
}

function openEditDialog(item: Category) {
    editingId.value = item.id
    editForm.clearErrors()
    editForm.title = item.title
    editForm.description = item.description ?? ''
    editForm.parent_id = item.parent_id
    isEditOpen.value = true
}

function submitEdit() {
    if (editingId.value === null) return

    editForm.put(route('categories.update', editingId.value), {
        onSuccess: () => {
            isEditOpen.value = false
            editingId.value = null
            editForm.reset()
        }
    })
}

function deleteCategory(id: number) {
    if (confirm("តើអ្នកនិងលុបប្រភេទ")) {
        router.delete(route('categories.destroy', id));
    }
}

function toggleSelectAll() {
    selectedIds.value = isAllSelected.value ? [] : props.categories.map((category) => category.id)
}

function deleteSelected() {
    if (selectedIds.value.length === 0 || !confirm(`Delete ${selectedIds.value.length} selected categories? Documents inside deleted categories will also be deleted.`)) return

    router.delete(route('categories.bulk-destroy'), {
        data: { ids: selectedIds.value },
        onSuccess: () => { selectedIds.value = [] },
    })
}

function handlePageChange(page: number) {
    router.get(route('categories.index'), {
        per_page: props.pagination.per_page,
        parent_ids: selectedParentIds.value,
        page: page
    }, { preserveState: true })
}

function handlePerPageChange(perPage: number) {
    router.get(route('categories.index'), {
        per_page: perPage,
        parent_ids: selectedParentIds.value,
        page: 1
    }, { preserveState: true })
}

function applyParentFilter() {
    router.get(route('categories.index'), {
        parent_ids: selectedParentIds.value,
        per_page: props.pagination.per_page,
        page: 1,
    }, { preserveState: true })
}

function clearParentFilter() {
    selectedParentIds.value = []
    applyParentFilter()
}
</script>

<template>
    <Head title="ប្រភេទ" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">

            <Dialog v-model:open="isCreateOpen">
                <DialogTrigger as-child>
                    <button
                        v-if="can('category.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 transition"
                    >
                        <Plus class="w-4 h-4" />
                        បង្កើត​ ប្រភេទថ្មី
                    </button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>បង្កើត​ ប្រភេទថ្មី</DialogTitle>
                        <DialogDescription>
                            បញ្ចូលពត៌មានប្រភេទថ្មី
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitCreate" class="space-y-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium">ឈ្មោះប្រភេទ</label>
                            <input
                                type="text"
                                v-model="createForm.title"
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                                placeholder="ដាក់ឈ្មោះប្រភេទ"
                            />
                            <p v-if="createForm.errors.title" class="text-red-500 text-sm mt-1">
                                {{ createForm.errors.title }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">រៀបរាប់</label>
                            <input
                                type="text"
                                v-model="createForm.description"
                                class="mt-1 block w-full border rounded-md border-gray-300 px-3 py-2"
                                placeholder="រៀបរាប់"
                            />
                            <p v-if="createForm.errors.description" class="text-red-500 text-sm mt-1">
                                {{ createForm.errors.description }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">ប្រភេទមេ</label>
                            <CategoryPicker
                                v-model="createForm.parent_id"
                                :categories="props.parents"
                                input-id="quick-create-category-parent"
                            />
                            <p class="mt-1 text-xs text-gray-500">ស្វែងរកប្រភេទមេ ឬទុកទទេដើម្បីបង្កើតប្រភេទមេថ្មី</p>
                            <p v-if="createForm.errors.parent_id" class="text-red-500 text-sm mt-1">
                                {{ createForm.errors.parent_id }}
                            </p>
                        </div>

                        <DialogFooter>
                            <DialogClose as-child>
                                <button
                                    type="button"
                                    class="px-3 py-2 text-xs font-medium border text-gray-700 bg-gray-200 rounded hover:bg-gray-300"
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

            <div class="relative mt-3 inline-block">
                <button type="button" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50" @click="isParentFilterOpen = !isParentFilterOpen">
                    <Filter class="h-4 w-4" /> Filter by parent category
                    <span v-if="selectedParentIds.length" class="rounded-full bg-blue-100 px-1.5 py-0.5 text-xs text-blue-700">{{ selectedParentIds.length }}</span>
                </button>
                <div v-if="isParentFilterOpen" class="absolute left-0 z-40 mt-2 w-80 rounded-lg border border-gray-200 bg-white p-3 shadow-lg">
                    <input v-model="parentFilterSearch" type="search" placeholder="Search parent categories" class="mb-3 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                    <div class="max-h-64 overflow-y-auto">
                        <label v-for="parent in filteredParents" :key="parent.id" class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-gray-50"><input v-model="selectedParentIds" type="checkbox" :value="parent.id" @change="applyParentFilter" /><span>{{ parent.title }}</span></label>
                        <p v-if="filteredParents.length === 0" class="px-2 py-3 text-sm text-gray-500">No categories found.</p>
                    </div>
                    <div class="mt-2 flex justify-between border-t border-gray-200 pt-2"><button type="button" class="text-sm text-blue-600 hover:underline" @click="clearParentFilter">Clear filter</button><button type="button" class="text-sm text-gray-600 hover:underline" @click="isParentFilterOpen = false">Close</button></div>
                </div>
            </div>

            <Dialog v-model:open="isEditOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>កែរ ប្រភេទ</DialogTitle>
                        <DialogDescription>
                            កែសម្រួលពត៌មានប្រភេទ
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitEdit" class="space-y-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium">ឈ្មោះ</label>
                            <input
                                type="text"
                                v-model="editForm.title"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                placeholder="Enter the name"
                            />
                            <p v-if="editForm.errors.title" class="text-red-500 text-sm mt-1">
                                {{ editForm.errors.title }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">រៀបរាប់</label>
                            <input
                                type="text"
                                v-model="editForm.description"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                placeholder="Enter description"
                            />
                            <p v-if="editForm.errors.description" class="text-red-500 text-sm mt-1">
                                {{ editForm.errors.description }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">ប្រភេទមេ</label>
                            <CategoryPicker
                                v-model="editForm.parent_id"
                                :categories="props.parents.filter((parent) => parent.id !== editingId)"
                                input-id="quick-update-category-parent"
                            />
                            <p class="mt-1 text-xs text-gray-500">ស្វែងរកប្រភេទមេ ឬលុបអក្សរដើម្បីដកប្រភេទមេ</p>
                            <p v-if="editForm.errors.parent_id" class="text-red-500 text-sm mt-1">
                                {{ editForm.errors.parent_id }}
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
                                កែរ ប្រភេទ
                            </button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <DataTable
                :data="props.categories"
                :pagination="props.pagination"
                :columns="['select', 'stt', 'title', 'parent_title', 'description', 'documents_count', 'actions']"
                class="mt-3"
                @page-change="handlePageChange"
                @per-page-change="handlePerPageChange"
            >
                <template #header-select>
                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" aria-label="Select all categories" />
                </template>
                <template #select="{ item }">
                    <input v-model="selectedIds" type="checkbox" :value="item.id" :aria-label="`Select ${item.title}`" />
                </template>
                <template #header-stt>លរ</template>
                <template #header-title>ឈ្មោះ</template>
                <template #header-parent_title>ប្រភេទមេ</template>
                <template #header-description>ការរៀបរាប់</template>
                <template #header-documents_count>ឯកសារ</template>
                <template #header-actions>សកម្មភាព</template>

                <template #stt="{ index }">
                    {{ (props.pagination.current_page - 1) * props.pagination.per_page + index + 1 }}
                </template>

                <template #title="{ item }">
                    <span v-if="item.parent_id" class="ml-4 text-gray-500">— {{ item.title }}</span>
                    <span v-else>{{ item.title }}</span>
                </template>

                <template #parent_title="{ item }">
                    {{ item.parent_title || '-' }}
                </template>

                <template #description="{ item }">
                    {{ item.description }}
                </template>

                <template #documents_count="{ item }">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ item.documents_count }} ឯកសារ
                    </span>
                </template>

                <template #actions="{ item }">
                    <div class="flex gap-2">
                        <Link :href="route('categories.show', item.id)"
                            class="cursor-pointer p-2 font-medium text-white bg-gray-700 rounded">
                            <Eye class="w-4 h-4" />
                        </Link>

                        <button
                            @click="openEditDialog(item)"
                            class="cursor-pointer p-2 font-medium text-white bg-blue-500 rounded"
                        >
                            <Pencil class="w-4 h-4" />
                        </button>

                        <button @click="deleteCategory(item.id)"
                            class="cursor-pointer p-2 font-medium text-white bg-red-500 rounded">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </DataTable>

            <div v-if="selectedIds.length && can('category.delete')" class="mt-3 flex items-center gap-3">
                <span class="text-sm text-gray-600">{{ selectedIds.length }} selected</span>
                <button @click="deleteSelected" class="inline-flex items-center gap-2 rounded bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">
                    <Trash2 class="w-4 h-4" /> Delete selected
                </button>
            </div>
        </div>
    </AppLayout>
</template>
