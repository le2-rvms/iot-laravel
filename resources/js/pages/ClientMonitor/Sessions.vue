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
    sessions: {
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
                <ClientMonitorSessionsFilters :filters="filters" :href="pageMeta.href" />
                <ClientMonitorSessionsTable v-if="sessions.data.length" :sessions="sessions" />

                <div v-else class="p-5">
                    <AppEmptyState
                        :title="hasFilters ? '未找到匹配的在线会话' : '还没有在线会话'"
                        :description="hasFilters ? '调整搜索条件后再试，或清空筛选查看全部在线会话。' : '客户端建立会话后，可在这里查看当前状态与最近事件。'"
                    />
                </div>

                <template v-if="sessions.data.length" #footer>
                    <AppPaginationBar :links="sessions.links" />
                </template>
            </AppDataTableShell>
        </div>
    </AppLayout>
</template>
