<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase
        title="ចូលទៅកាន់គណនីយយ របស់លោកអ្នក"
        description="ប្រើប្រាស់ អុីម៉ែល និង​ លេខសម្ងាត់ ដើម្បីចូលទៅកាន់​ គណនីយ របស់លោកអ្នក"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-6 text-center text-sm font-medium text-green-600 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6 bg-white dark:bg-gray-900 p-8 rounded-2xl border border-gray-100 dark:border-gray-800"
        >
            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="email" class="text-sm font-semibold text-gray-700 dark:text-gray-300">អុីម៉ែល</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="h-11"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-sm font-semibold text-gray-700 dark:text-gray-300">លេខសម្ងាត់</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                            :tabindex="5"
                        >
                            ភ្លេចលេខសម្ងាត់?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                        class="h-11"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center gap-3 py-1">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <Label for="remember" class="text-sm font-medium text-gray-600 dark:text-gray-400 cursor-pointer">រំលឹក</Label>
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full h-11 text-base font-semibold"
                    size="lg"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    ចូលទៅកាន់គណនីយ
                </Button>
            </div>
        </Form>
    </AuthBase>
</template>
