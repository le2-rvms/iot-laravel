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
