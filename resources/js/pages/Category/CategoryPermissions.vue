<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import ConfirmModal from '@/components/ConfirmModal.vue'
import { ref } from 'vue'
import { Eye, Pencil, Trash2 } from 'lucide-vue-next'

type Person = { id: number; name: string; email: string; permission?: string }
type Team = { id: number; name: string; permission?: string }
type Paginator<T> = { data: T[]; current_page: number; last_page: number; total: number }

const props = defineProps<{
    category: { id: number; title: string }
    users: Paginator<Person>
    allUsers: Paginator<Person>
    teams: Team[]
    availableTeams: Team[]
    permissions: string[]
    filters: { search: string; per_page: number }
}>()

const teamForm = useForm({ role_id: '', permission: 'view' })
const isRemoveUserOpen = ref(false)
const isRemoveTeamOpen = ref(false)
const removingUser = ref<Person | null>(null)
const removingTeam = ref<Team | null>(null)

const selectedUsers = ref<Record<number, string[]>>({})

function toggleUser(userId: number) {
    if (!selectedUsers.value[userId]) {
        selectedUsers.value[userId] = ['view']
    } else {
        delete selectedUsers.value[userId]
    }
}

function togglePermission(userId: number, permission: string) {
    if (!selectedUsers.value[userId]) {
        selectedUsers.value[userId] = ['view']
    }
    const perms = selectedUsers.value[userId]
    const index = perms.indexOf(permission)
    if (index > -1) {
        perms.splice(index, 1)
        if (perms.length === 0) {
            delete selectedUsers.value[userId]
        }
    } else {
        perms.push(permission)
    }
}

function isChecked(userId: number) {
    return !!selectedUsers.value[userId]
}

function isPermissionChecked(userId: number, permission: string) {
    return selectedUsers.value[userId]?.includes(permission) || false
}

function assignSelectedUsers() {
    const assignments = Object.entries(selectedUsers.value).map(([userId, permissions]) => ({
        user_id: Number(userId),
        permissions,
    }))

    if (assignments.length === 0) return

    router.post(route('categories.permissions.store', props.category.id), { assignments }, {
        onSuccess: () => {
            selectedUsers.value = {}
        },
    })
}

function assignTeam() {
    teamForm.post(route('categories.team-permissions.store', props.category.id), { onSuccess: () => teamForm.reset('role_id') })
}

function updateUser(user: Person, permission: string) {
    router.put(route('categories.permissions.update', [props.category.id, user.id]), { permission }, { preserveScroll: true })
}

function changeUserPermission(user: Person, event: Event) {
    updateUser(user, (event.target as HTMLSelectElement).value)
}

function removeUser(user: Person) {
    removingUser.value = user
    isRemoveUserOpen.value = true
}

function confirmRemoveUser() {
    if (!removingUser.value) return
    router.delete(route('categories.permissions.destroy', [props.category.id, removingUser.value.id]), { preserveScroll: true })
    isRemoveUserOpen.value = false
    removingUser.value = null
}

function removeTeam(team: Team) {
    removingTeam.value = team
    isRemoveTeamOpen.value = true
}

function confirmRemoveTeam() {
    if (!removingTeam.value) return
    router.delete(route('categories.team-permissions.destroy', [props.category.id, removingTeam.value.id]), { preserveScroll: true })
    isRemoveTeamOpen.value = false
    removingTeam.value = null
}

function go(pageName: 'assigned_page' | 'user_page', page: number) {
    router.get(route('categories.permissions.index', props.category.id), {
        search: props.filters.search,
        per_page: props.filters.per_page,
        [pageName]: page,
    }, { preserveScroll: true })
}

function parsePermissions(permissionStr: string | undefined): string[] {
    if (!permissionStr) return []
    return permissionStr.split(',').filter(p => p.trim() !== '')
}
</script>

