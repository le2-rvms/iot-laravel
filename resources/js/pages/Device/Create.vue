<script setup>
import { Head } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

defineProps({
    device: {
        type: Object,
        required: true,
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: '设备管理', href: route('devices.index') },
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
