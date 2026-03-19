<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="忘记密码" />

    <AuthLayout title="找回密码" description="输入账号邮箱，系统会发送密码重置链接。">
        <AuthForgotPasswordForm />
    </AuthLayout>
</template>
