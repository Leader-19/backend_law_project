<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Log Viewer',
        href: '/log-viewer',
    },
];
</script>

<template>
    <Head title="Log Viewer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <iframe
                src="/admin/log-viewer"
                class="h-[calc(100vh-8rem)] w-full rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
                frameborder="0"
            ></iframe>
        </div>
    </AppLayout>
</template>
