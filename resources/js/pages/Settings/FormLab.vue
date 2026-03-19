<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    channelTypes: {
        type: Array,
        default: () => [],
    },
    triggerModes: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: '/dashboard' },
    { label: '系统设置', href: '/settings' },
    { label: '复杂表单实验室' },
];
</script>

<template>
    <Head title="复杂表单实验室" />

    <AppLayout
        title="复杂表单实验室"
        description="使用 vee-validate + yup + Inertia 演示复杂表单的标准实现方式。"
        :breadcrumbs="breadcrumbs"
    >
        <SettingsNotificationRuleForm :channel-types="channelTypes" :trigger-modes="triggerModes" />
    </AppLayout>
</template>
