<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft, Award, Download, Printer } from '@lucide/vue'
import { type BreadcrumbItem } from '@/types'

interface Certificate {
    id: number
    certificate_number: string
    score: number
    pdf_path: string | null
    created_at: string
    user: { id: number; name: string; email: string }
    quiz: {
        id: number
        title: string
        category: { id: number; title: string }
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Certificates', href: '/certificates' },
    { title: 'Certificate', href: '#' },
]

defineProps<{
    certificate: Certificate
    pdfUrl: string | null
}>()

function printCertificate() {
    window.print()
}
</script>

<template>
    <Head :title="`Certificate: ${certificate.certificate_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-8xl mx-auto">
            <Link :href="route('certificates.index')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <ArrowLeft class="w-4 h-4" /> Back to Certificates
            </Link>

            <!-- Certificate Card -->
            <div class="bg-white border-2 border-yellow-300 rounded-lg p-8 text-center shadow-lg print:shadow-none print:border-yellow-400">
                <div class="mb-6">
                    <Award class="w-16 h-16 text-yellow-500 mx-auto" />
                </div>

                <h1 class="text-3xl font-bold text-gray-900 mb-2">Certificate of Achievement</h1>
                <div class="w-24 h-1 bg-yellow-400 mx-auto mb-6"></div>

                <p class="text-gray-600 mb-2">This is to certify that</p>
                <h2 class="text-2xl font-bold text-blue-600 mb-4">{{ certificate.user.name }}</h2>

                <p class="text-gray-600 mb-2">has successfully completed the quiz</p>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ certificate.quiz.title }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ certificate.quiz.category.title }}</p>

                <div class="flex justify-center gap-8 mb-6">
                    <div>
                        <p class="text-3xl font-bold text-green-600">{{ certificate.score }}%</p>
                        <p class="text-sm text-gray-500">Score</p>
                    </div>
                </div>

                <div class="w-full h-px bg-gray-200 my-6"></div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Certificate Number</p>
                        <p class="font-mono font-semibold text-gray-900">{{ certificate.certificate_number }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Date Issued</p>
                        <p class="font-semibold text-gray-900">{{ certificate.created_at }}</p>
                    </div>
                </div>

                <div class="mt-6 p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">
                        Verify this certificate at: /verify/{{ certificate.certificate_number }}
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex justify-center gap-4 print:hidden">
                <button
                    @click="printCertificate"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                >
                    <Printer class="w-4 h-4" /> Print Certificate
                </button>
                <a
                    :href="route('certificates.download', certificate.id)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition"
                >
                    <Download class="w-4 h-4" /> Download PDF
                </a>
            </div>
        </div>
    </AppLayout>
</template>
