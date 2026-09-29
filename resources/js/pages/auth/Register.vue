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
import { Eye, EyeOff, LockKeyhole, Mail, User as UserIcon, CheckCircle2, AlertCircle } from '@lucide/vue';
import { ref, computed } from 'vue';

const passwordVisible = ref(false);
const confirmationVisible = ref(false);
const passwordValue = ref('');
const confirmationValue = ref('');

const passwordsMatch = computed(() => {
    if (!passwordValue.value || !confirmationValue.value) return null;
    return passwordValue.value === confirmationValue.value;
});
</script>

<template>
    <AuthBase title="បង្កើតគណនីថ្មី" description="សូមបំពេញព័ត៌មានខាងក្រោមដើម្បីចុះឈ្មោះ">

        <Head title="Register" />

        <div
            class="flex flex-col gap-5 rounded-[5px] border border-slate-100 bg-white p-7 shadow-xl shadow-slate-200/50 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <!-- Google Sign-In Button -->
            <a href="/auth/google/redirect?source=backend"
                class="inline-flex h-11 w-full items-center justify-center gap-3 rounded-[5px] border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:text-slate-900 hover:shadow active:scale-[0.99] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700/80 dark:hover:text-white">
                <svg class="size-5 shrink-0" viewBox="0 0 24 24">
                    <path fill="#4285F4"
                        d="M21.35 12.23c0-.71-.06-1.39-.18-2.05H12v3.87h5.24a4.48 4.48 0 0 1-1.94 2.94v2.51h3.14c1.84-1.7 2.91-4.2 2.91-7.27Z" />
                    <path fill="#34A853"
                        d="M12 21.75c2.63 0 4.84-.87 6.45-2.36l-3.14-2.51c-.87.58-1.98.92-3.31.92-2.54 0-4.7-1.72-5.47-4.03H3.29v2.59A9.75 9.75 0 0 0 12 21.75Z" />
                    <path fill="#FBBC05"
                        d="M6.53 13.77A5.84 5.84 0 0 1 6.22 12c0-.61.11-1.2.31-1.77V7.64H3.29A9.75 9.75 0 0 0 2.25 12c0 1.57.38 3.05 1.04 4.36l3.24-2.59Z" />
                    <path fill="#EA4335"
                        d="M12 6.2c1.43 0 2.71.49 3.72 1.45l2.79-2.79C16.83 3.29 14.63 2.25 12 2.25a9.75 9.75 0 0 0-8.71 5.39l3.24 2.59C7.3 7.92 9.46 6.2 12 6.2Z" />
                </svg>
                <span>ចុះឈ្មោះជាមួយ Google (Sign up with Google)</span>
            </a>

            <!-- Divider -->
            <div class="relative flex items-center justify-center my-1">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
                </div>
                <div
                    class="relative bg-white px-3 text-xs uppercase font-medium tracking-wider text-slate-400 dark:bg-slate-900 dark:text-slate-500">
                    ឬចុះឈ្មោះតាមអ៊ីមែល
                </div>
            </div>

            <Form action="/register" method="post" :reset-on-success="['password', 'password_confirmation']"
                v-slot="{ errors, processing }" class="flex flex-col gap-4">
                <div class="grid gap-2">
                    <Label for="name" class="text-sm font-semibold text-slate-700 dark:text-slate-300">ឈ្មោះពេញ (Full
                        Name)</Label>
                    <div class="relative">
                        <UserIcon
                            class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                        <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" name="name"
                            placeholder="John Doe"
                            class="h-11 pl-10 rounded-[5px] bg-slate-50/70 border-slate-200 focus:bg-white dark:bg-slate-800/60 dark:border-slate-700 dark:focus:bg-slate-800" />
                    </div>
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email" class="text-sm font-semibold text-slate-700 dark:text-slate-300">អ៊ីមែល
                        (Email)</Label>
                    <div class="relative">
                        <Mail
                            class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                        <Input id="email" type="email" required :tabindex="2" autocomplete="email" name="email"
                            placeholder="name@example.com"
                            class="h-11 pl-10 rounded-[5px] bg-slate-50/70 border-slate-200 focus:bg-white dark:bg-slate-800/60 dark:border-slate-700 dark:focus:bg-slate-800" />
                    </div>
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="text-sm font-semibold text-slate-700 dark:text-slate-300">ពាក្យសម្ងាត់
                        (Password)</Label>
                    <div class="relative">
                        <LockKeyhole
                            class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                        <Input id="password" :type="passwordVisible ? 'text' : 'password'" required :tabindex="3"
                            autocomplete="new-password" name="password" placeholder="យ៉ាងតិច ៨ តួអក្សរ"
                            v-model="passwordValue"
                            class="h-11 px-10 rounded-[5px] bg-slate-50/70 border-slate-200 focus:bg-white dark:bg-slate-800/60 dark:border-slate-700 dark:focus:bg-slate-800" />
                        <button type="button"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition"
                            :aria-label="passwordVisible ? 'Hide password' : 'Show password'"
                            @click="passwordVisible = !passwordVisible">
                            <EyeOff v-if="passwordVisible" class="size-4" />
                            <Eye v-else class="size-4" />
                        </button>
                    </div>
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password_confirmation"
                            class="text-sm font-semibold text-slate-700 dark:text-slate-300">បញ្ជាក់ពាក្យសម្ងាត់
                            (Confirm)</Label>
                        <div v-if="passwordsMatch !== null" class="flex items-center gap-1 text-xs">
                            <span v-if="passwordsMatch" class="flex items-center gap-1 text-emerald-600 font-medium">
                                <CheckCircle2 class="size-3.5" /> ត្រូវគ្នា
                            </span>
                            <span v-else class="flex items-center gap-1 text-amber-600 font-medium">
                                <AlertCircle class="size-3.5" /> មិនទាន់ត្រូវគ្នា
                            </span>
                        </div>
                    </div>
                    <div class="relative">
                        <LockKeyhole
                            class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                        <Input id="password_confirmation" :type="confirmationVisible ? 'text' : 'password'" required
                            :tabindex="4" autocomplete="new-password" name="password_confirmation"
                            placeholder="បញ្ចូលពាក្យសម្ងាត់ម្តងទៀត" v-model="confirmationValue"
                            class="h-11 px-10 rounded-[5px] bg-slate-50/70 border-slate-200 focus:bg-white dark:bg-slate-800/60 dark:border-slate-700 dark:focus:bg-slate-800" />
                        <button type="button"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition"
                            :aria-label="confirmationVisible ? 'Hide password' : 'Show password'"
                            @click="confirmationVisible = !confirmationVisible">
                            <EyeOff v-if="confirmationVisible" class="size-4" />
                            <Eye v-else class="size-4" />
                        </button>
                    </div>
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button type="submit"
                    class="mt-2 h-11 w-full rounded-[5px] bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 text-sm font-semibold text-white shadow-md shadow-blue-500/20 hover:from-blue-700 hover:via-indigo-700 hover:to-violet-700 active:scale-[0.99] transition-all"
                    size="lg" tabindex="5" :disabled="processing" data-test="register-user-button">
                    <Spinner v-if="processing" class="mr-2" />
                    បង្កើតគណនីថ្មី (Create Account)
                </Button>
            </Form>

            <div
                class="text-center text-sm text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
                មានគណនីរួចហើយ?
                <TextLink :href="login()"
                    class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-semibold ml-1"
                    :tabindex="6">
                    ចូលគណនី (Sign in)
                </TextLink>
            </div>
        </div>
    </AuthBase>
</template>
