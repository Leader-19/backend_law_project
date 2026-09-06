<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Plus, X, Search } from 'lucide-vue-next'

interface Plan {
    id?: number
    name: string
    description: string | null
    price: number
    currency: string
    duration_days: number | null
    features: string[] | null
    max_categories: number | null
    max_documents: number | null
    max_storage_mb: number | null
    is_active: boolean
}

const props = defineProps<{
    open: boolean
    plan?: Plan | null
    currencies: Array<{ code: string; symbol: string; name: string }>
    categories: Array<{ id: number; title: string; parent_id: number | null }>
}>()

const emit = defineEmits(['update:open', 'saved'])

const featureInput = ref('')
const featuresList = ref<string[]>([])

const selectedCategoryIds = ref<number[]>([])
const categorySearch = ref('')

const filteredCategories = computed(() => {
    if (!categorySearch.value.trim()) return props.categories
    const query = categorySearch.value.toLowerCase()
    return props.categories.filter(c =>
        c.title.toLowerCase().includes(query)
    )
})

const form = useForm({
    name: '',
    description: '',
    price: 0,
    currency: 'USD',
    duration_days: 30 as number | null,
    features: [] as string[],
    max_categories: null as number | null,
    max_documents: null as number | null,
    max_storage_mb: null as number | null,
    is_active: true,
    category_ids: [] as number[],
})

watch(() => props.plan, (newPlan) => {
    if (newPlan) {
        form.name = newPlan.name
        form.description = newPlan.description || ''
        form.price = newPlan.price
        form.currency = newPlan.currency || 'USD'
        form.duration_days = newPlan.duration_days
        featuresList.value = newPlan.features ? [...newPlan.features] : []
        form.max_categories = newPlan.max_categories
        form.max_documents = newPlan.max_documents
        form.max_storage_mb = newPlan.max_storage_mb
        form.is_active = newPlan.is_active
        selectedCategoryIds.value = (newPlan as any).categories ? (newPlan as any).categories.map((c: any) => c.id) : []
    } else {
        form.reset()
        form.currency = 'USD'
        featuresList.value = []
        selectedCategoryIds.value = []
    }
    categorySearch.value = ''
}, { immediate: true })

function addFeature() {
    const trimmed = featureInput.value.trim()
    if (trimmed && !featuresList.value.includes(trimmed)) {
        featuresList.value.push(trimmed)
        featureInput.value = ''
    }
}

function removeFeature(index: number) {
    featuresList.value.splice(index, 1)
}

function submit() {
    form.features = featuresList.value
    form.category_ids = selectedCategoryIds.value
    if (props.plan?.id) {
        form.put(`/subscription-plans/${props.plan.id}`, {
            onSuccess: () => {
                emit('update:open', false)
                emit('saved')
            },
        })
    } else {
        form.post('/subscription-plans', {
            onSuccess: () => {
                emit('update:open', false)
                emit('saved')
            },
        })
    }
}
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                    {{ props.plan?.id ? 'Edit Subscription Plan' : 'Create New Subscription Plan' }}
                </h2>
                <button type="button" @click="emit('update:open', false)" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <X class="h-5 w-5" />
                </button>
            </div>

            <form @submit.prevent="submit" class="mt-4 space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">Plan Name</label>
                    <input v-model="form.name" type="text" required placeholder="e.g. VIP Plan" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">Description</label>
                    <textarea v-model="form.description" rows="2" placeholder="Brief plan description" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">Price</label>
                        <input v-model.number="form.price" type="number" step="0.01" min="0" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">Currency Code</label>
                        <select v-model="form.currency" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                            <option v-for="c in currencies" :key="c.code" :value="c.code">{{ c.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">Duration (Days)</label>
                        <input v-model.number="form.duration_days" type="number" placeholder="Leave empty for lifetime" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">Max Categories</label>
                        <input v-model.number="form.max_categories" type="number" placeholder="Unlimited if empty" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">Plan Features</label>
                    <div class="mt-1 flex gap-2">
                        <input v-model="featureInput" type="text" placeholder="Add a feature (e.g. Priority Support)" @keydown.enter.prevent="addFeature" class="flex-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                        <button type="button" @click="addFeature" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                            Add
                        </button>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1">
                        <span v-for="(feat, idx) in featuresList" :key="idx" class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                            {{ feat }}
                            <button type="button" @click="removeFeature(idx)" class="text-blue-500 hover:text-blue-700 ml-1">×</button>
                        </span>
                    </div>
                </div>

                <!-- Category Assignment -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">Assign Categories</label>
                    <p class="mt-1 text-xs text-slate-400">Select which categories users on this plan can access.</p>
                    <div class="mt-2">
                        <div class="relative mb-2">
                            <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                            <input
                                v-model="categorySearch"
                                type="text"
                                placeholder="Search categories..."
                                class="w-full rounded-lg border border-slate-300 pl-9 pr-9 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                            <button
                                v-if="categorySearch"
                                @click="categorySearch = ''"
                                class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="max-h-48 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-700 p-3 space-y-1">
                            <label v-for="cat in filteredCategories" :key="cat.id"
                                class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer transition-colors">
                                <input type="checkbox" :value="cat.id" v-model="selectedCategoryIds"
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ cat.title }}</span>
                                <span v-if="cat.parent_id" class="text-xs text-slate-400">— sub</span>
                            </label>
                            <p v-if="!filteredCategories.length" class="text-xs text-slate-400 italic py-2">No categories match your search.</p>
                        </div>
                    </div>
                    <p v-if="selectedCategoryIds.length > 0" class="mt-1 text-xs text-slate-500">
                        {{ selectedCategoryIds.length }} {{ selectedCategoryIds.length === 1 ? 'category' : 'categories' }} selected
                    </p>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                    <label for="is_active" class="text-sm font-medium text-slate-700 dark:text-slate-300">Active (Visible to users)</label>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="emit('update:open', false)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                        {{ props.plan?.id ? 'Update Plan' : 'Create Plan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
