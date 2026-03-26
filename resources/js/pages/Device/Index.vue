<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { buildRouteQueryHref, route } from '@/lib/routes';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    devices: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const canWrite = computed(() => page.props.auth?.access?.['device.write'] ?? false);
const hasFilters = computed(() => Object.values(props.filters ?? {}).some((value) => String(value ?? '').trim() !== ''));
const exportHref = computed(() => buildRouteQueryHref('devices.export', props.filters));

const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: '设备管理' },
];
</script>

<template>
    <Head title="设备管理" />

    <AppLayout
        title="设备管理"
        description="查看、筛选并维护设备标识、状态字段与鉴权信息。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <AppPageToolbar title="设备列表">
                <template #actions>
                    <UiButton as-child variant="outline" class="rounded-xl">
                        <a :href="exportHref">导出 CSV</a>
                    </UiButton>
                    <UiButton v-if="canWrite" as-child class="rounded-xl">
                        <Link :href="route('devices.create')">新建设备</Link>
                    </UiButton>
                </template>
            </AppPageToolbar>

            <AppDataTableShell>
                <DevicesFilters :filters="filters" />

                <DevicesTable v-if="devices.data.length" :devices="devices" />

                <div v-else class="p-5">
                    <AppEmptyState
                        :title="hasFilters ? '未找到匹配的设备' : '还没有设备'"
                        :description="hasFilters ? '调整筛选条件后再试，或清空条件查看全部设备。' : '创建设备后，可在这里统一维护设备基础资料、状态与鉴权字段。'"
                        :action-label="!hasFilters && canWrite ? '创建设备' : ''"
                        :action-href="!hasFilters && canWrite ? route('devices.create') : ''"
                    />
                </div>

                <template v-if="devices.data.length" #footer>
                    <AppPaginationBar :links="devices.links" />
                </template>
            </AppDataTableShell>
        </div>
    </AppLayout>
</template>
