<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { PencilLine, Trash2 } from 'lucide-vue-next';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { route } from '@/lib/routes';

defineProps({
    accounts: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const confirmDialog = useConfirmDialog();
const canWrite = computed(() => page.props.auth?.access?.['mqtt-account.write'] ?? false);

function accountRouteParams(account) {
    return { mqtt_account: account.act_id };
}

function confirmDelete(account) {
        confirmDialog.open({
        title: '删除MQTT账号',
        description: `确定删除账号 ${account.user_name} 吗？此操作不可撤销。`,
        confirmLabel: '确认删除',
        cancelLabel: '取消',
        variant: 'destructive',
        onConfirm: () => {
            router.delete(route('mqtt-accounts.destroy', accountRouteParams(account)), {
                preserveScroll: true,
                // 删除后只回拉列表、筛选和提示，避免整页重新加载打断当前操作上下文。
                only: ['accounts', 'filters', 'flash'],
            });
        },
    });
}
</script>

<template>
    <UiTable>
        <UiTableHeader>
            <UiTableRow>
                <UiTableHead class="w-[18%]">账号名</UiTableHead>
                <UiTableHead class="w-[16%]">客户端标识</UiTableHead>
                <UiTableHead>产品标识</UiTableHead>
                <UiTableHead>设备名称</UiTableHead>
                <UiTableHead>超级用户</UiTableHead>
                <UiTableHead>启用状态</UiTableHead>
                <UiTableHead class="w-[18%]">最近更新</UiTableHead>
                <UiTableHead class="w-[160px] text-right">操作</UiTableHead>
            </UiTableRow>
        </UiTableHeader>

        <UiTableBody>
            <UiTableRow v-for="account in accounts.data" :key="account.act_id">
                <UiTableCell>
                    <div class="space-y-1">
                        <div class="app-copy-strong font-medium">{{ account.user_name }}</div>
                        <div class="app-copy-muted text-xs">ID: {{ account.act_id }}</div>
                    </div>
                </UiTableCell>
                <UiTableCell class="app-copy-muted">{{ account.clientid || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ account.product_key || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ account.device_name || '未填写' }}</UiTableCell>
                <UiTableCell>
                    <UiBadge :variant="account.is_superuser ? 'default' : 'outline'">
                        {{ account.is_superuser_label || '未知' }}
                    </UiBadge>
                </UiTableCell>
                <UiTableCell>
                    <UiBadge :variant="account.enabled ? 'default' : 'secondary'">
                        {{ account.enabled_label || '未知' }}
                    </UiBadge>
                </UiTableCell>
                <UiTableCell class="app-copy-muted">
                    <div>{{ account.act_updated_at?.slice(0, 16).replace('T', ' ') || '-' }}</div>
                    <div class="text-xs">{{ account.act_updated_by || '系统' }}</div>
                </UiTableCell>
                <UiTableCell class="text-right">
                    <div v-if="canWrite" class="flex justify-end gap-2">
                        <UiButton as-child variant="outline" size="sm" class="rounded-lg">
                            <Link :href="route('mqtt-accounts.edit', accountRouteParams(account))" class="inline-flex items-center gap-2">
                                <PencilLine class="size-4" />
                                编辑
                            </Link>
                        </UiButton>
                        <UiButton
                            variant="outline"
                            size="sm"
                            class="rounded-lg text-red-600 hover:text-red-600"
                            @click="confirmDelete(account)"
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
