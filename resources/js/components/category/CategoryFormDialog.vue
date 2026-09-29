<script setup lang="ts">
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import Textarea from '@/components/ui/textarea/Textarea.vue'
import { X } from '@lucide/vue'

interface Category {
  id?: number
  title: string
  description?: string | null
}

const props = defineProps<{
  category?: Category | null
  open?: boolean
  mode?: 'create' | 'edit'
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  'success': [category: any]
  'close': []
}>()

const isOpen = computed({
  get: () => props.open ?? false,
  set: (value) => emit('update:open', value)
})

const form = useForm({
  title: props.category?.title || '',
  description: props.category?.description || '',
})

const isEditMode = computed(() => props.mode === 'edit')

const submit = () => {
  if (isEditMode.value && props.category?.id) {
    form.put(
      route('categories.update', props.category.id),
      {
        onSuccess: () => {
          isOpen.value = false
          form.reset()
        },
        onError: () => {
          // Errors are already handled by form
        }
      }
    )
  } else {
    form.post(
      route('categories.store'),
      {
        forceFormData: true,
        onSuccess: () => {
          isOpen.value = false
          form.reset()
        },
        onError: () => {
          // Errors are already handled by form
        }
      }
    )
  }
}

watch(() => props.category, (newCategory) => {
  if (newCategory) {
    form.title = newCategory.title || ''
    form.description = newCategory.description || ''
  } else {
    form.reset()
  }
}, { deep: true })

const closeDialog = () => {
  isOpen.value = false
  form.reset()
  form.clearErrors()
  emit('close')
}
</script>

<template>
  <Dialog :open="isOpen" @update:open="isOpen = $event">
    <DialogTrigger as-child>
      <Button type="button">{{ isEditMode ? 'Edit category' : 'Create category' }}</Button>
    </DialogTrigger>
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ isEditMode ? 'Edit category' : 'Create category' }}</DialogTitle>
        <DialogDescription>Enter the category details below.</DialogDescription>
      </DialogHeader>
      <form class="space-y-4" @submit.prevent="submit">
        <div class="space-y-2">
          <Label for="category-title">Title</Label>
          <Input id="category-title" v-model="form.title" />
          <p v-if="form.errors.title" class="text-sm text-red-600">{{ form.errors.title }}</p>
        </div>
        <div class="space-y-2">
          <Label for="category-description">Description</Label>
          <Textarea id="category-description" v-model="form.description" />
          <p v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</p>
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="closeDialog">
            <X class="mr-2 size-4" /> Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Saving...' : 'Save category' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>