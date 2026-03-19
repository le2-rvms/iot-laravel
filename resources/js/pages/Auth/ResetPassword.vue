<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    email: {
        type: String,
        default: '',
    },
    token: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="重置密码" />

    <AuthLayout title="设置新密码" description="重置成功后将直接使用新密码登录后台。">
        <AuthResetPasswordForm :email="email" :token="token" />
    </AuthLayout>
</template>
