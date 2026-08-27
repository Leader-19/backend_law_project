<script setup lang="ts">
import { SidebarProvider } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
    variant?: 'header' | 'sidebar';
}

defineProps<Props>();

const isOpen = usePage().props.sidebarOpen as boolean | undefined;

const localStorageKey = 'sidebar_state';
const savedState = localStorage.getItem(localStorageKey);
const defaultOpen = savedState !== null ? savedState === 'true' : (isOpen ?? true);

const isSidebarOpen = ref(defaultOpen);

watch(isSidebarOpen, (newValue) => {
    localStorage.setItem(localStorageKey, String(newValue));
});
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider v-else :default-open="isSidebarOpen">
        <slot />
    </SidebarProvider>
</template>
