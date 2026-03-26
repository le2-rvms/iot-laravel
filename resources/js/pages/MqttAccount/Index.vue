<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { buildRouteQueryHref, route } from '@/lib/routes';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    accounts: {
        type: Object,
        required: true,
    },
});

const page = usePage();
// 页面级按钮仍按权限收口，避免只靠路由保护导致用户先看到再点进 403。
const canWrite = computed(() => page.props.auth?.access?.['mqtt-account.write'] ?? false);
const hasSearch = computed(() => (props.filters.search__func ?? '').trim() !== '');
const exportHref = computed(() => buildRouteQueryHref('mqtt-accounts.export', props.filters));

// 列表页 breadcrumb 固定收口到 MQTT 账号管理，保持新建/编辑/返回路径一致。
const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: 'MQTT账号管理' },
];
</script>

<template>
    <Head title="MQTT账号管理" />

    <AppLayout
        title="MQTT账号管理"
        description="查看、筛选并维护 MQTT 连接账号。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <AppPageToolbar title="账号列表">
                <template #actions>
                    <UiButton as-child variant="outline" class="rounded-xl">
                        <a :href="exportHref">导出 CSV</a>
                    </UiButton>
                    <UiButton v-if="canWrite" as-child class="rounded-xl">
                        <Link :href="route('mqtt-accounts.create')">新建MQTT账号</Link>
                    </UiButton>
                </template>
            </AppPageToolbar>

            <AppDataTableShell>
                <MqttAccountsFilters :filters="filters" />

                <MqttAccountsTable v-if="accounts.data.length" :accounts="accounts" />

                <div v-else class="p-5">
                    <AppEmptyState
                        :title="hasSearch ? '未找到匹配的MQTT账号' : '还没有MQTT账号'"
                        :description="hasSearch ? '调整搜索条件后再试，或清空关键字查看全部账号。' : '创建第一个 MQTT 账号后，可在这里集中维护连接信息。'"
                        :action-label="!hasSearch && canWrite ? '创建MQTT账号' : ''"
                        :action-href="!hasSearch && canWrite ? route('mqtt-accounts.create') : ''"
                    />
                </div>

                <template v-if="accounts.data.length" #footer>
                    <AppPaginationBar :links="accounts.links" />
                </template>
            </AppDataTableShell>
        </div>
    </AppLayout>
</template>
