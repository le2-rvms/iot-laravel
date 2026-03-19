<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTableShell from '@/components/app/DataTableShell.vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageToolbar from '@/components/app/PageToolbar.vue';
import PaginationBar from '@/components/app/PaginationBar.vue';
import UserFilters from '@/components/users/UserFilters.vue';
import UsersTable from '@/components/users/UsersTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps({
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
const canWrite = computed(() => page.props.auth?.access?.['users.write'] ?? false);

const breadcrumbs = [
    { label: '仪表盘', href: '/dashboard' },
    { label: '用户管理' },
];
</script>

<template>
    <Head title="用户管理" />

    <AppLayout
        title="用户管理"
        description="列表页采用工具栏、表格、分页与操作区的统一结构。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <PageToolbar title="用户列表" description="搜索、分页和操作都通过 Inertia 与 Laravel 控制器协同完成。">
                <template #actions v-if="canWrite">
                    <UiButton as-child class="rounded-xl">
                        <Link href="/users/create">新建用户</Link>
                    </UiButton>
                </template>
            </PageToolbar>

            <UserFilters :filters="filters" />

            <DataTableShell v-if="users.data.length">
                <UsersTable :users="users" />
                <template #footer>
                    <PaginationBar :links="users.links" />
                </template>
            </DataTableShell>

            <EmptyState
                v-else
                title="还没有用户数据"
                description="当前列表为空。创建首个后台用户后，这里会展示标准 CRUD 列表结构。"
                :action-label="canWrite ? '创建用户' : ''"
                :action-href="canWrite ? '/users/create' : ''"
            />
        </div>
    </AppLayout>
</template>
