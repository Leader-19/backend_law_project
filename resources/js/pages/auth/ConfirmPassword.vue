<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';
import { Form, Head } from '@inertiajs/vue3';
</script>

<template>
    <AuthLayout
        title="Confirm your password"
        description="This is a secure area of the application. Please confirm your password before continuing."
    >
        <Head title="Confirm password" />

        <Form
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
            class="grid gap-5 bg-white dark:bg-gray-900 p-8 rounded-[5px] border border-gray-100 dark:border-gray-800"
        >
            <div class="grid gap-2">
                <Label for="password" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Password</Label>
                <Input
                    id="password"
                    type="password"
                    name="password"
                    class="h-11"
                    required
                    autocomplete="current-password"
                    autofocus
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    type="submit"
                    class="w-full h-11 text-base font-semibold"
                    size="lg"
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" />
                    Confirm Password
                </Button>
            </div>
        </Form>
    </AuthLayout>
</template>
