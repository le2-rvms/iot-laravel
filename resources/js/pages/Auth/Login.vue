<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    devQuickLogins: {
        type: Array,
        default: null,
    },
});
</script>

<template>
    <Head title="登录" />

    <AuthLayout title="登录后台" description="使用已开通的后台账号进入管理系统。">
        <AuthLoginForm :dev-quick-logins="devQuickLogins" />
    </AuthLayout>
</template>
