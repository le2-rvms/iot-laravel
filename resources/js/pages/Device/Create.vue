<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    device: {
        type: Object,
        required: true,
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: '/admin/dashboard' },
    { label: '设备管理', href: '/admin/devices' },
    { label: '新建设备' },
];
</script>

<template>
    <Head title="新建设备" />

    <AppLayout
        title="新建设备"
        description="维护设备基础信息、状态字段与鉴权信息。"
        :breadcrumbs="breadcrumbs"
    >
        <DevicesForm mode="create" :device="device" />
    </AppLayout>
</template>
