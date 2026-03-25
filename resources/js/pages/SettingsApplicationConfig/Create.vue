<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    category: {
        type: String,
        required: true,
    },
    config: {
        type: Object,
        required: true,
    },
});

const resource = {
    title: '应用配置',
    index_href: '/admin/settings/application-configs',
};

const breadcrumbs = [
    { label: '仪表盘', href: '/admin/dashboard' },
    { label: resource.title, href: resource.index_href },
    { label: '新建配置项' },
];
</script>

<template>
    <Head :title="`新建${resource.title}`" />

    <AppLayout
        :title="`新建${resource.title}`"
        description="用统一的配置项模型维护后台可编辑设置。"
        :breadcrumbs="breadcrumbs"
    >
        <SettingsConfigForm mode="create" :resource="resource" :config="config" />
    </AppLayout>
</template>
