<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10"
    >
        <div class="w-full max-w-md">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium transition-opacity hover:opacity-80"
                    >
                        <div
                            class="flex h-16 w-16 items-center justify-center"
                        >
                            <AppLogoIcon
                                class="size-24 fill-current text-[var(--foreground)] dark:text-white"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground max-w-xs">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
