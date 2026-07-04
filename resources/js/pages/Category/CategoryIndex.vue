<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { Plus, Pencil, Trash2, Eye } from 'lucide-vue-next'
import DataTable from '@/components/ui/data-table/DataTable.vue'
import { can } from '@/lib/can'
import { type BreadcrumbItem } from '@/types'
import { ref } from 'vue'
import {
    Dialog,
    DialogTrigger,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogClose
} from '@/components/ui/dialog'

interface Category {
    id: number
    title: string
    description: string | null
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
    pagination: Pagination
}>()

const isCreateOpen = ref(false)
const isEditOpen = ref(false)
const editingId = ref<number | null>(null)

const createForm = useForm({
    title: '',
    description: '',
})

const editForm = useForm({
    title: '',
    description: '',
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

function handlePageChange(page: number) {
    router.get(route('categories.index'), {
        per_page: props.pagination.per_page,
        page: page
    }, { preserveState: true })
}

function handlePerPageChange(perPage: number) {
    router.get(route('categories.index'), {
        per_page: perPage,
        page: 1
    }, { preserveState: true })
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
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                                placeholder="រៀបរាប់"
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

            <Dialog v-model:open="isEditOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>កែរ ប្រភេទ</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="submitEdit" class="space-y-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium">ឈ្មោះ</label>
                            <input
                                type="text"
                                v-model="editForm.title"
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
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
                                class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2"
                                placeholder="Enter description"
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
                                កែរ ប្រភេទ
                            </button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <DataTable
                :data="props.categories"
                :pagination="props.pagination"
                :columns="['stt', 'title', 'description', 'documents_count', 'actions']"
                class="mt-3"
                @page-change="handlePageChange"
                @per-page-change="handlePerPageChange"
            >
                <template #header-stt>លរ</template>
                <template #header-title>ឈ្មោះ</template>
                <template #header-description>ការរៀបរាប់</template>
                <template #header-documents_count>ឯកសារ</template>
                <template #header-actions>សកម្មភាព</template>

                <template #stt="{ index }">
                    {{ (props.pagination.current_page - 1) * props.pagination.per_page + index + 1 }}
                </template>

                <template #title="{ item }">
                    {{ item.title }}
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
        </div>
    </AppLayout>
</template>
