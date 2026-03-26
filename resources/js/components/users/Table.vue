<script setup>
import { Link, router } from '@inertiajs/vue3';
import { PencilLine, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { usePage } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

defineProps({
    users: {
        type: Object,
        required: true,
    },
});

const confirmDialog = useConfirmDialog();
const page = usePage();
const canWrite = computed(() => page.props.auth?.access?.['admin-user.write'] ?? false);

function confirmDelete(user) {
    confirmDialog.open({
        title: '删除管理员用户',
        description: `确定删除 ${user.name} 吗？此操作不可撤销。`,
        confirmLabel: '确认删除',
        cancelLabel: '取消',
        variant: 'destructive',
        onConfirm: () => {
            router.delete(route('admin-users.destroy', user), {
                preserveScroll: true,
                only: ['users', 'filters', 'flash'],
            });
        },
    });
}
</script>

<template>
    <UiTable>
        <UiTableHeader>
            <UiTableRow>
                <UiTableHead class="w-[28%]">管理员用户</UiTableHead>
                <UiTableHead>邮箱</UiTableHead>
                <UiTableHead>管理员角色</UiTableHead>
                <UiTableHead class="w-[18%]">验证状态</UiTableHead>
                <UiTableHead class="w-[20%]">创建时间</UiTableHead>
                <UiTableHead class="w-[160px] text-center">操作</UiTableHead>
            </UiTableRow>
        </UiTableHeader>

        <UiTableBody>
            <UiTableRow v-for="user in users.data" :key="user.id">
                <UiTableCell>
                    <div class="space-y-1">
                        <div class="app-copy-strong font-medium">{{ user.name }}</div>
                        <div class="app-copy-muted text-xs">ID: {{ user.id }}</div>
                    </div>
                </UiTableCell>
                <UiTableCell class="app-copy-muted">{{ user.email }}</UiTableCell>
                <UiTableCell>
                    <div class="flex flex-wrap gap-2">
                        <UiBadge
                            v-for="role in user.roles"
                            :key="role.name"
                            variant="outline"
                        >
                            {{ role.name }}
                        </UiBadge>
                        <span v-if="!user.roles.length" class="app-copy-muted-soft text-xs">未分配管理员角色</span>
                    </div>
                </UiTableCell>
                <UiTableCell>
                    <UiBadge :variant="user.email_verified_at ? 'default' : 'secondary'">
                        {{ user.email_verified_at ? '已验证' : '待验证' }}
                    </UiBadge>
                </UiTableCell>
                <UiTableCell class="app-copy-muted">
                    {{ user.created_at?.slice(0, 10) }}
                </UiTableCell>
                <UiTableCell class="text-center">
                    <div v-if="canWrite" class="flex justify-center gap-2">
                        <UiButton as-child variant="outline" size="sm" class="rounded-lg">
                            <Link :href="route('admin-users.edit', user)" class="inline-flex items-center gap-2">
                                <PencilLine class="size-4" />
                                编辑
                            </Link>
                        </UiButton>
                        <UiButton
                            variant="outline"
                            size="sm"
                            class="rounded-lg text-red-600 hover:text-red-600"
                            @click="confirmDelete(user)"
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
