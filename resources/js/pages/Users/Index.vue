<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
const canWrite = computed(() => page.props.auth?.access?.['user.write'] ?? false);

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
            <AppPageToolbar title="用户列表" description="搜索、分页和操作都通过 Inertia 与 Laravel 控制器协同完成。">
                <template #actions v-if="canWrite">
                    <UiButton as-child class="rounded-xl">
                        <Link href="/users/create">新建用户</Link>
                    </UiButton>
                </template>
            </AppPageToolbar>

            <UsersUserFilters :filters="filters" />

            <AppDataTableShell v-if="users.data.length">
                <UsersTable :users="users" />
                <template #footer>
                    <AppPaginationBar :links="users.links" />
                </template>
            </AppDataTableShell>

            <AppEmptyState
                v-else
                title="还没有用户数据"
                description="当前列表为空。创建首个后台用户后，这里会展示标准 CRUD 列表结构。"
                :action-label="canWrite ? '创建用户' : ''"
                :action-href="canWrite ? '/users/create' : ''"
            />
        </div>
    </AppLayout>
</template>
