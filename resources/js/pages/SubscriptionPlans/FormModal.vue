<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Check, Plus, Search, X } from 'lucide-vue-next'

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
    return props.categories.filter(c => c.title.toLowerCase().includes(query))
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

function selectAllCategories() {
    selectedCategoryIds.value = props.categories.map(c => c.id)
}

function deselectAllCategories() {
    selectedCategoryIds.value = []
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
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4 backdrop-blur-sm">
        <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                    {{ props.plan?.id ? 'Edit subscription plan' : 'Create subscription plan' }}
                </h2>
                <button type="button" @click="emit('update:open', false)" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <X class="h-5 w-5" />
                </button>
            </div>

            <form @submit.prevent="submit" class="mt-4 max-h-[70vh] space-y-5 overflow-y-auto pr-1">
                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Plan name</label>
                    <input v-model="form.name" type="text" required placeholder="e.g. VIP Plan"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-white dark:focus:ring-white" />
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Description</label>
                    <textarea v-model="form.description" rows="2" placeholder="Brief plan description"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-white dark:focus:ring-white"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Price</label>
                        <input v-model.number="form.price" type="number" step="0.01" min="0" required
                            class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-white dark:focus:ring-white" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Currency</label>
                        <select v-model="form.currency"
                            class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <option v-for="c in currencies" :key="c.code" :value="c.code">{{ c.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Duration (days)</label>
                        <input v-model.number="form.duration_days" type="number" placeholder="Leave empty for lifetime"
                            class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-white dark:focus:ring-white" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Max categories</label>
                        <input v-model.number="form.max_categories" type="number" placeholder="Unlimited if empty"
                            class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-white dark:focus:ring-white" />
                    </div>
                </div>

                <!-- Features -->
                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Plan features</label>
                    <div class="mt-1.5 flex gap-2">
                        <input v-model="featureInput" type="text" placeholder="Add a feature, e.g. Priority support" @keydown.enter.prevent="addFeature"
                            class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-white dark:focus:ring-white" />
                        <button type="button" @click="addFeature"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            <Plus class="h-3.5 w-3.5" /> Add
                        </button>
                    </div>
                    <div v-if="featuresList.length" class="mt-2 flex flex-wrap gap-1.5">
                        <span v-for="(feat, idx) in featuresList" :key="idx"
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 py-1 pl-2.5 pr-1.5 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ feat }}
                            <button type="button" @click="removeFeature(idx)" class="text-slate-400 hover:text-red-500">
                                <X class="h-3 w-3" />
                            </button>
                        </span>
                    </div>
                </div>

                <!-- Category assignment -->
                <div>
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Assign categories</label>
                        <div class="flex items-center gap-2 text-xs">
                            <button type="button" @click="selectAllCategories" class="font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">Select all</button>
                            <span class="text-slate-300 dark:text-slate-600">|</span>
                            <button type="button" @click="deselectAllCategories" class="font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">Deselect all</button>
                        </div>
                    </div>
                    <p class="mt-0.5 text-xs text-slate-400">Users on this plan can access documents in the categories you select.</p>

                    <div class="relative mt-2 mb-2">
                        <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <input v-model="categorySearch" type="text" placeholder="Search categories"
                            class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-9 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-white dark:focus:ring-white" />
                        <button v-if="categorySearch" @click="categorySearch = ''" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="max-h-48 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2 dark:border-slate-700">
                        <label v-for="cat in filteredCategories" :key="cat.id"
                            class="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-800">
                            <input type="checkbox" :value="cat.id" v-model="selectedCategoryIds" class="sr-only" />
                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border"
                                :class="selectedCategoryIds.includes(cat.id) ? 'border-slate-900 bg-slate-900 dark:border-white dark:bg-white' : 'border-slate-300 dark:border-slate-600'">
                                <Check v-if="selectedCategoryIds.includes(cat.id)" class="h-3 w-3 text-white dark:text-slate-900" />
                            </span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ cat.title }}</span>
                            <span v-if="cat.parent_id" class="text-xs text-slate-400">— sub</span>
                        </label>
                        <p v-if="!filteredCategories.length" class="py-2 text-center text-xs italic text-slate-400">No categories match your search.</p>
                    </div>
                    <p v-if="selectedCategoryIds.length" class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ selectedCategoryIds.length }} {{ selectedCategoryIds.length === 1 ? 'category' : 'categories' }} selected
                    </p>
                </div>

                <label class="flex cursor-pointer items-center gap-2.5 pt-1">
                    <input v-model="form.is_active" type="checkbox" class="sr-only" />
                    <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border"
                        :class="form.is_active ? 'border-slate-900 bg-slate-900 dark:border-white dark:bg-white' : 'border-slate-300 dark:border-slate-600'">
                        <Check v-if="form.is_active" class="h-3 w-3 text-white dark:text-slate-900" />
                    </span>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Active (visible to users)</span>
                </label>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <button type="button" @click="emit('update:open', false)"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                        {{ props.plan?.id ? 'Update plan' : 'Create plan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
