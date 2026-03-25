<script setup>
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    availableRoles: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: '/admin/dashboard' },
    { label: '管理员用户', href: '/admin/admin-users' },
    { label: '编辑管理员用户' },
];
</script>

<template>
    <Head :title="`编辑 ${props.user.name}`" />

    <AppLayout
        title="编辑管理员用户"
        description="编辑基础资料；若修改邮箱，系统会重置验证状态并重新发送验证邮件。"
        :breadcrumbs="breadcrumbs"
    >
        <UsersForm mode="edit" :user="props.user" :available-roles="props.availableRoles" />
    </AppLayout>
</template>
