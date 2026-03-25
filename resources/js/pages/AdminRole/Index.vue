<script setup>
import { computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";

defineProps({
    roles: {
        type: Object,
        required: true,
    },
    permissionDisplayNames: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const canWrite = computed(
    () => page.props.auth?.access?.["admin-role.write"] ?? false,
);

const breadcrumbs = [
    { label: "仪表盘", href: "/admin/dashboard" },
    { label: "管理员角色" },
];
</script>

<template>
    <Head title="管理员角色" />

    <AppLayout
        title="管理员角色"
        description="为不同岗位配置可访问的系统功能。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <AppPageToolbar
                title="管理员角色列表"
                description="查看现有角色，并按职责调整可用功能范围。"
            >
                <template #actions>
                    <UiButton as-child variant="outline" class="rounded-xl">
                        <a href="/admin/admin-roles/export">导出 CSV</a>
                    </UiButton>
                    <UiButton v-if="canWrite" as-child class="rounded-xl">
                        <Link href="/admin/admin-roles/create"
                            >新建管理员角色</Link
                        >
                    </UiButton>
                </template>
            </AppPageToolbar>

            <AppDataTableShell v-if="roles.data.length">
                <RolesTable
                    :roles="roles"
                    :permission-display-names="permissionDisplayNames"
                />
                <template #footer>
                    <AppPaginationBar :links="roles.links" />
                </template>
            </AppDataTableShell>

            <AppEmptyState
                v-else
                title="还没有管理员角色"
                description="先创建管理员角色，再分配给需要使用后台的人员。"
                :action-label="canWrite ? '创建管理员角色' : ''"
                :action-href="canWrite ? '/admin/admin-roles/create' : ''"
            />
        </div>
    </AppLayout>
</template>
