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
    { label: 'Precognition 实验室' },
];
</script>

<template>
    <Head title="Precognition 实验室" />

    <AppLayout
        title="Precognition 实验室"
        description="用于体验填写过程中的实时校验反馈。"
        :breadcrumbs="breadcrumbs"
    >
        <SettingsPrecognitionRuleForm :channel-types="channelTypes" />
    </AppLayout>
</template>
