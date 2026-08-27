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
        <div class="p-3 max-w-lg mx-auto">
            <Link :href="route('documents.index')" class="px-3 py-2 text-xs text-white bg-blue-500 rounded">
                Back
            </Link>

            <form @submit.prevent="submit" class="space-y-5 mt-4">
                <DocumentFormFields
                    :form="form"
                    :categories="categories"
                    :processing="form.processing"
                    submit-label="Update Document"
                    :is-edit="true"
                />
            </form>
        </div>
    </AppLayout>
</template>