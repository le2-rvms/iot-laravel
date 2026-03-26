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
