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
    gpsPositionHistories: {
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
                <ClientMonitorGpsPositionHistoriesFilters :filters="filters" :href="pageMeta.href" />
                <ClientMonitorGpsPositionHistoriesTable
                    v-if="gpsPositionHistories.data.length"
                    :gps-position-histories="gpsPositionHistories"
                />

                <div v-else class="p-5">
                    <AppEmptyState
                        :title="hasFilters ? '未找到匹配的定位历史' : '还没有定位历史'"
                        :description="hasFilters ? '调整搜索条件后再试，或清空筛选查看全部定位历史。' : '终端持续上报定位后，可在这里查看历史记录与状态变化。'"
                    />
                </div>

                <template v-if="gpsPositionHistories.data.length" #footer>
                    <AppPaginationBar :links="gpsPositionHistories.links" />
                </template>
            </AppDataTableShell>
        </div>
    </AppLayout>
</template>
