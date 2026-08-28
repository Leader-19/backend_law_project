<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import DocumentFormFields from '@/components/documents/DocumentFormFields.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Update Document',
        href: route('documents.index'),
    },
];

const props = defineProps<{
    document: {
        id: number;
        doc_name: string;
        doc_title: string;
        description: string | null;
        doc_upload?: string | null;
        image?: string | null;
        category_id?: number | null;
    };
    categories: { id: number; title: string; children?: { id: number; title: string }[] }[];
}>();

const form = useForm({
    _method: 'PUT',
    doc_name: props.document.doc_name ?? '',
    doc_title: props.document.doc_title ?? '',
    description: props.document.description ?? '',
    doc_upload: null as File | null,
    image: null as File | null,
    category_id: props.document.category_id ?? null,
});

const submit = () => {
    form.post(route('documents.update', props.document.id), {
        forceFormData: true,
        onSuccess: () => console.log('Updated successfully'),
        onError: (err) => console.log('Validation errors:', err),
    });
};
</script>

<template>
    <Head title="Update Document" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl mx-auto p-4 sm:p-6">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <Link
                    :href="route('documents.index')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-600 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm"
                >
                    ← Back to Documents
                </Link>
            </div>

            <!-- Form Card -->
            <div class="rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <h1 class="text-lg font-bold text-slate-900 dark:text-white">Update Document</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Edit the document details below.</p>
                </div>

                <form @submit.prevent="submit" class="p-6">
                    <DocumentFormFields
                        :form="form"
                        :categories="categories"
                        :processing="form.processing"
                        submit-label="Update Document"
                        :is-edit="true"
                    />
                </form>
            </div>
        </div>
    </AppLayout>
</template>