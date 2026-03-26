<script setup>
import { Head } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

defineProps({
    availableRoles: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: '管理员用户', href: route('admin-users.index') },
    { label: '新建管理员用户' },
];
</script>

<template>
    <Head title="新建管理员用户" />

    <AppLayout
        title="新建管理员用户"
        description="创建成功后立即发送验证邮件，保持首版邮箱验证链路闭合。"
        :breadcrumbs="breadcrumbs"
    >
        <UsersForm mode="create" :available-roles="availableRoles" />
    </AppLayout>
</template>
