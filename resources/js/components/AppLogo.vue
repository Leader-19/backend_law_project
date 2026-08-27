<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square h-14 w-14 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="h-full w-full fill-current text-white dark:text-black" />
    </div>
    <div class="ml-2 grid flex-1 text-left text-xl">
        <span class="mb-0.5 truncate leading-tight font-semibold">SPRITUP</span>
    </div>
</template>
