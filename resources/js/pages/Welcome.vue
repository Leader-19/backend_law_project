<script setup lang="ts">
import Login from './auth/Login.vue';


withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>

    <Login :canResetPassword="true" :canRegister="canRegister" />

    <div class="hidden h-14.5 lg:block"></div>

</template>
