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
        description="更新登录密码，完成后可继续使用当前账号。"
        :breadcrumbs="breadcrumbs"
    >
        <AccountPasswordForm />
    </AppLayout>
</template>
