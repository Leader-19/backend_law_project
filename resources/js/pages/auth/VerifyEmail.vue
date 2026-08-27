<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Verify email"
        description="Please verify your email address by clicking on the link we just emailed to you."
    >
        <Head title="Email verification" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-6 text-center text-sm font-medium text-green-600 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg"
        >
            A new verification link has been sent to the email address you
            provided during registration.
        </div>

        <div class="space-y-6 bg-white dark:bg-gray-900 p-8 rounded-2xl border border-gray-100 dark:border-gray-800">
            <Form
                v-bind="send.form()"
                class="space-y-6 text-center"
                v-slot="{ processing }"
            >
                <Button :disabled="processing" variant="secondary" size="lg" class="w-full">
                    <Spinner v-if="processing" />
                    Resend verification email
                </Button>

                <TextLink
                    :href="logout()"
                    as="button"
                    class="mx-auto block text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                >
                    Log out
                </TextLink>
            </Form>
        </div>
    </AuthLayout>
</template>
