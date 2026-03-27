<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

const page = usePage();
const status = computed(() => page.props.flash?.success);

const form = useForm({
    email: '',
});

function submit() {
    form.post(route('password.email'));
}
</script>

<template>
    <form class="space-y-5" @submit.prevent="submit">
        <UiAlert v-if="status">
            <UiAlertTitle>发送成功</UiAlertTitle>
            <UiAlertDescription>
                {{ status }}
            </UiAlertDescription>
        </UiAlert>

        <div class="space-y-2">
            <UiLabel for="forgot-email">邮箱</UiLabel>
            <UiInput
                id="forgot-email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                placeholder="you@example.com"
                :aria-invalid="Boolean(form.errors.email)"
            />
            <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
        </div>

        <UiButton type="submit" class="h-11 w-full rounded-lg" :disabled="form.processing">
            {{ form.processing ? '发送中...' : '发送重置邮件' }}
        </UiButton>

        <Link :href="route('login')" class="app-link-muted block text-center text-sm font-medium">
            返回登录
        </Link>
    </form>
</template>
