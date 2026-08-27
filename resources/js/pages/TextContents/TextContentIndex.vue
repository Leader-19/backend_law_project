<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { Plus, Pencil, Trash2, FileText, AlertTriangle } from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'
import { computed, ref } from 'vue'
import { can } from '@/lib/can'
import DataTable from '@/components/ui/data-table/DataTable.vue'
import TextContentSearchBar from '@/components/text-contents/TextContentSearchBar.vue'

interface TextContent {
    id: number
    title: string
    body: string
    category_id: number
    category: { id: number; title: string } | null
    created_at: string
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

interface Quota {
    used: number
    limit: number
    remaining: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'អត្ថបទ', href: '/text-contents' },
]

const props = defineProps<{
    textContents: TextContent[]
    categories: Category[]
    pagination: Pagination
    search: string
    selectedCategoryId: number | null
    quota: Quota | null
}>()

const searchQuery = ref<string>(props.search || '')
const selectedCategoryId = ref<number | null>(props.selectedCategoryId || null)
const selectedIds = ref<number[]>([])

const isAllSelected = computed(() =>
    props.textContents.length > 0 &&
    props.textContents.every((item) => selectedIds.value.includes(item.id))
)

const quotaPercent = computed(() => {
    if (!props.quota) return 0
    return Math.round((props.quota.used / props.quota.limit) * 100)
})

const quotaColor = computed(() => {
    if (!props.quota) return 'bg-green-500'
    if (quotaPercent.value >= 90) return 'bg-red-500'
    if (quotaPercent.value >= 70) return 'bg-yellow-500'
    return 'bg-green-500'
})

const quotaExhausted = computed(() => {
    if (!props.quota) return false
    return props.quota.remaining <= 0
})

function toggleSelectAll() {
    selectedIds.value = isAllSelected.value
        ? []
        : props.textContents.map((item) => item.id)
}

function changePage(page: number) {
    if (page < 1 || page > props.pagination.last_page) return
    router.get(route('text-contents.index'), {
        per_page: props.pagination.per_page,
        page,
        search: searchQuery.value,
        category_id: selectedCategoryId.value,
    }, { preserveState: true })
}

function changeItemsPerPage(perPage: number) {
    router.get(route('text-contents.index'), {
        per_page: perPage,
        page: 1,
        search: searchQuery.value,
        category_id: selectedCategoryId.value,
    }, { preserveState: true })
}

function handleSearch() {
    router.get(route('text-contents.index'), {
        search: searchQuery.value,
        category_id: selectedCategoryId.value,
        per_page: props.pagination.per_page,
        page: 1,
    }, { preserveState: true })
}

function deleteItem(id: number) {
    if (confirm('Are you sure you want to delete this text content?')) {
        router.delete(route('text-contents.destroy', id))
    }
}

function deleteSelected() {
    if (selectedIds.value.length === 0 || !confirm(`Delete ${selectedIds.value.length} selected items?`)) return
    router.delete(route('text-contents.bulk-destroy'), {
        data: { ids: selectedIds.value },
        onSuccess: () => { selectedIds.value = [] },
    })
}

function truncateText(text: string, max: number = 80): string {
    if (!text) return ''
    const plain = text.replace(/<[^>]*>/g, '')
    return plain.length > max ? plain.substring(0, max) + '...' : plain
}
</script>

