<script setup>
import { computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

const page = usePage();
const status = computed(() => page.props.flash?.success);

const resendForm = useForm({});

function resend() {
    resendForm.post(route('verification.send'));
}

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <Head title="验证邮箱" />

    <AuthLayout title="请先验证邮箱" description="首版后台核心页面受 verified 中间件保护，完成验证后才能继续访问。">
        <div class="space-y-5">
            <UiAlert>
                <UiAlertTitle>验证提醒</UiAlertTitle>
                <UiAlertDescription>
                    系统已向你的邮箱发送验证链接。验证成功后，重新进入后台即可访问仪表盘和业务模块。
                </UiAlertDescription>
            </UiAlert>

            <UiAlert v-if="status">
                <UiAlertTitle>发送成功</UiAlertTitle>
                <UiAlertDescription>{{ status }}</UiAlertDescription>
            </UiAlert>

            <div class="grid gap-3">
                <UiButton class="h-11 rounded-2xl" :disabled="resendForm.processing" @click="resend">
                    {{ resendForm.processing ? '发送中...' : '重新发送验证邮件' }}
                </UiButton>
                <UiButton variant="outline" class="h-11 rounded-2xl" @click="logout">
                    退出登录
                </UiButton>
            </div>
        </div>
    </AuthLayout>
</template>
