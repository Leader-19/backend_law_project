<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Forgot password"
        description="Enter your email to receive a password reset link"
    >
        <Head title="Forgot password" />

        <div
            v-if="status"
            class="mb-6 text-center text-sm font-medium text-green-600 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg"
        >
            {{ status }}
        </div>

        <div class="space-y-6 bg-white dark:bg-gray-900 p-8 rounded-[5px] border border-gray-100 dark:border-gray-800">
            <Form v-bind="email.form()" v-slot="{ errors, processing }">
                <div class="grid gap-2">
                    <Label for="email" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        autofocus
                        placeholder="email@example.com"
                        class="h-11"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="mt-6">
                    <Button
                        type="submit"
                        class="w-full h-11 text-base font-semibold"
                        size="lg"
                        :disabled="processing"
                        data-test="email-password-reset-link-button"
                    >
                        <Spinner v-if="processing" />
                        Email password reset link
                    </Button>
                </div>
            </Form>

            <div class="text-center text-sm text-muted-foreground">
                <span>Or, return to</span>
                <TextLink :href="login()" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium">log in</TextLink>
            </div>
        </div>
    </AuthLayout>
</template>
