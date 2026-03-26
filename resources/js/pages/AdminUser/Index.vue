<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { buildRouteQueryHref, route } from '@/lib/routes';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    users: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const canWrite = computed(() => page.props.auth?.access?.['admin-user.write'] ?? false);
const exportHref = computed(() => buildRouteQueryHref('admin-users.export', props.filters));

const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: '管理员用户' },
];
</script>

<template>
    <Head title="管理员用户" />

    <AppLayout
        title="管理员用户"
        description="查看、筛选并维护后台管理员用户资料。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <AppPageToolbar title="管理员用户列表">
                <template #actions>
                    <UiButton as-child variant="outline" class="rounded-xl">
                        <a :href="exportHref">导出 CSV</a>
                    </UiButton>
                    <UiButton v-if="canWrite" as-child class="rounded-xl">
                        <Link :href="route('admin-users.create')">新建管理员用户</Link>
                    </UiButton>
                </template>
            </AppPageToolbar>

            <AppDataTableShell v-if="users.data.length">
                <UsersUserFilters :filters="filters" />
                <UsersTable :users="users" />
                <template #footer>
                    <AppPaginationBar :links="users.links" />
                </template>
            </AppDataTableShell>

            <AppEmptyState
                v-else
                title="还没有管理员用户数据"
                description="创建第一个后台管理员用户后，可在这里查看和维护账号信息。"
                :action-label="canWrite ? '创建管理员用户' : ''"
                :action-href="canWrite ? route('admin-users.create') : ''"
            />
        </div>
    </AppLayout>
</template>
