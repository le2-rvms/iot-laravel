<script setup>
import { Head } from '@inertiajs/vue3';
import RoleForm from '@/components/roles/RoleForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps({
    permissionGroups: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: '/dashboard' },
    { label: '角色权限', href: '/roles' },
    { label: '新建角色' },
];
</script>

<template>
    <Head title="新建角色" />

    <AppLayout
        title="新建角色"
        description="角色决定模块的读写权限，用户通过角色获得后台访问能力。"
        :breadcrumbs="breadcrumbs"
    >
        <RoleForm mode="create" :permission-groups="permissionGroups" />
    </AppLayout>
</template>
