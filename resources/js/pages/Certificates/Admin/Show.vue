<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ArrowLeft, Award, RefreshCw } from 'lucide-vue-next'
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
    attempt: {
        id: number
        correct_answers: number
        total_questions: number
    } | null
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Certificate Management', href: '/certificate-management' },
    { title: 'Certificate', href: '#' },
]

const props = defineProps<{
    certificate: Certificate
}>()
</script>

<template>
    <Head :title="`Certificate: ${certificate.certificate_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-3xl mx-auto">
            <Link :href="route('certificates-management.index')" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <ArrowLeft class="w-4 h-4" /> Back to Certificates
            </Link>

            <div class="bg-white border border-yellow-300 rounded-lg p-8 text-center">
                <Award class="w-16 h-16 text-yellow-500 mx-auto mb-4" />
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Certificate of Achievement</h1>
                <div class="w-24 h-1 bg-yellow-400 mx-auto mb-6"></div>

                <p class="text-gray-600 mb-2">Awarded to</p>
                <h2 class="text-2xl font-bold text-blue-600 mb-4">{{ certificate.user.name }}</h2>
                <p class="text-sm text-gray-500 mb-4">{{ certificate.user.email }}</p>

                <p class="text-gray-600 mb-2">for completing</p>
                <h3 class="text-xl font-semibold text-gray-900 mb-1">{{ certificate.quiz.title }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ certificate.quiz.category.title }}</p>

                <div class="flex justify-center gap-8 mb-6">
                    <div>
                        <p class="text-3xl font-bold text-green-600">{{ certificate.score }}%</p>
                        <p class="text-sm text-gray-500">Score</p>
                    </div>
                    <div v-if="certificate.attempt">
                        <p class="text-3xl font-bold text-blue-600">{{ certificate.attempt.correct_answers }}/{{ certificate.attempt.total_questions }}</p>
                        <p class="text-sm text-gray-500">Correct</p>
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
            </div>
        </div>
    </AppLayout>
</template>
