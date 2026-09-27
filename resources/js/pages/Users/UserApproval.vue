<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { UserCheck, UserX, CheckCircle, XCircle, Users } from '@lucide/vue'
import { ref } from 'vue'
import { type BreadcrumbItem } from '@/types'

interface User {
    id: number
    name: string
    email: string
    status: string
    created_at: string
    roles: Array<{ name: string }>
}

interface Pagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'User Approvals', href: '/user-approvals' },
]

const props = defineProps<{
    users: { data: User[] } & Pagination
    currentStatus: string
}>()

const selectedIds = ref<number[]>([])
const rejectReason = ref<string>('')
const rejectingId = ref<number | null>(null)

function approveUser(id: number) {
    router.post(route('user-approvals.approve', id))
}

function openRejectDialog(id: number) {
    rejectingId.value = id
    rejectReason.value = ''
}

function confirmReject() {
    if (rejectingId.value) {
        router.post(route('user-approvals.reject', rejectingId.value), {
            rejection_reason: rejectReason.value,
        })
        rejectingId.value = null
        rejectReason.value = ''
    }
}

function deactivateUser(id: number) {
    if (confirm('Deactivate this user?')) {
        router.post(route('user-approvals.deactivate', id))
    }
}

function activateUser(id: number) {
    router.post(route('user-approvals.activate', id))
}

function filterByStatus(status: string) {
    router.get(route('user-approvals.index'), { status }, { preserveState: true })
}

function bulkApprove() {
    if (selectedIds.value.length === 0) return
    if (!confirm(`Approve ${selectedIds.value.length} users?`)) return

    router.post(route('user-approvals.bulk-approve'), { ids: selectedIds.value }, {
        onSuccess: () => { selectedIds.value = [] }
    })
}

function bulkReject() {
    if (selectedIds.value.length === 0) return
    if (!confirm(`Reject ${selectedIds.value.length} users?`)) return

    router.post(route('user-approvals.bulk-reject'), { ids: selectedIds.value }, {
        onSuccess: () => { selectedIds.value = [] }
    })
}

function changePage(page: number) {
    router.get(route('user-approvals.index'), { status: props.currentStatus, page }, { preserveState: true })
}
</script>

<template>
    <Head title="User Approvals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3">
            <div class="flex items-center gap-3 mb-6">
                <UserCheck class="w-6 h-6 text-blue-600" />
                <h1 class="text-2xl font-bold text-gray-900">User Approvals</h1>
            </div>

            <!-- Status Tabs -->
            <div class="flex gap-2 mb-6 flex-wrap">
                <button
                    v-for="status in ['pending', 'approved', 'rejected', 'inactive', 'all']"
                    :key="status"
                    @click="filterByStatus(status)"
                    :class="[
                        'px-4 py-2 text-sm rounded-lg capitalize transition',
                        currentStatus === status
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                >
                    {{ status }}
                </button>
            </div>

            <!-- Bulk Actions -->
            <div v-if="selectedIds.length > 0 && currentStatus === 'pending'" class="flex gap-2 mb-4">
                <button @click="bulkApprove" class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                    Approve {{ selectedIds.length }}
                </button>
                <button @click="bulkReject" class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                    Reject {{ selectedIds.length }}
                </button>
            </div>

            <!-- Users Table -->
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th v-if="currentStatus === 'pending'" class="px-4 py-3 text-left">
                                <input type="checkbox" @change="(e: Event) => {
                                    const checked = (e.target as HTMLInputElement).checked
                                    selectedIds = checked ? users.data.filter(u => u.status === 'pending').map(u => u.id) : []
                                }" />
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Registered</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                            <td v-if="currentStatus === 'pending'" class="px-4 py-3">
                                <input type="checkbox" :value="user.id" v-model="selectedIds" />
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ user.name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <span :class="[
                                    'text-xs font-medium px-2 py-1 rounded-full',
                                    user.status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                                    user.status === 'approved' ? 'bg-green-100 text-green-700' :
                                    user.status === 'rejected' ? 'bg-red-100 text-red-700' :
                                    'bg-gray-100 text-gray-700'
                                ]">
                                    {{ user.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ user.roles?.map(r => r.name).join(', ') || '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ user.created_at }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-2">
                                    <button
                                        v-if="user.status === 'pending'"
                                        @click="approveUser(user.id)"
                                        class="p-1.5 bg-green-500 text-white rounded hover:bg-green-600"
                                        title="Approve"
                                    >
                                        <CheckCircle class="w-4 h-4" />
                                    </button>
                                    <button
                                        v-if="user.status === 'pending'"
                                        @click="openRejectDialog(user.id)"
                                        class="p-1.5 bg-red-500 text-white rounded hover:bg-red-600"
                                        title="Reject"
                                    >
                                        <XCircle class="w-4 h-4" />
                                    </button>
                                    <button
                                        v-if="user.status === 'approved'"
                                        @click="deactivateUser(user.id)"
                                        class="p-1.5 bg-yellow-500 text-white rounded hover:bg-yellow-600"
                                        title="Deactivate"
                                    >
                                        <UserX class="w-4 h-4" />
                                    </button>
                                    <button
                                        v-if="user.status === 'inactive'"
                                        @click="activateUser(user.id)"
                                        class="p-1.5 bg-green-500 text-white rounded hover:bg-green-600"
                                        title="Activate"
                                    >
                                        <UserCheck class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="users.data.length === 0" class="text-center py-12">
                    <Users class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">No users found.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="mt-6 flex justify-center gap-2">
                <button
                    v-for="page in users.last_page"
                    :key="page"
                    @click="changePage(page)"
                    :class="[
                        'px-3 py-1 rounded text-sm',
                        page === users.current_page
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                >
                    {{ page }}
                </button>
            </div>

            <!-- Reject Dialog -->
            <div v-if="rejectingId" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-semibold mb-4">Reject User</h3>
                    <textarea
                        v-model="rejectReason"
                        rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4"
                        placeholder="Reason for rejection (optional)..."
                    ></textarea>
                    <div class="flex justify-end gap-2">
                        <button @click="rejectingId = null" class="px-4 py-2 text-sm bg-gray-100 rounded hover:bg-gray-200">
                            Cancel
                        </button>
                        <button @click="confirmReject" class="px-4 py-2 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                            Reject
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
