<script setup>
import { Head } from '@inertiajs/vue3';
import UserForm from '@/components/users/UserForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: '/dashboard' },
    { label: '用户管理', href: '/users' },
    { label: '编辑用户' },
];
</script>

<template>
    <Head :title="`编辑 ${props.user.name}`" />

    <AppLayout
        title="编辑用户"
        description="编辑基础资料；若修改邮箱，系统会重置验证状态并重新发送验证邮件。"
        :breadcrumbs="breadcrumbs"
    >
        <UserForm mode="edit" :user="props.user" />
    </AppLayout>
</template>
