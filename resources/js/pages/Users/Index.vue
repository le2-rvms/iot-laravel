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
        description="查看、筛选并维护后台用户资料。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <AppPageToolbar title="用户列表" description="支持按姓名或邮箱筛选，并可继续新增用户。">
                <template #actions v-if="canWrite">
                    <UiButton as-child class="rounded-xl">
                        <Link href="/users/create">新建用户</Link>
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
                title="还没有用户数据"
                description="创建第一个后台用户后，可在这里查看和维护账号信息。"
                :action-label="canWrite ? '创建用户' : ''"
                :action-href="canWrite ? '/users/create' : ''"
            />
        </div>
    </AppLayout>
</template>
