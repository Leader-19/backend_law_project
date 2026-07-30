<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Download } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';

interface Category {
    id: number;
    title: string;
    description: string | null;
    children?: {
        id: number;
        title: string;
    }[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Backup',
        href: '/backup',
    },
];

const props = defineProps<{
    categories: Category[];
}>();

const selectedCategories = ref<number[]>([]);
const isDownloading = ref(false);
const downloadUrl = ref<string | null>(null);

const backupForm = useForm({
    category_ids: [] as number[],
});

function toggleCategory(categoryId: number) {
    const index = selectedCategories.value.indexOf(categoryId);
    if (index > -1) {
        selectedCategories.value.splice(index, 1);
    } else {
        selectedCategories.value.push(categoryId);
    }
}

function toggleAllCategories() {
    const allIds = props.categories.flatMap(c => {
        const childIds = c.children?.map(sc => sc.id) ?? [];
        return [c.id, ...childIds];
    });

    if (selectedCategories.value.length === allIds.length) {
        selectedCategories.value = [];
    } else {
        selectedCategories.value = allIds;
    }
}

function isAllSelected(): boolean {
    const allIds = props.categories.flatMap(c => {
        const childIds = c.children?.map(sc => sc.id) ?? [];
        return [c.id, ...childIds];
    });
    return allIds.length > 0 && selectedCategories.value.length === allIds.length;
}

function hasSelected(): boolean {
    return selectedCategories.value.length > 0;
}

function submitBackup() {
    isDownloading.value = true;
    downloadUrl.value = null;

    backupForm.category_ids = selectedCategories.value;

    backupForm.post(route('backup.create'), {
        forceFormData: true,
        onSuccess: (response) => {
            isDownloading.value = false;
        },
        onError: (errors) => {
            isDownloading.value = false;
        },
        onFinish: () => {
            isDownloading.value = false;
        },
    });
}
</script>

<template>
    <Head title="Backup" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Backup Data</h2>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold mb-3">Select Categories</h3>

                <div class="mb-3">
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input
                            type="checkbox"
                            :checked="isAllSelected()"
                            @change="toggleAllCategories"
                            class="rounded border-gray-300"
                        />
                        Select All
                    </label>
                </div>

                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <div v-for="cat in props.categories" :key="cat.id" class="space-y-1">
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input
                                type="checkbox"
                                :value="cat.id"
                                :checked="selectedCategories.includes(cat.id)"
                                @change="toggleCategory(cat.id)"
                                class="rounded border-gray-300"
                            />
                            {{ cat.title }}
                        </label>

                        <div v-if="cat.children && cat.children.length" class="ml-6 space-y-1">
                            <label
                                v-for="sub in cat.children"
                                :key="sub.id"
                                class="flex items-center gap-2 text-sm cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :value="sub.id"
                                    :checked="selectedCategories.includes(sub.id)"
                                    @change="toggleCategory(sub.id)"
                                    class="rounded border-gray-300"
                                />
                                — {{ sub.title }}
                            </label>
                        </div>
                    </div>
                </div>

                <div v-if="props.categories.length === 0" class="text-center py-4 text-gray-400 text-sm">
                    No categories found.
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button
                        type="button"
                        @click="submitBackup"
                        :disabled="backupForm.processing || !hasSelected()"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 transition disabled:opacity-50"
                    >
                        <Download class="w-4 h-4" />
                        {{ backupForm.processing ? 'Generating...' : 'Generate Backup' }}
                    </button>

                    <span v-if="!hasSelected()" class="text-xs text-gray-400">
                        Select at least one category to backup
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>