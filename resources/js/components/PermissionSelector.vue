<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    permissions: string[]
}>()

const model = defineModel<string[]>({ required: true })

const groups = computed(() => {
    return props.permissions.reduce<Record<string, string[]>>((result, permission) => {
        const group = permission.split('.')[0] || 'other'
        result[group] ??= []
        result[group].push(permission)
        return result
    }, {})
})

function toggleGroup(groupPermissions: string[]) {
    const allSelected = groupPermissions.every((permission) => model.value.includes(permission))
    model.value = allSelected
        ? model.value.filter((permission) => !groupPermissions.includes(permission))
        : [...new Set([...model.value, ...groupPermissions])]
}
</script>

<template>
    <div class="space-y-3">
        <div v-for="(groupPermissions, group) in groups" :key="group" class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold capitalize text-slate-800 dark:text-slate-100">{{ group }}</h3>
                <button type="button" class="text-xs font-medium text-blue-600 hover:text-blue-700" @click="toggleGroup(groupPermissions)">
                    Select / clear all
                </button>
            </div>
            <div class="grid gap-2 sm:grid-cols-2">
                <label v-for="permission in groupPermissions" :key="permission" class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">
                    <input v-model="model" :value="permission" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ permission }}</span>
                </label>
            </div>
        </div>
    </div>
</template>
