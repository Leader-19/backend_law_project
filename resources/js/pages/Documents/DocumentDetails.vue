<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, Download, FileText, FolderOpen, Image as ImageIcon } from 'lucide-vue-next'
import { route } from 'ziggy-js'

interface Document { id: number; doc_name: string; doc_title: string; doc_upload: string; image: string | null; description: string | null; category?: { id: number; title: string } }
const props = defineProps<{ document: Document }>()
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Document details', href: '/documents' }]
</script>

<template>
    <Head title="Document Details" />
    <AppLayout :breadcrumbs="breadcrumbs"><main class="mx-auto max-w-4xl p-4 sm:p-6"><Link :href="route('documents.index')" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-blue-600 dark:text-slate-300"><ArrowLeft class="h-4 w-4" /> Back to documents</Link><section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"><div class="bg-gradient-to-r from-slate-800 to-slate-700 p-6 text-white"><div class="flex items-start gap-4"><div class="rounded-xl bg-white/15 p-3"><FileText class="h-7 w-7" /></div><div><p class="text-sm text-slate-300">Document</p><h1 class="text-2xl font-bold">{{ props.document.doc_name }}</h1><p class="mt-1 text-slate-300">{{ props.document.doc_title }}</p></div></div></div><div class="grid gap-6 p-6 md:grid-cols-[1fr_220px]"><div class="space-y-5"><div><p class="text-sm font-medium text-slate-500">Description</p><p class="mt-1 whitespace-pre-line text-slate-800 dark:text-slate-200">{{ props.document.description || 'No description provided.' }}</p></div><div v-if="props.document.doc_upload"><p class="text-sm font-medium text-slate-500">Attached file</p><a :href="`/storage/${props.document.doc_upload}`" target="_blank" class="mt-2 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"><Download class="h-4 w-4" /> Open / download file</a></div></div><aside class="space-y-4"><div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><div class="flex items-center gap-2 text-sm text-slate-500"><FolderOpen class="h-4 w-4" /> Category</div><p class="mt-2 font-semibold text-slate-900 dark:text-white">{{ props.document.category?.title || 'Uncategorized' }}</p></div><div v-if="props.document.image"><p class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-500"><ImageIcon class="h-4 w-4" /> Preview</p><a :href="`/storage/${props.document.image}`" target="_blank"><img :src="`/storage/${props.document.image}`" :alt="props.document.doc_name" class="aspect-square w-full rounded-xl object-cover ring-1 ring-slate-200 hover:opacity-90 dark:ring-slate-700" /></a></div></aside></div></section></main></AppLayout>
</template>
