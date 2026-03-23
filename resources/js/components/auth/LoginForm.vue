<script setup>
import { computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    devQuickLogin: {
        type: Object,
        default: () => ({
            enabled: false,
            users: [],
        }),
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const hasErrors = computed(() => Object.keys(form.errors).length > 0);
const showDevQuickLogin = computed(() => props.devQuickLogin.enabled);

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}

function loginAs(loginUrl) {
    router.post(loginUrl);
}
</script>

<template>
    <div class="space-y-6">
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
                    <Link href="/forgot-password" class="app-link-muted text-sm font-medium">
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

            <label class="app-option-card flex items-center gap-3 rounded-2xl border px-4 py-3">
                <UiCheckbox v-model="form.remember" />
                <span class="app-copy-strong text-sm">记住我</span>
            </label>

            <UiButton type="submit" class="h-11 w-full rounded-2xl" :disabled="form.processing">
                {{ form.processing ? '登录中...' : '登录' }}
            </UiButton>
        </form>

        <section v-if="showDevQuickLogin" class="space-y-4 rounded-[1.5rem] border border-dashed border-app-panel-border bg-app-panel/40 p-5">
            <div class="space-y-1">
                <h2 class="app-copy-strong text-base">开发环境快捷登录</h2>
                <p class="text-sm text-app-subtle-foreground">仅在 dev 环境显示，直接选择现有管理员进入后台。</p>
            </div>

            <UiAlert v-if="devQuickLogin.users.length === 0">
                <UiAlertTitle>暂无管理员账号</UiAlertTitle>
                <UiAlertDescription>当前没有可用于快捷登录的管理员账号。</UiAlertDescription>
            </UiAlert>

            <div v-else class="space-y-3">
                <button
                    v-for="adminUser in devQuickLogin.users"
                    :key="adminUser.id"
                    type="button"
                    class="app-option-card flex w-full items-start justify-between gap-4 rounded-2xl border px-4 py-4 text-left"
                    @click="loginAs(adminUser.login_url)"
                >
                    <div class="space-y-1">
                        <p class="app-copy-strong text-sm">{{ adminUser.name }}</p>
                        <p class="text-sm text-app-subtle-foreground">{{ adminUser.email }}</p>
                    </div>
                    <span class="text-xs text-app-subtle-foreground">
                        {{ adminUser.email_verified_at ? '已验证' : '未验证' }}
                    </span>
                </button>
            </div>
        </section>
    </div>
</template>
