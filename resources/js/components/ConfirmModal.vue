<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { AlertTriangle } from '@lucide/vue';

interface Props {
    open: boolean;
    title?: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    processing?: boolean;
    variant?: 'danger' | 'warning' | 'info';
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Are you sure?',
    description: 'This action cannot be undone.',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    processing: false,
    variant: 'danger',
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm'): void;
}>();

const variantClasses = {
    danger: {
        icon: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
        button: 'bg-red-600 hover:bg-red-700 text-white',
    },
    warning: {
        icon: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
        button: 'bg-amber-600 hover:bg-amber-700 text-white',
    },
    info: {
        icon: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
        button: 'bg-blue-600 hover:bg-blue-700 text-white',
    },
};

function close() {
    emit('update:open', false);
}

function confirm() {
    emit('confirm');
}
</script>

<template>
    <Dialog :open="open" @update:open="close">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <div :class="['p-2 rounded-lg', variantClasses[variant].icon]">
                        <AlertTriangle class="h-5 w-5" />
                    </div>
                    <DialogTitle>{{ title }}</DialogTitle>
                </div>
                <DialogDescription class="mt-2">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2 sm:gap-0">
                <button
                    type="button"
                    @click="close"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                >
                    {{ cancelLabel }}
                </button>
                <button
                    type="button"
                    @click="confirm"
                    :disabled="processing"
                    :class="['px-4 py-2 text-sm font-medium rounded-lg disabled:opacity-50 transition-colors', variantClasses[variant].button]"
                >
                    {{ processing ? 'Processing...' : confirmLabel }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
