<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const hasErrors = computed(() => Object.keys(form.errors).length > 0);

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <form class="space-y-5" @submit.prevent="submit">
        <UiAlert v-if="hasErrors" variant="destructive">
            <UiAlertTitle>登录失败</UiAlertTitle>
            <UiAlertDescription>
                请检查邮箱和密码，并根据提示修正输入内容。
            </UiAlertDescription>
        </UiAlert>

        <div class="space-y-2">
            <UiLabel for="login-email">邮箱</UiLabel>
            <UiInput
                id="login-email"
                v-model="form.email"
                type="email"
                autocomplete="username"
                placeholder="you@example.com"
                :aria-invalid="Boolean(form.errors.email)"
            />
            <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
        </div>

        <div class="space-y-2">
            <div class="flex items-center justify-between gap-3">
                <UiLabel for="login-password">密码</UiLabel>
                <Link href="/forgot-password" class="text-sm font-medium text-slate-600 transition hover:text-slate-950">
                    忘记密码？
                </Link>
            </div>
            <UiInput
                id="login-password"
                v-model="form.password"
                type="password"
                autocomplete="current-password"
                :aria-invalid="Boolean(form.errors.password)"
            />
            <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
        </div>

        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
            <UiCheckbox v-model="form.remember" />
            <span class="text-sm text-slate-700">记住我</span>
        </label>

        <UiButton type="submit" class="h-11 w-full rounded-2xl" :disabled="form.processing">
            {{ form.processing ? '登录中...' : '登录' }}
        </UiButton>
    </form>
</template>
