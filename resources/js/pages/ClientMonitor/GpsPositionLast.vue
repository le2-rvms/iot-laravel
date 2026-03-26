<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { hrefForRouteTarget, route } from '@/lib/routes';

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
    gpsPositionLast: {
        type: Object,
        required: true,
    },
});

const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: '客户端监控', href: hrefForRouteTarget(props.deviceContext ?? props.pageMeta.monitorRoute) },
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
                <ClientMonitorGpsPositionLastFilters :filters="filters" :route-target="pageMeta" />
                <ClientMonitorGpsPositionLastTable v-if="gpsPositionLast.data.length" :gps-position-last="gpsPositionLast" />

                <div v-else class="p-5">
                    <AppEmptyState
                        :title="hasFilters ? '未找到匹配的当前定位' : '还没有当前定位'"
                        :description="hasFilters ? '调整搜索条件后再试，或清空筛选查看全部当前定位。' : '终端上报当前定位后，可在这里查看最新坐标、状态和附加信息。'"
                    />
                </div>

                <template v-if="gpsPositionLast.data.length" #footer>
                    <AppPaginationBar :links="gpsPositionLast.links" />
                </template>
            </AppDataTableShell>
        </div>
    </AppLayout>
</template>
