<script setup>
import { Link, router } from '@inertiajs/vue3';
import { MoreHorizontal, PencilLine, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { usePage } from '@inertiajs/vue3';

defineProps({
    users: {
        type: Object,
        required: true,
    },
});

const confirmDialog = useConfirmDialog();
const page = usePage();
const canWrite = computed(() => page.props.auth?.access?.['users.write'] ?? false);

function confirmDelete(user) {
    confirmDialog.open({
        title: '删除用户',
        description: `确定删除 ${user.name} 吗？此操作不可撤销。`,
        confirmLabel: '确认删除',
        cancelLabel: '取消',
        variant: 'destructive',
        onConfirm: () => {
            router.delete(`/users/${user.id}`, {
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
                <UiTableHead class="w-[28%]">用户</UiTableHead>
                <UiTableHead>邮箱</UiTableHead>
                <UiTableHead>角色</UiTableHead>
                <UiTableHead class="w-[18%]">验证状态</UiTableHead>
                <UiTableHead class="w-[20%]">创建时间</UiTableHead>
                <UiTableHead class="w-[72px] text-right">操作</UiTableHead>
            </UiTableRow>
        </UiTableHeader>

        <UiTableBody>
            <UiTableRow v-for="user in users.data" :key="user.id">
                <UiTableCell>
                    <div class="space-y-1">
                        <div class="font-medium text-slate-950">{{ user.name }}</div>
                        <div class="text-xs text-slate-500">ID: {{ user.id }}</div>
                    </div>
                </UiTableCell>
                <UiTableCell class="text-slate-600">{{ user.email }}</UiTableCell>
                <UiTableCell>
                    <div class="flex flex-wrap gap-2">
                        <UiBadge
                            v-for="role in user.roles"
                            :key="role"
                            variant="outline"
                        >
                            {{ role }}
                        </UiBadge>
                        <span v-if="!user.roles.length" class="text-xs text-slate-400">未分配角色</span>
                    </div>
                </UiTableCell>
                <UiTableCell>
                    <UiBadge :variant="user.email_verified_at ? 'default' : 'secondary'">
                        {{ user.email_verified_at ? '已验证' : '待验证' }}
                    </UiBadge>
                </UiTableCell>
                <UiTableCell class="text-slate-600">
                    {{ user.created_at?.slice(0, 10) }}
                </UiTableCell>
                <UiTableCell class="text-right">
                    <UiDropdownMenu v-if="canWrite">
                        <UiDropdownMenuTrigger as-child>
                            <UiButton variant="ghost" size="icon" class="rounded-xl">
                                <MoreHorizontal class="size-4" />
                            </UiButton>
                        </UiDropdownMenuTrigger>
                        <UiDropdownMenuContent align="end">
                            <UiDropdownMenuItem as-child>
                                <Link :href="`/users/${user.id}/edit`" class="flex cursor-pointer items-center">
                                    <PencilLine class="mr-2 size-4" />
                                    编辑
                                </Link>
                            </UiDropdownMenuItem>
                            <UiDropdownMenuItem class="text-red-600 focus:text-red-600" @select.prevent="confirmDelete(user)">
                                <Trash2 class="mr-2 size-4" />
                                删除
                            </UiDropdownMenuItem>
                        </UiDropdownMenuContent>
                    </UiDropdownMenu>
                    <span v-else class="text-xs text-slate-400">只读</span>
                </UiTableCell>
            </UiTableRow>
        </UiTableBody>
    </UiTable>
</template>
