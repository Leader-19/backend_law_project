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
import { Eye, EyeOff, LockKeyhole, Mail, AlertCircle } from '@lucide/vue';
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
            class="mb-6 flex items-center gap-2 text-center text-sm font-medium text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 p-3.5 rounded-xl"
        >
            <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>{{ status }}</span>
        </div>

        <div class="flex flex-col gap-5 rounded-2xl border border-slate-100 bg-white p-7 shadow-xl shadow-slate-200/50 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <!-- Google Sign-In Button -->
            <a
                href="/auth/google/redirect?source=backend"
                class="inline-flex h-11 w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:text-slate-900 hover:shadow active:scale-[0.99] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700/80 dark:hover:text-white"
            >
                <svg class="size-5 shrink-0" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M21.35 12.23c0-.71-.06-1.39-.18-2.05H12v3.87h5.24a4.48 4.48 0 0 1-1.94 2.94v2.51h3.14c1.84-1.7 2.91-4.2 2.91-7.27Z"/>
                    <path fill="#34A853" d="M12 21.75c2.63 0 4.84-.87 6.45-2.36l-3.14-2.51c-.87.58-1.98.92-3.31.92-2.54 0-4.7-1.72-5.47-4.03H3.29v2.59A9.75 9.75 0 0 0 12 21.75Z"/>
                    <path fill="#FBBC05" d="M6.53 13.77A5.84 5.84 0 0 1 6.22 12c0-.61.11-1.2.31-1.77V7.64H3.29A9.75 9.75 0 0 0 2.25 12c0 1.57.38 3.05 1.04 4.36l3.24-2.59Z"/>
                    <path fill="#EA4335" d="M12 6.2c1.43 0 2.71.49 3.72 1.45l2.79-2.79C16.83 3.29 14.63 2.25 12 2.25a9.75 9.75 0 0 0-8.71 5.39l3.24 2.59C7.3 7.92 9.46 6.2 12 6.2Z"/>
                </svg>
                <span>បន្តជាមួយ Google (Sign in with Google)</span>
            </a>

            <!-- Divider -->
            <div class="relative flex items-center justify-center my-1">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
                </div>
                <div class="relative bg-white px-3 text-xs uppercase font-medium tracking-wider text-slate-400 dark:bg-slate-900 dark:text-slate-500">
                    ឬអ៊ីមែល
                </div>
            </div>

            <!-- Email & Password Form -->
            <Form
                v-bind="store.form()"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-5"
            >
                <div
                    v-if="errors && Object.keys(errors).length > 0 && errors.email"
                    class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-600 dark:border-red-800/80 dark:bg-red-950/30 dark:text-red-400"
                >
                    <AlertCircle class="mt-0.5 size-4 shrink-0" />
                    <span>{{ errors.email }}</span>
                </div>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="email" class="text-sm font-semibold text-slate-700 dark:text-slate-300">អ៊ីមែល (Email)</Label>
                        <div class="relative">
                            <Mail class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="email"
                                placeholder="name@example.com"
                                class="h-11 pl-10 rounded-xl bg-slate-50/70 border-slate-200 focus:bg-white dark:bg-slate-800/60 dark:border-slate-700 dark:focus:bg-slate-800"
                            />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-sm font-semibold text-slate-700 dark:text-slate-300">ពាក្យសម្ងាត់ (Password)</Label>
                            <TextLink
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                :tabindex="5"
                            >
                                ភ្លេចពាក្យសម្ងាត់?
                            </TextLink>
                        </div>
                        <div class="relative">
                            <LockKeyhole class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                            <Input
                                id="password"
                                :type="passwordVisible ? 'text' : 'password'"
                                name="password"
                                required
                                :tabindex="2"
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="h-11 px-10 rounded-xl bg-slate-50/70 border-slate-200 focus:bg-white dark:bg-slate-800/60 dark:border-slate-700 dark:focus:bg-slate-800"
                            />
                            <button
                                type="button"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition"
                                :aria-label="passwordVisible ? 'Hide password' : 'Show password'"
                                @click="passwordVisible = !passwordVisible"
                            >
                                <EyeOff v-if="passwordVisible" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                        <InputError :message="errors.password" />
                    </div>

                    <div class="flex items-center gap-2.5 py-0.5">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <Label for="remember" class="text-sm text-slate-600 dark:text-slate-400 cursor-pointer font-normal">
                            ចងចាំខ្ញុំ (Remember me)
                        </Label>
                    </div>

                    <Button
                        type="submit"
                        class="mt-2 h-11 w-full rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 text-sm font-semibold text-white shadow-md shadow-blue-500/20 hover:from-blue-700 hover:via-indigo-700 hover:to-violet-700 active:scale-[0.99] transition-all"
                        size="lg"
                        :tabindex="4"
                        :disabled="processing"
                        data-test="login-button"
                    >
                        <Spinner v-if="processing" class="mr-2" />
                        ចូលគណនី (Sign in)
                    </Button>
                </div>
            </Form>

            <!-- <div v-if="canRegister" class="text-center text-sm text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
                មិនទាន់មានគណនី?
                <TextLink
                    href="/register"
                    class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-semibold ml-1"
                >
                    ចុះឈ្មោះឥឡូវនេះ (Sign up)
                </TextLink>
            </div> -->
        </div>
    </AuthBase>
</template>
