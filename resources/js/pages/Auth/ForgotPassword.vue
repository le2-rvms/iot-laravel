<script setup>
import { Head } from '@inertiajs/vue3';
import ForgotPasswordForm from '@/components/auth/ForgotPasswordForm.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
</script>

<template>
    <Head title="忘记密码" />

    <AuthLayout title="找回密码" description="输入账号邮箱，系统会发送密码重置链接。">
        <ForgotPasswordForm />
    </AuthLayout>
</template>
