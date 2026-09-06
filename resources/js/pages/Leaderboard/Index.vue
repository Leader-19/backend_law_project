<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { Trophy, Medal, User } from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'

interface LeaderboardEntry {
    rank: number
    user_id: number
    total_attempts: number
    best_score: number
    quizzes_passed: number
    average_score: number
    user: { id: number; name: string; avatar: string | null } | null
}

interface UserStats {
    total_attempts: number
    best_score: number
    quizzes_passed: number
    average_score: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Leaderboard', href: '/leaderboard' },
]

const props = defineProps<{
    leaderboard: LeaderboardEntry[]
    userRank: number | null
    userStats: UserStats
}>()

function getRankIcon(rank: number) {
    if (rank === 1) return '🥇'
    if (rank === 2) return '🥈'
    if (rank === 3) return '🥉'
    return `#${rank}`
}
</script>

<template>
    <Head title="Leaderboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 max-w-8xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Trophy class="w-6 h-6 text-yellow-500" />
                <h1 class="text-2xl font-bold text-gray-900">Leaderboard</h1>
            </div>

            <!-- Current User Stats -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-6 text-white mb-6">
                <h2 class="text-lg font-semibold mb-4">Your Stats</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white/10 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold">{{ userRank ?? '-' }}</p>
                        <p class="text-xs text-blue-100">Rank</p>
                    </div>
                    <div class="bg-white/10 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold">{{ userStats.best_score }}%</p>
                        <p class="text-xs text-blue-100">Best Score</p>
                    </div>
                    <div class="bg-white/10 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold">{{ userStats.quizzes_passed }}</p>
                        <p class="text-xs text-blue-100">Quizzes Passed</p>
                    </div>
                    <div class="bg-white/10 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold">{{ userStats.average_score }}%</p>
                        <p class="text-xs text-blue-100">Avg Score</p>
                    </div>
                </div>
            </div>

            <!-- Leaderboard Table -->
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div v-if="leaderboard.length === 0" class="text-center py-12">
                    <Trophy class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500 text-lg">No leaderboard data yet.</p>
                </div>

                <table v-else class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Rank</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">User</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Best Score</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Avg Score</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Passed</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Attempts</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr
                            v-for="entry in leaderboard"
                            :key="entry.user_id"
                            :class="[
                                entry.rank <= 3 ? 'bg-yellow-50/50' : '',
                                entry.user_id === ($page.props.auth as any).user?.id ? 'bg-blue-50' : ''
                            ]"
                        >
                            <td class="px-4 py-3">
                                <span class="text-lg font-bold" :class="entry.rank <= 3 ? 'text-yellow-600' : 'text-gray-500'">
                                    {{ getRankIcon(entry.rank) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div v-if="entry.user?.avatar" class="w-8 h-8 rounded-full overflow-hidden">
                                        <img :src="`/storage/${entry.user.avatar}`" :alt="entry.user.name" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                                        <User class="w-4 h-4 text-gray-400" />
                                    </div>
                                    <span class="font-medium text-gray-900">{{ entry.user?.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-gray-900">{{ entry.best_score }}%</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-gray-600">{{ entry.average_score }}%</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1 text-green-600 font-medium">
                                    <Medal class="w-4 h-4" />
                                    {{ entry.quizzes_passed }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-gray-500">{{ entry.total_attempts }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
