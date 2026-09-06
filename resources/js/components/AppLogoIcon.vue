<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

interface Props {
    className?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <img
        src="/logo.jpg"
        alt="SPRITUP"
        :class="['h-full w-full object-contain', props.className]"
    />
</template>
