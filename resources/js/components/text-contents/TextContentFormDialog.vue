<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
    DialogClose,
} from '@/components/ui/dialog'
import TextContentFormFields from '@/components/text-contents/TextContentFormFields.vue'
import { type Form } from '@inertiajs/vue3'

defineProps<{
    open: boolean
    title: string
    description: string
    submitLabel: string
    processing: boolean
    categories: { id: number; title: string; parent_id: number | null }[]
    isEdit?: boolean
}>()

const form = defineModel<Form<any>>('form', { required: true })

defineEmits<{
    (e: 'update:open', value: boolean): void
    (e: 'submit'): void
}>()
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-2xl max-h-[85vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <form @submit.prevent="$emit('submit')" class="space-y-4 mt-4">
                <TextContentFormFields
                    v-model:form="form"
                    :categories="categories"
                    :processing="processing"
                    :submit-label="submitLabel"
                    :is-edit="isEdit"
                />

                <DialogFooter>
                    <DialogClose as-child>
                        <button type="button" class="px-3 py-2 text-xs font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                            បោះបង់
                        </button>
                    </DialogClose>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="px-3 py-2 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 disabled:opacity-50"
                    >
                        {{ processing ? 'Saving...' : submitLabel }}
                    </button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
