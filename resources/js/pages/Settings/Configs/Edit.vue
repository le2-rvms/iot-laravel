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
    { label: '编辑配置项' },
]);
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
