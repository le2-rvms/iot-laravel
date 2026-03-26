<script setup>
import { Head } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

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
    { label: '仪表盘', href: route('dashboard') },
    { label: '复杂表单实验室' },
];
</script>

<template>
    <Head title="复杂表单实验室" />

    <AppLayout
        title="复杂表单实验室"
        description="用于演练通知规则的填写流程。"
        :breadcrumbs="breadcrumbs"
    >
        <SettingsNotificationRuleForm :channel-types="channelTypes" :trigger-modes="triggerModes" />
    </AppLayout>
</template>
