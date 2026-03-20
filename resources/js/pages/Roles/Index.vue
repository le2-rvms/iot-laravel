<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    roles: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const canWrite = computed(() => page.props.auth?.access?.['role.write'] ?? false);

const breadcrumbs = [
    { label: '仪表盘', href: '/dashboard' },
    { label: '角色权限' },
];
</script>

<template>
    <Head title="角色权限" />

    <AppLayout
        title="角色权限"
        description="为不同岗位配置可访问的系统功能。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <AppPageToolbar title="角色列表" description="查看现有角色，并按职责调整可用功能范围。">
                <template #actions v-if="canWrite">
                    <UiButton as-child class="rounded-xl">
                        <Link href="/roles/create">新建角色</Link>
                    </UiButton>
                </template>
            </AppPageToolbar>

            <AppDataTableShell v-if="roles.data.length">
                <RolesTable :roles="roles" />
                <template #footer>
                    <AppPaginationBar :links="roles.links" />
                </template>
            </AppDataTableShell>

            <AppEmptyState
                v-else
                title="还没有角色"
                description="先创建角色，再分配给需要使用后台的人员。"
                :action-label="canWrite ? '创建角色' : ''"
                :action-href="canWrite ? '/roles/create' : ''"
            />
        </div>
    </AppLayout>
</template>
