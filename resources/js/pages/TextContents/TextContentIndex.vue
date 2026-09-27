<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref, computed } from 'vue'
import type { BreadcrumbItem } from '@/types'

interface Category {
    id: number
    title: string
}

interface TextContent {
    id: number
    title: string
    body: string
    category_id: number | null
    category?: Category | null
    created_at: string
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'អត្ថបទ', href: route('text-contents.index') },
]

const page = usePage()
const textContents = page.props.textContents as TextContent[]
const categories = page.props.categories as Category[]

// Filters (Excel-like)
const search = ref('')
const categoryFilter = ref<string | number>('')
const dateFrom = ref('')
const dateTo = ref('')

const filteredContents = computed(() => {
    return textContents.filter((item) => {
        // Search (title + body)
        const searchLower = search.value.toLowerCase().trim()
        const matchesSearch =
            !searchLower ||
            item.title.toLowerCase().includes(searchLower) ||
            (item.body && item.body.toLowerCase().includes(searchLower))

        // Category
        const matchesCategory =
            !categoryFilter.value || item.category_id === Number(categoryFilter.value)

        // Date range
        const created = new Date(item.created_at)
        const matchesFrom = !dateFrom.value || created >= new Date(dateFrom.value)
        const matchesTo = !dateTo.value || created <= new Date(dateTo.value + 'T23:59:59')

        return matchesSearch && matchesCategory && matchesFrom && matchesTo
    })
})

const clearFilters = () => {
    search.value = ''
    categoryFilter.value = ''
    dateFrom.value = ''
    dateTo.value = ''
}

const hasActiveFilters = computed(() => {
    return !!(search.value || categoryFilter.value || dateFrom.value || dateTo.value)
})

const deleteItem = (id: number) => {
    if (confirm('តើអ្នកពិតជាចង់លុបអត្ថបទនេះមែនទេ?')) {
        router.delete(route('text-contents.destroy', id))
    }
}

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('km-KH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}
</script>

<template>
    <Head title="បញ្ជីអត្ថបទ" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-7xl mx-auto p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">អត្ថបទ</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        សរុប {{ filteredContents.length }} / {{ textContents.length }} អត្ថបទ
                    </p>
                </div>
                <Link
                    :href="route('text-contents.create')"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700"
                >
                    + បង្កើតអត្ថបទថ្មី
                </Link>
            </div>

            <!-- Excel-like Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Search -->
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">ស្វែងរក</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="ស្វែងរកតាមចំណងជើង ឬ អត្ថបទ..."
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">ប្រភេទ</label>
                        <select
                            v-model="categoryFilter"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">ទាំងអស់</option>
                            <option
                                v-for="cat in categories"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.title }}
                            </option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">ពីថ្ងៃ</label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">ដល់ថ្ងៃ</label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <div v-if="hasActiveFilters" class="mt-3 flex justify-end">
                    <button
                        @click="clearFilters"
                        class="text-sm text-red-600 hover:text-red-700 font-medium"
                    >
                        សម្អាតតម្រងទាំងអស់
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    ចំណងជើង
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    ប្រភេទ
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    ថ្ងៃបង្កើត
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    សកម្មភាព
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr
                                v-for="item in filteredContents"
                                :key="item.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-4 py-3">
                                    <Link
                                        :href="route('text-contents.show', item.id)"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                    >
                                        {{ item.title }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="item.category"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700"
                                    >
                                        {{ item.category.title }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ formatDate(item.created_at) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm space-x-2">
                                    <Link
                                        :href="route('text-contents.show', item.id)"
                                        class="text-gray-600 hover:text-gray-900"
                                    >
                                        មើល
                                    </Link>
                                    <Link
                                        :href="route('text-contents.edit', item.id)"
                                        class="text-blue-600 hover:text-blue-800"
                                    >
                                        កែ
                                    </Link>
                                    <button
                                        @click="deleteItem(item.id)"
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        លុប
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="filteredContents.length === 0">
                                <td colspan="4" class="px-4 py-12 text-center text-gray-500">
                                    <p class="text-sm">មិនមានអត្ថបទណាមួយត្រូវនឹងតម្រងនេះទេ</p>
                                    <button
                                        v-if="hasActiveFilters"
                                        @click="clearFilters"
                                        class="mt-2 text-sm text-blue-600 hover:underline"
                                    >
                                        សម្អាតតម្រង
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
