<script setup>
import { Head } from '@inertiajs/vue3';
import UserForm from '@/components/users/UserForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps({
    availableRoles: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: '/dashboard' },
    { label: '用户管理', href: '/users' },
    { label: '新建用户' },
];
</script>

<template>
    <Head title="新建用户" />

    <AppLayout
        title="新建用户"
        description="创建成功后立即发送验证邮件，保持首版邮箱验证链路闭合。"
        :breadcrumbs="breadcrumbs"
    >
        <UserForm mode="create" :available-roles="availableRoles" />
    </AppLayout>
</template>
