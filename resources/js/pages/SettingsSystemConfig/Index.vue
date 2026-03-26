<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { buildRouteQueryHref, route } from '@/lib/routes';

const props = defineProps({
    category: {
        type: String,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    configs: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const resource = {
    title: '系统配置',
    description: '维护系统层的公共设定、展示策略与后台说明。',
    index_route: 'system-configs.index',
    export_route: 'system-configs.export',
    create_route: 'system-configs.create',
    store_route: 'system-configs.store',
    edit_route: 'system-configs.edit',
    update_route: 'system-configs.update',
    destroy_route: 'system-configs.destroy',
    write_permission: 'settings-system-config.write',
};
const canWrite = computed(() => page.props.auth?.access?.[resource.write_permission] ?? false);
const hasSearch = computed(() => (props.filters.search__func ?? '').trim() !== '');
const exportHref = computed(() => buildRouteQueryHref(resource.export_route, props.filters));
const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: resource.title },
];
</script>

<template>
    <Head :title="resource.title" />

    <AppLayout
        :title="resource.title"
        :description="resource.description"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <AppPageToolbar :title="`${resource.title}列表`" description="可按关键字查找，并直接维护配置项。">
                <template #actions>
                    <UiButton as-child variant="outline" class="rounded-xl">
                        <a :href="exportHref">导出 CSV</a>
                    </UiButton>
                    <UiButton v-if="canWrite" as-child class="rounded-xl">
                        <Link :href="route(resource.create_route)">新建配置项</Link>
                    </UiButton>
                </template>
            </AppPageToolbar>

            <AppDataTableShell>
                <SettingsConfigFilters :filters="filters" :index-route="resource.index_route" />

                <SettingsConfigTable v-if="configs.data.length" :configs="configs" :resource="resource" />

                <div v-else class="p-5">
                    <AppEmptyState
                        :title="hasSearch ? '未找到匹配的配置项' : `还没有${resource.title}`"
                        :description="hasSearch ? '调整搜索条件后再试，或清空关键字查看全部配置项。' : '创建第一个配置项后，可在这里集中查看和维护。'"
                        :action-label="!hasSearch && canWrite ? '创建配置项' : ''"
                        :action-href="!hasSearch && canWrite ? route(resource.create_route) : ''"
                    />
                </div>

                <template v-if="configs.data.length" #footer>
                    <AppPaginationBar :links="configs.links" />
                </template>
            </AppDataTableShell>
        </div>
    </AppLayout>
</template>
