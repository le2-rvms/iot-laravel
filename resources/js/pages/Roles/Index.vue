<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import DataTableShell from '@/components/app/DataTableShell.vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageToolbar from '@/components/app/PageToolbar.vue';
import PaginationBar from '@/components/app/PaginationBar.vue';
import RolesTable from '@/components/roles/RolesTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps({
    roles: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const canWrite = computed(() => page.props.auth?.access?.['roles.write'] ?? false);

const breadcrumbs = [
    { label: '仪表盘', href: '/dashboard' },
    { label: '角色权限' },
];
</script>

<template>
    <Head title="角色权限" />

    <AppLayout
        title="角色权限"
        description="角色是权限集合；首版统一使用 read / write 两类权限点。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <PageToolbar title="角色列表" description="创建角色并维护模块级读写权限。">
                <template #actions v-if="canWrite">
                    <UiButton as-child class="rounded-xl">
                        <Link href="/roles/create">新建角色</Link>
                    </UiButton>
                </template>
            </PageToolbar>

            <DataTableShell v-if="roles.data.length">
                <RolesTable :roles="roles" />
                <template #footer>
                    <PaginationBar :links="roles.links" />
                </template>
            </DataTableShell>

            <EmptyState
                v-else
                title="还没有角色"
                description="先创建角色，再把角色分配给后台用户。"
                :action-label="canWrite ? '创建角色' : ''"
                :action-href="canWrite ? '/roles/create' : ''"
            />
        </div>
    </AppLayout>
</template>
