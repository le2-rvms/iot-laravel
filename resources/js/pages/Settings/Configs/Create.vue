<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { resolveConfigResource } from './resource';

const props = defineProps({
    category: {
        type: String,
        required: true,
    },
    config: {
        type: Object,
        required: true,
    },
});

const resource = computed(() => resolveConfigResource(props.category));

const breadcrumbs = computed(() => [
    { label: '仪表盘', href: '/admin/dashboard' },
    { label: resource.value.title, href: resource.value.index_href },
    { label: '新建配置项' },
]);
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
