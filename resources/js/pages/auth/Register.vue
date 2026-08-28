<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
</script>

<template>
    <AuthBase
        title="បង្កើតគណនីយថ្មី"
        description="សូមបំពេញ​ ព័ត៍មានខាងក្រោម"
    >
        <Head title="Register" />

        <Form
            action="/register"
            method="post"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6 bg-white dark:bg-gray-900 p-8 rounded-2xl border border-gray-100 dark:border-gray-800"
        >
            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="name" class="text-sm font-semibold text-gray-700 dark:text-gray-300">ឈ្មោះ</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Full name"
                        class="h-11"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email" class="text-sm font-semibold text-gray-700 dark:text-gray-300">អុីម៉ែល</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                        class="h-11"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="text-sm font-semibold text-gray-700 dark:text-gray-300">លេខសម្ងាត់</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                        class="h-11"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation" class="text-sm font-semibold text-gray-700 dark:text-gray-300">បញ្ជាក់​​ លេខសម្ងាត់</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                        class="h-11"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full h-11 text-base font-semibold"
                    size="lg"
                    tabindex="5"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    បង្កើតគណនីយថ្មី
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                មានគណនីយ?
                <TextLink
                    :href="login()"
                    class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                    :tabindex="6"
                    >ចូលទៅកាន់គណនីយ</TextLink
                >
            </div>
        </Form>
    </AuthBase>
</template>
