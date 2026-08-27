<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
    DialogClose
} from '@/components/ui/dialog'
import { type Form } from '@inertiajs/vue3'

defineProps<{
    open: boolean
    form: Form<any>
    processing: boolean
}>()

defineEmits<{
    (e: 'update:open', value: boolean): void
    (e: 'submit'): void
}>()

function fieldClass(name: string) {
    return 'mt-1 block w-full rounded-md border border-gray-300 px-3 py-2'
}
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>កែសម្រួលឯកសារ</DialogTitle>
                <DialogDescription>កែសម្រួលពត៌មានឯកសារ</DialogDescription>
            </DialogHeader>
            <form @submit.prevent="$emit('submit')" class="space-y-4 mt-4">
                <div>
                    <label class="block text-sm font-medium">ឈ្មោះឯកសារ</label>
                    <input type="text" v-model="form.doc_name" :class="fieldClass('doc_name')" placeholder="Enter document name" />
                    <p v-if="form.errors.doc_name" class="text-red-500 text-sm mt-1">{{ form.errors.doc_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium">ចំណងជើង</label>
                    <input type="text" v-model="form.doc_title" :class="fieldClass('doc_title')" placeholder="Enter title" />
                    <p v-if="form.errors.doc_title" class="text-red-500 text-sm mt-1">{{ form.errors.doc_title }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium">ប្រភេទ</label>
                    <slot name="category" />
                    <p v-if="form.errors.category_id" class="text-red-500 text-sm mt-1">{{ form.errors.category_id }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium">Upload File (leave empty to keep current)</label>
                    <input type="file" @change="(e) => { const t = e.target as HTMLInputElement; if (t.files && t.files.length > 0) form.doc_upload = t.files[0] }" class="mt-1 block w-full text-sm border border-gray-300 rounded-md p-2" />
                    <p v-if="form.errors.doc_upload" class="text-red-500 text-sm mt-1">{{ form.errors.doc_upload }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium">Image (leave empty to keep current)</label>
                    <input type="file" @change="(e) => { const t = e.target as HTMLInputElement; if (t.files && t.files.length > 0) form.image = t.files[0] }" accept="image/*" class="mt-1 block w-full border text-sm border border-gray-300 rounded-md p-2" />
                    <p v-if="form.errors.image" class="text-red-500 text-sm mt-1">{{ form.errors.image }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium">រៀបរាប់</label>
                    <input type="text" v-model="form.description" :class="fieldClass('description')" placeholder="Optional description" />
                    <p v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</p>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <button type="button" class="px-3 py-2 text-xs font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300">បោះបង់</button>
                    </DialogClose>
                    <button type="submit" :disabled="processing" class="px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50">រក្សាទុក</button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
