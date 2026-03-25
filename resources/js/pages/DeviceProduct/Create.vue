<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: '/admin/dashboard' },
    { label: '设备产品', href: '/admin/device-products' },
    { label: '新建设备产品' },
];
</script>

<template>
    <Head title="新建设备产品" />

    <AppLayout
        title="新建设备产品"
        description="维护产品标识、产品名称与协议分类信息。"
        :breadcrumbs="breadcrumbs"
    >
        <DeviceProductsForm mode="create" :product="product" />
    </AppLayout>
</template>
