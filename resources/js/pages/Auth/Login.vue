<script setup>
import { Head } from '@inertiajs/vue3';
import LoginForm from '@/components/auth/LoginForm.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
</script>

<template>
    <Head title="登录" />

    <AuthLayout title="登录后台" description="使用已开通的后台账号进入管理系统。">
        <LoginForm />
    </AuthLayout>
</template>
