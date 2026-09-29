<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { Award, Trash2, RefreshCw } from '@lucide/vue'
import { type BreadcrumbItem } from '@/types'

interface Certificate {
    id: number
    certificate_number: string
    score: number
    created_at: string
    user: { id: number; name: string; email: string }
    quiz: { id: number; title: string; category: { id: number; title: string } }
}

interface Stats {
    total: number
    this_month: number
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Certificate Management', href: '/certificate-management' },
]

const props = defineProps<{
    certificates: { data: Certificate[] } & Pagination
    search: string
    stats: Stats
}>()

function regenerate(id: number) {
    if (confirm('Regenerate certificate number?')) {
        router.post(route('certificates-management.regenerate', id))
    }
}

function deleteCertificate(id: number) {
    if (confirm('Delete this certificate?')) {
        router.delete(route('certificates-management.destroy', id))
    }
}

function changePage(page: number) {
    router.get(route('certificates-management.index'), { page, search: props.search }, { preserveState: true })
}
</script>

<template>
    <Head title="Certificate Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">
            <div class="flex items-center gap-3 mb-6">
                <Award class="w-6 h-6 text-yellow-500" />
                <h1 class="text-2xl font-bold text-gray-900">Certificate Management</h1>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 mb-6 max-w-md">
                <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                    <p class="text-sm text-gray-500">Total Certificates</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ stats.this_month }}</p>
                    <p class="text-sm text-gray-500">This Month</p>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Certificate #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Quiz</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Score</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Date</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="cert in certificates.data" :key="cert.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-sm text-gray-900">{{ cert.certificate_number }}</td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-900">{{ cert.user.name }}</p>
                                <p class="text-xs text-gray-500">{{ cert.user.email }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ cert.quiz.title }}</td>
                            <td class="px-4 py-3 text-center text-sm font-bold text-green-600">{{ cert.score }}%</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ cert.created_at }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-2">
                                    <Link
                                        :href="route('certificates-management.show', cert.id)"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded"
                                        title="View"
                                    >
                                        <Award class="w-4 h-4" />
                                    </Link>
                                    <button @click="regenerate(cert.id)" class="p-1.5 text-yellow-600 hover:bg-yellow-50 rounded" title="Regenerate">
                                        <RefreshCw class="w-4 h-4" />
                                    </button>
                                    <button @click="deleteCertificate(cert.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded" title="Delete">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="certificates.data.length === 0" class="text-center py-12">
                    <Award class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">No certificates found.</p>
                </div>
            </div>

            <div v-if="certificates.last_page > 1" class="mt-6 flex justify-center gap-2">
                <button
                    v-for="page in certificates.last_page"
                    :key="page"
                    @click="changePage(page)"
                    :class="[
                        'px-3 py-1 rounded text-sm',
                        page === certificates.current_page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                >
                    {{ page }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
