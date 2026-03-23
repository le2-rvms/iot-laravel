<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    devQuickLogin: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="登录" />

    <AuthLayout title="登录后台" description="使用已开通的后台账号进入管理系统。">
        <AuthLoginForm :dev-quick-login="devQuickLogin" />
    </AuthLayout>
</template>
