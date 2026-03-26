<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

const props = defineProps({
    email: {
        type: String,
        default: '',
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <form class="space-y-5" @submit.prevent="submit">
        <div class="space-y-2">
            <UiLabel for="reset-email">邮箱</UiLabel>
            <UiInput
                id="reset-email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                :aria-invalid="Boolean(form.errors.email)"
            />
            <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
        </div>

        <div class="space-y-2">
            <UiLabel for="reset-password">新密码</UiLabel>
            <UiInput
                id="reset-password"
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                :aria-invalid="Boolean(form.errors.password)"
            />
            <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
        </div>

        <div class="space-y-2">
            <UiLabel for="reset-password-confirmation">确认新密码</UiLabel>
            <UiInput
                id="reset-password-confirmation"
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
                :aria-invalid="Boolean(form.errors.password_confirmation)"
            />
            <p v-if="form.errors.password_confirmation" class="text-sm text-red-600">
                {{ form.errors.password_confirmation }}
            </p>
        </div>

        <UiButton type="submit" class="h-11 w-full rounded-2xl" :disabled="form.processing">
            {{ form.processing ? '提交中...' : '重置密码' }}
        </UiButton>

        <Link :href="route('login')" class="app-link-muted block text-center text-sm font-medium">
            返回登录
        </Link>
    </form>
</template>
