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
    { label: '编辑设备产品' },
];
</script>

<template>
    <Head :title="`编辑设备产品 ${product.product_name}`" />

    <AppLayout
        title="编辑设备产品"
        description="更新产品名称、厂商、协议分类等信息，产品标识创建后不可修改。"
        :breadcrumbs="breadcrumbs"
    >
        <DeviceProductsForm mode="edit" :product="product" />
    </AppLayout>
</template>
