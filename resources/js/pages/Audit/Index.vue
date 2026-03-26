<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import { buildRouteQueryHref, route } from '@/lib/routes';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    audits: {
        type: Object,
        required: true,
    },
    eventOptions: {
        type: Array,
        required: true,
    },
    resourceTypeOptions: {
        type: Array,
        required: true,
    },
});

const breadcrumbs = [
    { label: "仪表盘", href: route('dashboard') },
    { label: "审计日志" },
];
const exportHref = computed(() => buildRouteQueryHref('audits.export', props.filters));

const hasFilters = computed(() => {
    return Boolean(
        (props.filters.search__func ?? "").trim()
        || (props.filters.event__eq ?? "").trim()
        || (props.filters.auditable_type__eq ?? "").trim(),
    );
});
</script>

<template>
    <Head title="审计日志" />

    <AppLayout
        title="审计日志"
        description="查看后台资源的创建、更新、删除与业务事件记录。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <AppPageToolbar
                title="审计日志"
                description="支持按操作者、事件类型和资源类型筛选后台写操作记录。"
            >
                <template #actions>
                    <UiButton as-child variant="outline" class="rounded-xl">
                        <a :href="exportHref">导出 CSV</a>
                    </UiButton>
                </template>
            </AppPageToolbar>

            <AppDataTableShell>
                <AuditsFilters
                    :filters="filters"
                    :event-options="eventOptions"
                    :resource-type-options="resourceTypeOptions"
                />

                <AuditsTable v-if="audits.data.length" :audits="audits" />

                <div v-else class="p-5">
                    <AppEmptyState
                        :title="hasFilters ? '未找到匹配的审计日志' : '还没有审计日志'"
                        :description="hasFilters ? '调整搜索条件后再试，或清空筛选查看全部日志。' : '后台资源发生创建、更新、删除或业务事件后，会在这里留下记录。'"
                    />
                </div>

                <template v-if="audits.data.length" #footer>
                    <AppPaginationBar :links="audits.links" />
                </template>
            </AppDataTableShell>
        </div>
    </AppLayout>
</template>
