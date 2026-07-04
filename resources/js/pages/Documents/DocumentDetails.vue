<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'បង្ហាញ ឯកសារ',
        href: '/documents',
    },
];

interface Document {
    id: number;
    doc_name: string;
    doc_title: string;
    doc_upload: string;
    image: string | null;
    description: string | null;
    category?: {
        id: number;
        title: string;
    };
}

const props = defineProps<{
    document: Document;
}>();
</script>

<template>

    <Head title="បង្ហាញ ឯកសារ" />


    <AppLayout :breadcrumbs="breadcrumbs">


        <div class="over-flow-x-auto p-3">

            <Link :href="route('documents.index')"
                class="cursor-pointer px-3 py-2 text-xs mb-3 font-medium text-white bg-blue-500 rounded">
                ត្រឡប់ ក្រោយ
            </Link>

            <div class="max-w-2xl space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-600">ឈ្មោះឯកសារ</p>
                    <p class="text-lg">{{ props.document.doc_name }}</p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-600">ចំណងជើង</p>
                    <p class="text-lg">{{ props.document.doc_title }}</p>
                </div>

                <div v-if="props.document.category">
                    <p class="text-sm font-medium text-gray-600">ប្រភេទ</p>
                    <p class="text-lg">{{ props.document.category.title }}</p>
                </div>

                <div v-if="props.document.image">
                    <p class="text-sm font-medium text-gray-600">រូបភាព</p>
                    <a :href="`/storage/${props.document.image}`" target="_blank">
                        <img
                            :src="`/storage/${props.document.image}`"
                            :alt="props.document.doc_name"
                            class="w-48 h-48 object-cover rounded cursor-pointer hover:opacity-80"
                        />
                    </a>
                </div>

                <div v-if="props.document.doc_upload">
                    <p class="text-sm font-medium text-gray-600">ឯកសារ</p>
                    <a
                        :href="`/storage/${props.document.doc_upload}`"
                        target="_blank"
                        class="text-blue-600 hover:underline"
                    >
                        View File
                    </a>
                </div>

                <div v-if="props.document.description">
                    <p class="text-sm font-medium text-gray-600">រៀបរាប់</p>
                    <p class="text-lg">{{ props.document.description }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
