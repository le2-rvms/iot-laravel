<script setup>
import { Head } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

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
    title: '系统配置',
    index_route: 'system-configs.index',
    store_route: 'system-configs.store',
    update_route: 'system-configs.update',
    edit_route: 'system-configs.edit',
    destroy_route: 'system-configs.destroy',
};

const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: resource.title, href: route(resource.index_route) },
    { label: '编辑配置项' },
];
</script>

<template>
    <Head :title="`编辑${resource.title}`" />

    <AppLayout
        :title="`编辑${resource.title}`"
        description="更新配置键、配置值、打码显示与备注说明。"
        :breadcrumbs="breadcrumbs"
    >
        <SettingsConfigForm mode="edit" :resource="resource" :config="config" />
    </AppLayout>
</template>
