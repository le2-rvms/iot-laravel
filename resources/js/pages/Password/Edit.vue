<script setup>
import { Head } from '@inertiajs/vue3';

// 改密保留独立页面，不走弹窗，便于和权限路由、返回路径保持一致。
const breadcrumbs = [
    { label: '仪表盘', href: '/admin/dashboard' },
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
