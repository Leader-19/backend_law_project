<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps<{ plan: { id: number; name: string; price: number | string; currency: string }; qr_code_url: string | null }>()
const step = ref(1); const receipt = ref<File | null>(null); const reference = ref(''); const preview = ref(''); const processing = ref(false)
const amount = computed(() => new Intl.NumberFormat(undefined, { style: 'currency', currency: props.plan.currency }).format(Number(props.plan.price)))
function choose(event: Event) { const file = (event.target as HTMLInputElement).files?.[0]; if (!file) return; receipt.value = file; preview.value = URL.createObjectURL(file); step.value = 3 }
function submit() { if (!receipt.value) return; processing.value = true; router.post('/payment/receipt', { plan_id: props.plan.id, receipt: receipt.value, reference: reference.value }, { forceFormData: true, onFinish: () => processing.value = false }) }
</script>
<template>

    <Head title="Pay by QR" />
    <AppLayout :breadcrumbs="[{ title: 'Plans', href: '/pricing' }, { title: 'QR payment', href: '/payment/receipt' }]">
        <main class="mx-auto max-w-xl p-6">
            <h1 class="text-2xl font-bold">{{ plan.name }} · {{ amount }}</h1>
            <p class="mt-2 text-sm text-slate-500">1. Scan QR 2. Upload receipt 3. Review and send</p>
            <section class="mt-6 rounded-xl border p-5"><img v-if="qr_code_url" :src="qr_code_url"
                    class="mx-auto max-h-64" alt="Payment QR code">
                <p v-else class="text-center text-amber-600">Payment QR code has not been configured.</p><label
                    class="mt-5 block text-sm font-medium">Payment reference (optional)<input v-model="reference"
                        class="mt-1 w-full rounded border p-2" maxlength="100"></label><label
                    class="mt-4 block rounded border border-dashed p-4 text-center">Upload receipt<input
                        class="mt-2 block w-full text-sm" type="file" accept="image/png,image/jpeg,image/webp"
                        @change="choose"></label><img v-if="preview" :src="preview" class="mt-4 max-h-64 rounded"
                    alt="Receipt preview"><button :disabled="!receipt || processing" @click="submit"
                    class="mt-5 w-full rounded bg-indigo-600 px-4 py-2 font-semibold text-white disabled:opacity-50">{{
                        processing ? 'Sending…' : 'Send for approval' }}</button>
            </section>
        </main>
    </AppLayout>
</template>
