<script setup>
import { Head } from '@inertiajs/vue3';

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
    { label: '仪表盘', href: '/dashboard' },
    { label: '角色权限', href: '/roles' },
    { label: '编辑角色' },
];
</script>

<template>
    <Head :title="`编辑角色 ${role.name}`" />

    <AppLayout
        title="编辑角色"
        description="维护角色名称与读写权限。受保护角色会锁定关键字段。"
        :breadcrumbs="breadcrumbs"
    >
        <RolesForm mode="edit" :role="role" :permission-groups="permissionGroups" />
    </AppLayout>
</template>
