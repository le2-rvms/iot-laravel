<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { buildRouteQueryHref, route } from '@/lib/routes';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    products: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const canWrite = computed(() => page.props.auth?.access?.['device-product.write'] ?? false);
const hasSearch = computed(() => (props.filters.search__func ?? '').trim() !== '');
const exportHref = computed(() => buildRouteQueryHref('device-products.export', props.filters));

const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: '设备产品' },
];
</script>

<template>
    <Head title="设备产品" />

    <AppLayout
        title="设备产品"
        description="查看、筛选并维护设备产品标识、名称与协议分类信息。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <AppPageToolbar title="产品列表" description="支持按产品标识、名称、厂商和协议快速查找。">
                <template #actions>
                    <UiButton as-child variant="outline" class="rounded-xl">
                        <a :href="exportHref">导出 CSV</a>
                    </UiButton>
                    <UiButton v-if="canWrite" as-child class="rounded-xl">
                        <Link :href="route('device-products.create')">新建设备产品</Link>
                    </UiButton>
                </template>
            </AppPageToolbar>

            <AppDataTableShell>
                <DeviceProductsFilters :filters="filters" />

                <DeviceProductsTable v-if="products.data.length" :products="products" />

                <div v-else class="p-5">
                    <AppEmptyState
                        :title="hasSearch ? '未找到匹配的设备产品' : '还没有设备产品'"
                        :description="hasSearch ? '调整搜索条件后再试，或清空关键字查看全部设备产品。' : '创建设备产品后，可在这里集中维护产品标识、协议与关联情况。'"
                        :action-label="!hasSearch && canWrite ? '创建设备产品' : ''"
                        :action-href="!hasSearch && canWrite ? route('device-products.create') : ''"
                    />
                </div>

                <template v-if="products.data.length" #footer>
                    <AppPaginationBar :links="products.links" />
                </template>
            </AppDataTableShell>
        </div>
    </AppLayout>
</template>
