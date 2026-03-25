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
    { label: '编辑设备' },
];
</script>

<template>
    <Head :title="`编辑设备 ${device.dev_name}`" />

    <AppLayout
        title="编辑设备"
        description="更新设备基础资料、状态字段与鉴权信息，终端 ID 创建后不可修改。"
        :breadcrumbs="breadcrumbs"
    >
        <DevicesForm mode="edit" :device="device" />
    </AppLayout>
</template>
