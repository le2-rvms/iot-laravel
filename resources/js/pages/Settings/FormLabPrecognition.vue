<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    channelTypes: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: '/dashboard' },
    { label: '系统设置', href: '/settings' },
    { label: 'Precognition 实验室' },
];
</script>

<template>
    <Head title="Precognition 实验室" />

    <AppLayout
        title="Precognition 实验室"
        description="使用 Laravel Precognition + Inertia 演示服务端实时预校验，不依赖 vee-validate 或 yup。"
        :breadcrumbs="breadcrumbs"
    >
        <SettingsPrecognitionRuleForm :channel-types="channelTypes" />
    </AppLayout>
</template>
