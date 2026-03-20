<script setup>
import { Head } from '@inertiajs/vue3';

const breadcrumbs = [
    { label: '仪表盘', href: '/dashboard' },
    { label: '修改密码' },
];
</script>

<template>
    <Head title="修改密码" />

    <AppLayout
        title="修改密码"
        description="更新当前登录账号的密码；提交成功后会保持当前登录状态。"
        :breadcrumbs="breadcrumbs"
    >
        <AccountPasswordForm />
    </AppLayout>
</template>
