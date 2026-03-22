<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { resolveConfigResource } from './resource';

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
const resource = computed(() => resolveConfigResource(props.category));
const canWrite = computed(() => page.props.auth?.access?.[resource.value.write_permission] ?? false);
const hasSearch = computed(() => (props.filters.search__func ?? '').trim() !== '');
const breadcrumbs = computed(() => [
    { label: '仪表盘', href: '/admin/dashboard' },
    { label: resource.value.title },
]);
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
                <template #actions v-if="canWrite">
                    <UiButton as-child class="rounded-xl">
                        <Link :href="resource.create_href">新建配置项</Link>
                    </UiButton>
                </template>
            </AppPageToolbar>

            <AppDataTableShell>
                <SettingsConfigFilters :filters="filters" :index-href="resource.index_href" />

                <SettingsConfigTable v-if="configs.data.length" :configs="configs" :resource="resource" />

                <AppEmptyState
                    v-else
                    :title="hasSearch ? '未找到匹配的配置项' : `还没有${resource.title}`"
                    :description="hasSearch ? '调整搜索条件后再试，或清空关键字查看全部配置项。' : '创建第一个配置项后，可在这里集中查看和维护。'"
                    :action-label="!hasSearch && canWrite ? '创建配置项' : ''"
                    :action-href="!hasSearch && canWrite ? resource.create_href : ''"
                />

                <template v-if="configs.data.length" #footer>
                    <AppPaginationBar :links="configs.links" />
                </template>
            </AppDataTableShell>
        </div>
    </AppLayout>
</template>
