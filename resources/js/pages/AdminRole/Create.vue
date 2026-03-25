<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    permissionGroups: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: '/admin/dashboard' },
    { label: '管理员角色', href: '/admin/admin-roles' },
    { label: '新建管理员角色' },
];
</script>

<template>
    <Head title="新建管理员角色" />

    <AppLayout
        title="新建管理员角色"
        description="角色决定模块的读写权限，用户通过角色获得后台访问能力。"
        :breadcrumbs="breadcrumbs"
    >
        <RolesForm mode="create" :permission-groups="permissionGroups" />
    </AppLayout>
</template>
