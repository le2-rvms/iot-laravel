<script setup>
import { Head } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

defineProps({
    role: {
        type: Object,
        required: true,
    },
    permissionGroups: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: '管理员角色', href: route('admin-roles.index') },
    { label: '编辑管理员角色' },
];
</script>

<template>
    <Head :title="`编辑管理员角色 ${role.name}`" />

    <AppLayout
        title="编辑管理员角色"
        description="维护管理员角色名称与读写权限。受保护角色会锁定关键字段。"
        :breadcrumbs="breadcrumbs"
    >
        <RolesForm mode="edit" :role="role" :permission-groups="permissionGroups" />
    </AppLayout>
</template>
