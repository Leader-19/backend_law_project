<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { CheckCircle2, XCircle } from 'lucide-vue-next'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { AppPageProps } from '@/types'

const page = usePage<AppPageProps>()

const successMessage = computed(() => page.props.flash?.success ?? null)
const errorMessage = computed(() => page.props.flash?.error ?? null)

const visibleSuccess = ref<string | null>(null)
const visibleError = ref<string | null>(null)
let hideTimer: number | undefined

watch([successMessage, errorMessage], ([success, error]) => {
    window.clearTimeout(hideTimer)

    visibleSuccess.value = success
    visibleError.value = error

    if (success || error) {
        hideTimer = window.setTimeout(() => {
            visibleSuccess.value = null
            visibleError.value = null
        }, 4000)
    }
}, {
    immediate: true,
})

onBeforeUnmount(() => {
    window.clearTimeout(hideTimer)
})
</script>

<template>
    <div class="space-y-3">
        <Alert
            v-if="visibleSuccess"
            class="border-emerald-200 bg-emerald-50 text-emerald-900"
        >
            <CheckCircle2 class="size-4 text-emerald-600" />
            <AlertTitle>Success</AlertTitle>
            <AlertDescription>
                {{ visibleSuccess }}
            </AlertDescription>
        </Alert>

        <Alert
            v-if="visibleError"
            variant="destructive"
        >
            <XCircle class="size-4" />
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>
                {{ visibleError }}
            </AlertDescription>
        </Alert>
    </div>
</template>
