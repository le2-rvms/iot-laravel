<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    sections: {
        type: Array,
        required: true,
    },
    pageMeta: {
        type: Object,
        required: true,
    },
    deviceContext: {
        type: Object,
        default: null,
    },
    connEvents: {
        type: Object,
        required: true,
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: '/admin/dashboard' },
    { label: '客户端监控', href: props.deviceContext?.rootHref ?? props.pageMeta.monitorHref },
    { label: props.pageMeta.title },
];

const hasFilters = computed(() => Object.values(props.filters ?? {}).some((value) => String(value ?? '').trim() !== ''));
</script>

<template>
    <Head :title="pageMeta.title" />

    <AppLayout
        :title="pageMeta.title"
        :description="pageMeta.description"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <AppPageToolbar :title="pageMeta.title" :description="pageMeta.description" />

            <ClientMonitorTabs :sections="sections" />
            <ClientMonitorDeviceContextBanner :device-context="deviceContext" :client-id="filters.client_id__eq" />

            <AppDataTableShell>
                <ClientMonitorConnEventsFilters :filters="filters" :href="pageMeta.href" />
                <ClientMonitorConnEventsTable v-if="connEvents.data.length" :conn-events="connEvents" />

                <div v-else class="p-5">
                    <AppEmptyState
                        :title="hasFilters ? '未找到匹配的连接事件' : '还没有连接事件'"
                        :description="hasFilters ? '调整搜索条件后再试，或清空筛选查看全部连接事件。' : '客户端发生连接或断开后，可在这里查看事件流和附加上下文。'"
                    />
                </div>

                <template v-if="connEvents.data.length" #footer>
                    <AppPaginationBar :links="connEvents.links" />
                </template>
            </AppDataTableShell>
        </div>
    </AppLayout>
</template>
