<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { Eye, EyeOff, AlertCircle } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const passwordVisible = ref(false);
</script>

<template>
    <AuthBase
        title="ចូលទៅកាន់គណនីរបស់អ្នក"
        description="បញ្ចូលអ៊ីមែល និងពាក្យសម្ងាត់របស់អ្នកដើម្បីចូលប្រព័ន្ធ"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-5 flex items-center gap-2 rounded-[5px] border border-green-200 bg-green-50 p-3.5 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300"
        >
            <span class="size-2 rounded-full bg-green-500"></span>
            <span>{{ status }}</span>
        </div>

        <!-- Google Sign-In Button -->
        <a
            href="/auth/google/redirect?source=backend"
            class="flex w-full items-center justify-center gap-3 rounded-[5px] border border-zinc-300 bg-white py-2.5 text-sm font-medium text-zinc-700 transition-colors hover:border-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-500"
        >
            <svg class="size-5 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M21.35 12.23c0-.71-.06-1.39-.18-2.05H12v3.87h5.24a4.48 4.48 0 0 1-1.94 2.94v2.51h3.14c1.84-1.7 2.91-4.2 2.91-7.27Z"/>
                <path fill="#34A853" d="M12 21.75c2.63 0 4.84-.87 6.45-2.36l-3.14-2.51c-.87.58-1.98.92-3.31.92-2.54 0-4.7-1.72-5.47-4.03H3.29v2.59A9.75 9.75 0 0 0 12 21.75Z"/>
                <path fill="#FBBC05" d="M6.53 13.77A5.84 5.84 0 0 1 6.22 12c0-.61.11-1.2.31-1.77V7.64H3.29A9.75 9.75 0 0 0 2.25 12c0 1.57.38 3.05 1.04 4.36l3.24-2.59Z"/>
                <path fill="#EA4335" d="M12 6.2c1.43 0 2.71.49 3.72 1.45l2.79-2.79C16.83 3.29 14.63 2.25 12 2.25a9.75 9.75 0 0 0-8.71 5.39l3.24 2.59C7.3 7.92 9.46 6.2 12 6.2Z"/>
            </svg>
            <span>បន្តជាមួយ Google</span>
        </a>

        <!-- Divider -->
        <div class="my-6 flex items-center gap-3 text-xs text-zinc-400">
            <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700" />
            ឬអ៊ីមែល
            <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700" />
        </div>

        <!-- Email & Password Form -->
        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="space-y-4"
        >
            <div
                v-if="errors && Object.keys(errors).length > 0 && errors.email"
                class="flex items-start gap-2.5 rounded-[5px] border border-red-200 bg-red-50 p-3.5 text-sm text-red-600 dark:border-red-800/80 dark:bg-red-950/30 dark:text-red-400"
            >
                <AlertCircle class="mt-0.5 size-4 shrink-0" />
                <span>{{ errors.email }}</span>
            </div>

            <div>
                <Label for="email" class="text-xs font-medium text-zinc-600 dark:text-zinc-400">អ៊ីមែល (Email)</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="name@example.com"
                    class="mt-1 h-auto w-full rounded-[5px] border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 focus-visible:border-zinc-900 focus-visible:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                />
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-xs font-medium text-zinc-600 dark:text-zinc-400">ពាក្យសម្ងាត់ (Password)</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                        :tabindex="5"
                    >
                        ភ្លេចពាក្យសម្ងាត់?
                    </TextLink>
                </div>
                <div class="relative mt-1">
                    <Input
                        id="password"
                        :type="passwordVisible ? 'text' : 'password'"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="h-auto w-full rounded-[5px] border-zinc-300 bg-white px-3 py-2.5 pr-11 text-sm text-zinc-900 focus-visible:border-zinc-900 focus-visible:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                    />
                    <button
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-700 dark:text-zinc-500 dark:hover:text-zinc-300 transition"
                        :aria-label="passwordVisible ? 'Hide password' : 'Show password'"
                        @click="passwordVisible = !passwordVisible"
                    >
                        <EyeOff v-if="passwordVisible" class="size-4" />
                        <Eye v-else class="size-4" />
                    </button>
                </div>
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center gap-2 py-0.5">
                <Checkbox id="remember" name="remember" :tabindex="3" />
                <Label for="remember" class="cursor-pointer text-sm font-normal text-zinc-600 dark:text-zinc-400">
                    ចងចាំខ្ញុំ (Remember me)
                </Label>
            </div>

            <button
                type="submit"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
                class="flex w-full items-center justify-center gap-2 rounded-[5px] bg-zinc-900 py-2.5 text-sm font-medium text-white transition-colors hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
            >
                <Spinner v-if="processing" class="size-4" />
                <span>ចូលគណនី (Sign in)</span>
            </button>
        </Form>

        <p v-if="canRegister" class="mt-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
            មិនទាន់មានគណនី?
            <TextLink
                href="/register"
                class="ml-1 font-semibold text-zinc-900 underline underline-offset-2 hover:text-zinc-600 dark:text-white dark:hover:text-zinc-300"
            >
                ចុះឈ្មោះឥឡូវនេះ (Sign up)
            </TextLink>
        </p>
    </AuthBase>
</template>