<template>
    <Head title="អត្ថបទ" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">

            <!-- Top Bar -->
            <div class="flex justify-between items-center mb-4">
                <div class="flex flex-wrap items-center gap-3">
                    <Link
                        v-if="can('text-contents.create') && !quotaExhausted"
                        :href="route('text-contents.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 transition"
                    >
                        <Plus class="w-4 h-4" />
                        បង្កើត​ អត្ថបទ
                    </Link>

                    <span
                        v-else-if="can('text-contents.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-gray-400 bg-gray-100 rounded-md cursor-not-allowed"
                    >
                        <Plus class="w-4 h-4" />
                        Quota Exhausted
                    </span>

                    <div v-if="selectedIds.length && can('text-contents.delete')" class="inline-flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-md">
                        <span class="text-sm text-red-700">{{ selectedIds.length }} selected</span>
                        <button @click="deleteSelected" class="inline-flex items-center gap-1 rounded bg-red-600 px-2 py-1 text-xs font-semibold text-white hover:bg-red-700">
                            <Trash2 class="w-3 h-3" /> Delete selected
                        </button>
                    </div>
                </div>

                <!-- Search -->
                <TextContentSearchBar
                    v-model:searchQuery="searchQuery"
                    v-model:selectedCategoryId="selectedCategoryId"
                    :categories="categories"
                    @search="handleSearch"
                />
            </div>

            <!-- Quota Banner -->
            <div v-if="quota" class="mb-4 p-3 rounded-lg border" :class="quotaPercent >= 90 ? 'bg-red-50 border-red-200' : quotaPercent >= 70 ? 'bg-yellow-50 border-yellow-200' : 'bg-blue-50 border-blue-200'">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <AlertTriangle v-if="quotaPercent >= 90" class="w-4 h-4 text-red-500" />
                        <span class="text-sm font-medium" :class="quotaPercent >= 90 ? 'text-red-700' : quotaPercent >= 70 ? 'text-yellow-700' : 'text-blue-700'">
                            Text Content Quota: {{ quota.used }} / {{ quota.limit }} used
                        </span>
                    </div>
                    <span class="text-xs" :class="quotaPercent >= 90 ? 'text-red-500' : quotaPercent >= 70 ? 'text-yellow-500' : 'text-blue-500'">
                        {{ quota.remaining }} remaining
                    </span>
                </div>
                <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                    <div :class="quotaColor" class="h-2 rounded-full transition-all" :style="{ width: quotaPercent + '%' }"></div>
                </div>
            </div>

            <!-- Data Table -->
            <DataTable
                :data="props.textContents"
                :pagination="props.pagination"
                :columns="['select', 'stt', 'title', 'category', 'body', 'created_at', 'actions']"
                class="mt-3"
                @page-change="changePage"
                @per-page-change="changeItemsPerPage"
            >
                <template #header-select>
                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" aria-label="Select all" />
                </template>
                <template #select="{ item }">
                    <input v-model="selectedIds" type="checkbox" :value="item.id" />
                </template>

                <template #header-stt>លរ</template>
                <template #header-title>ចំណងជើង</template>
                <template #header-category>ប្រភេទ</template>
                <template #header-body>អត្ថបទ</template>
                <template #header-created_at>ថ្ងៃបង្កើត</template>
                <template #header-actions>សកម្មភាព</template>

                <template #stt="{ index }">
                    {{ (props.pagination.current_page - 1) * props.pagination.per_page + index + 1 }}
                </template>

                <template #category="{ item }">
                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">
                        {{ item.category?.title || '-' }}
                    </span>
                </template>

                <template #body="{ item }">
                    <span class="text-xs text-gray-600" :title="item.body.replace(/<[^>]*>/g, '')">
                        {{ truncateText(item.body) }}
                    </span>
                </template>

                <template #created_at="{ item }">
                    <span class="text-xs text-gray-500">
                        {{ new Date(item.created_at).toLocaleDateString() }}
                    </span>
                </template>

                <template #actions="{ item }">
                    <div class="flex justify-center gap-2">
                        <Link
                            :href="route('text-contents.show', item.id)"
                            class="p-2 bg-gray-800 rounded hover:bg-gray-700"
                        >
                            <FileText class="w-4 h-4 text-white" />
                        </Link>
                        <Link
                            v-if="can('text-contents.edit')"
                            :href="route('text-contents.edit', item.id)"
                            class="p-2 bg-blue-500 rounded hover:bg-blue-900"
                        >
                            <Pencil class="w-4 h-4 text-white" />
                        </Link>
                        <button
                            v-if="can('text-contents.delete')"
                            @click="deleteItem(item.id)"
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
