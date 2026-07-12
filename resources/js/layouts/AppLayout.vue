<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import LoadingOverlay from '@/components/LoadingOverlay.vue';
import FlashMessage from '@/components/FlashMessage.vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <LoadingOverlay />
        <div class="mx-auto w-full max-w-6xl px-4 pt-4">
            <FlashMessage />
        </div>

        <slot />
    </AppLayout>
</template>
