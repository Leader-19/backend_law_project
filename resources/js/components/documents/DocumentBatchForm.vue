<script setup lang="ts">
import type { Form } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import ZipDropZone from '../../components/ZipDropZone.vue';
import UploadCloud from '@/Components/Icons/UploadCloud.vue'; // adjust path if needed

interface UploadedFile {
    uuid: string;
    name: string;
    size: number;
    maxSize?: number;
}

interface BatchErrors {
    [key: string]: string | null;
}

interface FileErrors {
    [key: string]: string | null;
}

const page = usePage();
const currentUser = page.props.auth?.user as any;

// Main form
const form = reactive({
    title: '',
    description: '',
    shared_description: '',
    category_id: '' as string | number,
    status: 'draft',
    visibility: 'public',
    files: [] as UploadedFile[],
    zip_file: null as File | null,
    zip_title: '',
    zip_description: '',
    errors: {} as Record<string, string>,
});

// Separate zip form
const zipForm = reactive({
    zip_file: null as File | null,
    zip_title: '',
    zip_description: '',
    errors: {} as Record<string, string>,
});

const selectedFiles = reactive<
    Record<string, { file: UploadedFile; error: string | null }>
>({});

const fileErrors = reactive<Record<string, string | null>>({});
const fileErrors2 = reactive<Record<string, string | null>>({});

const errors = reactive<BatchErrors>({});

const activeTab = reactive({ value: 'files' as 'files' | 'zip' });

const isSubmitDisabled = computed(() => {
    if (activeTab.value === 'files') {
        return (
            Object.keys(selectedFiles).length === 0 ||
            Object.values(fileErrors).some(Boolean)
        );
    }
    if (activeTab.value === 'zip') {
        return !zipForm.zip_file || Object.values(zipForm.errors).some(Boolean);
    }
    return true;
});

watch(
    () => currentUser?.status ?? 'inactive',
    () => {
        if (activeTab.value === 'zip' && !documentPolicyCanEdit()) {
            activeTab.value = 'files';
        }
    },
);

function documentPolicyCanEdit(): boolean {
    if (!currentUser) return true;
    const permissions = (currentUser.permissions ?? '').toLowerCase();
    return (
        permissions.includes('document.create') ||
        permissions.includes('document.edit')
    );
}

function handleFileSelect(
    file: UploadedFile,
    event: Event,
    errorsObj: Record<string, string | null>,
) {
    const target = event.target as HTMLInputElement;
    const fileError =
        typeof file.maxSize !== 'undefined' && file.size > file.maxSize
            ? 'Max file size: ' + (file.maxSize / 1024 / 1024).toFixed(2) + 'MB'
            : null;

    errorsObj[file.uuid] = fileError;
    fileErrors2[file.uuid] = fileError;
}

function handleRemoveFile(uuid: string) {
    delete selectedFiles[uuid];
    delete fileErrors[uuid];

    if (activeTab.value === 'zip') {
        zipForm.zip_file = null;
        delete zipForm.errors.zip_file;
    }
}

// Temporary placeholder – replace with real data from props
const categoryTree = [
    // { id: 1, path: 'Category 1' },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Title -->
        <div>
            <label
                for="batch-title"
                class="block text-sm font-semibold text-slate-800 dark:text-slate-100"
            >
                Material Title <span class="text-red-500">*</span>
            </label>
            <input
                id="batch-title"
                v-model="form.title"
                type="text"
                required
                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
            />
            <p
                v-if="form.errors.title"
                class="mt-1 text-xs text-red-600 dark:text-red-400"
            >
                {{ form.errors.title }}
            </p>
        </div>

        <!-- Description + Category -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label
                    for="batch-description"
                    class="block text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
                    Description <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="batch-description"
                    v-model="form.description"
                    rows="3"
                    required
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                ></textarea>
                <p
                    v-if="form.errors.description"
                    class="mt-1 text-xs text-red-600 dark:text-red-400"
                >
                    {{ form.errors.description }}
                </p>
            </div>

            <div>
                <label
                    for="batch-category"
                    class="block text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
                    Category <span class="text-red-500">*</span>
                </label>
                <select
                    id="batch-category"
                    v-model="form.category_id"
                    required
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                >
                    <option value="">Select...</option>
                    <option
                        v-for="cat in categoryTree"
                        :key="cat.id"
                        :value="cat.id"
                    >
                        {{ cat.path }}
                    </option>
                </select>
                <p
                    v-if="form.errors.category_id"
                    class="mt-1 text-xs text-red-600 dark:text-red-400"
                >
                    {{ form.errors.category_id }}
                </p>
            </div>
        </div>

        <!-- Status + Visibility -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label
                    for="batch-status"
                    class="block text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
                    Publication Status
                </label>
                <select
                    id="batch-status"
                    v-model="form.status"
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                >
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>

            <div>
                <label
                    for="batch-visibility"
                    class="block text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
                    Visibility
                </label>
                <select
                    id="batch-visibility"
                    v-model="form.visibility"
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                >
                    <option value="public">Public</option>
                    <option value="private">Private</option>
                </select>
            </div>
        </div>

        <!-- Batch Options Tabs -->
        <div
            class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40"
        >
            <div class="flex items-center justify-between">
                <h3
                    class="text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
                    Batch Options
                </h3>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    Choose how to add documents
                </span>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    :class="{
                        'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-500 dark:bg-blue-900/30 dark:text-blue-200':
                            activeTab.value === 'files',
                    }"
                    @click="activeTab.value = 'files'"
                >
                    ✏️ Upload files individually
                </button>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    :class="{
                        'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-500 dark:bg-blue-900/30 dark:text-blue-200':
                            activeTab.value === 'zip',
                    }"
                    @click="activeTab.value = 'zip'"
                >
                    Import from ZIP
                </button>
            </div>
        </div>

        <!-- ==================== FILES TAB ==================== -->
        <div v-if="activeTab.value === 'files'">
            <div class="mt-2 flex flex-wrap gap-2">
                <label
                    v-for="file in form.files"
                    :key="file.uuid"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    @click="handleRemoveFile(file.uuid)"
                >
                    <span class="truncate">{{ file.name }}</span>
                    <span class="text-xs text-slate-400">
                        ({{ (file.size / 1024 / 1024).toFixed(2) }} MB)
                    </span>
                </label>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <label
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                    <!-- <UploadCloud /> -->
                    Add more files
                    <!-- You can put a real <input type="file" multiple hidden> here -->
                </label>
            </div>
        </div>

        <!-- ==================== ZIP TAB ==================== -->
        <div v-else-if="activeTab.value === 'zip'">
            <ZipDropZone
                v-model="zipForm.zip_file"
                :error="zipForm.errors.zip_file"
            />

            <div class="mt-3 space-y-3">
                <div>
                    <label
                        for="zip-title"
                        class="block text-sm font-semibold text-slate-800 dark:text-slate-100"
                    >
                        ZIP Title
                    </label>
                    <input
                        id="zip-title"
                        v-model="zipForm.zip_title"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    />
                </div>

                <div>
                    <label
                        for="zip-description"
                        class="block text-sm font-semibold text-slate-800 dark:text-slate-100"
                    >
                        ZIP Description
                    </label>
                    <textarea
                        id="zip-description"
                        v-model="zipForm.zip_description"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    ></textarea>
                    <p
                        v-if="zipForm.errors.zip_description"
                        class="mt-1 text-xs text-red-600 dark:text-red-400"
                    >
                        {{ zipForm.errors.zip_description }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>