<template>
    <Head :title="`Category access – ${props.category.title}`" />
    <AppLayout :breadcrumbs="[{ title: 'Categories', href: '/categories' }, { title: 'Access', href: '#' }]">
        <main class="mx-auto max-w-8xl space-y-6 p-4 sm:p-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
                <h1 class="text-xl font-bold">Category access: {{ props.category.title }}</h1>
                <p class="mt-1 text-sm text-slate-500">Assign an individual or a team (role). Members only see categories assigned to them or their team.</p>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <form class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900" @submit.prevent="assignSelectedUsers">
                    <h2 class="font-semibold">Assign users</h2>
                    <p class="mt-1 text-xs text-slate-500">Check users to assign, then select permissions and save.</p>
                    <div class="mt-4 max-h-80 overflow-y-auto space-y-2">
                        <div v-for="user in allUsers.data" :key="user.id" class="flex items-center justify-between rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    :checked="isChecked(user.id)"
                                    @change="toggleUser(user.id)"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800"
                                />
                                <div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ user.name }}</div>
                                    <div class="text-xs text-slate-500">{{ user.email }}</div>
                                </div>
                            </label>
                            <div v-if="isChecked(user.id)" class="flex flex-wrap gap-2">
                                <label v-for="permission in permissions" :key="permission" class="flex items-center gap-1 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        :checked="isPermissionChecked(user.id, permission)"
                                        @change="togglePermission(user.id, permission)"
                                        class="h-3.5 w-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800"
                                    />
                                    <span class="text-xs text-slate-600 dark:text-slate-400">{{ permission }}</span>
                                </label>
                            </div>
                        </div>
                        <p v-if="!allUsers.data.length" class="text-sm text-slate-400 text-center py-4">No available users.</p>
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-xs text-slate-500">{{ allUsers.total }} available users</span>
                        <button type="submit" :disabled="Object.keys(selectedUsers).length === 0" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">Save selected</button>
                    </div>
                </form>
                <form class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900" @submit.prevent="assignTeam">
                    <h2 class="font-semibold">Add a team</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2"><select v-model="teamForm.role_id" required class="rounded-lg border p-2 dark:bg-slate-800"><option value="">Select team / role</option><option v-for="team in availableTeams" :key="team.id" :value="team.id">{{ team.name }}</option></select><select v-model="teamForm.permission" class="rounded-lg border p-2 dark:bg-slate-800"><option v-for="permission in permissions" :key="permission" :value="permission">{{ permission }}</option></select></div>
                    <button :disabled="teamForm.processing" class="mt-3 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Assign team</button>
                    <div class="mt-4 space-y-2 text-sm"><div v-for="team in teams" :key="team.id" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800"><span>{{ team.name }} <span class="text-slate-500">({{ team.permission }})</span></span><button type="button" class="text-red-600" @click="removeTeam(team)">Remove</button></div><p v-if="teams.length === 0" class="text-slate-500">No teams assigned.</p></div>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold">Assigned users</h2>
                    <span class="text-sm text-slate-500">{{ users.total }} users</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b text-slate-500">
                            <tr>
                                <th class="p-2">User</th>
                                <th class="p-2">Permissions</th>
                                <th class="p-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="border-b">
                                <td class="p-2">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ user.name }}</div>
                                    <div class="text-slate-500">{{ user.email }}</div>
                                </td>
                                <td class="p-2">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="perm in parsePermissions(user.permission)" :key="perm" class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300">{{ perm }}</span>
                                    </div>
                                </td>
                                <td class="p-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link :href="route('users.show', user.id)" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700" title="View">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                        <Link :href="route('users.edit', user.id)" class="rounded-lg p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50" title="Edit">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button type="button" class="text-red-600 hover:text-red-700" @click="removeUser(user)" title="Remove">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="3" class="p-6 text-center text-slate-500">No users assigned.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-end gap-3 text-sm">
                    <button type="button" :disabled="users.current_page <= 1" @click="go('assigned_page', users.current_page - 1)">Previous</button>
                    <span>Page {{ users.current_page }} of {{ users.last_page }}</span>
                    <button type="button" :disabled="users.current_page >= users.last_page" @click="go('assigned_page', users.current_page + 1)">Next</button>
                </div>
            </section>

            <!-- Remove User Confirmation -->
            <ConfirmModal
                :open="isRemoveUserOpen"
                title="Remove User"
                :description="`Are you sure you want to remove ${removingUser?.name} from this category?`"
                confirm-label="Remove"
                @confirm="confirmRemoveUser"
                @update:open="isRemoveUserOpen = $event"
            />

            <!-- Remove Team Confirmation -->
            <ConfirmModal
                :open="isRemoveTeamOpen"
                title="Remove Team"
                :description="`Are you sure you want to remove ${removingTeam?.name} from this category?`"
                confirm-label="Remove"
                @confirm="confirmRemoveTeam"
                @update:open="isRemoveTeamOpen = $event"
            />
        </main>
    </AppLayout>
</template>