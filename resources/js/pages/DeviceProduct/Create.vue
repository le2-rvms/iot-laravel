<script setup>
import { Head } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: '设备产品', href: route('device-products.index') },
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
