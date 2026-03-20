<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { PencilLine, Trash2 } from 'lucide-vue-next';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

defineProps({
    roles: {
        type: Object,
        required: true,
    },
});

const confirmDialog = useConfirmDialog();
const page = usePage();
const canWrite = computed(() => page.props.auth?.access?.['role.write'] ?? false);

function confirmDelete(role) {
    confirmDialog.open({
        title: '删除角色',
        description: `确定删除角色 ${role.name} 吗？`,
        confirmLabel: '确认删除',
        cancelLabel: '取消',
        variant: 'destructive',
        onConfirm: () => {
            router.delete(`/roles/${role.id}`, {
                preserveScroll: true,
            });
        },
    });
}
</script>

<template>
    <UiTable>
        <UiTableHeader>
            <UiTableRow>
                <UiTableHead>角色名称</UiTableHead>
                <UiTableHead>权限数</UiTableHead>
                <UiTableHead>绑定用户</UiTableHead>
                <UiTableHead>已选权限</UiTableHead>
                <UiTableHead>创建时间</UiTableHead>
                <UiTableHead class="w-[160px] text-right">操作</UiTableHead>
            </UiTableRow>
        </UiTableHeader>

        <UiTableBody>
            <UiTableRow v-for="role in roles.data" :key="role.id">
                <UiTableCell>
                    <div class="space-y-1">
                        <div class="app-copy-strong flex items-center gap-2 font-medium">
                            <span>{{ role.name }}</span>
                            <UiBadge v-if="role.is_protected" variant="outline">受保护</UiBadge>
                        </div>
                    </div>
                </UiTableCell>
                <UiTableCell>{{ role.permissions_count }}</UiTableCell>
                <UiTableCell>{{ role.users_count }}</UiTableCell>
                <UiTableCell>
                    <div class="flex flex-wrap gap-2">
                        <UiBadge v-for="permission in role.permissions" :key="permission" variant="secondary">
                            {{ permission }}
                        </UiBadge>
                    </div>
                </UiTableCell>
                <UiTableCell>{{ role.created_at?.slice(0, 10) }}</UiTableCell>
                <UiTableCell class="text-right">
                    <div v-if="canWrite" class="flex justify-end gap-2">
                        <UiButton as-child variant="outline" size="sm" class="rounded-lg">
                            <Link :href="`/roles/${role.id}/edit`" class="inline-flex items-center gap-2">
                                <PencilLine class="size-4" />
                                编辑
                            </Link>
                        </UiButton>
                        <UiButton
                            variant="outline"
                            size="sm"
                            class="rounded-lg text-red-600 hover:text-red-600"
                            @click="confirmDelete(role)"
                        >
                            <Trash2 class="size-4" />
                            删除
                        </UiButton>
                    </div>
                    <span v-else class="app-copy-muted-soft text-xs">只读</span>
                </UiTableCell>
            </UiTableRow>
        </UiTableBody>
    </UiTable>
</template>
