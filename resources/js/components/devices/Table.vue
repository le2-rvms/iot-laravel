<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { PencilLine, Trash2 } from 'lucide-vue-next';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

defineProps({
    devices: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const confirmDialog = useConfirmDialog();
const canWrite = computed(() => page.props.auth?.access?.['device.write'] ?? false);

function confirmDelete(device) {
    confirmDialog.open({
        title: '删除设备',
        description: `确定删除设备 ${device.dev_name} (${device.terminal_id}) 吗？此操作不可撤销。`,
        confirmLabel: '确认删除',
        cancelLabel: '取消',
        variant: 'destructive',
        onConfirm: () => {
            router.delete(`/admin/devices/${device.terminal_id}`, {
                preserveScroll: true,
                only: ['devices', 'filters', 'flash'],
            });
        },
    });
}
</script>

<template>
    <UiTable>
        <UiTableHeader>
            <UiTableRow>
                <UiTableHead class="w-[16%]">终端ID</UiTableHead>
                <UiTableHead class="w-[14%]">设备名称</UiTableHead>
                <UiTableHead>产品标识</UiTableHead>
                <UiTableHead>SIM号</UiTableHead>
                <UiTableHead>设备状态</UiTableHead>
                <UiTableHead>审核状态</UiTableHead>
                <UiTableHead>城市关联ID</UiTableHead>
                <UiTableHead class="w-[16%]">创建时间</UiTableHead>
                <UiTableHead class="w-[160px] text-right">操作</UiTableHead>
            </UiTableRow>
        </UiTableHeader>

        <UiTableBody>
            <UiTableRow v-for="device in devices.data" :key="device.terminal_id">
                <UiTableCell>
                    <div class="space-y-1">
                        <div class="app-copy-strong font-medium">{{ device.terminal_id }}</div>
                        <div class="app-copy-muted text-xs">ID: {{ device.dev_id ?? '-' }}</div>
                    </div>
                </UiTableCell>
                <UiTableCell>{{ device.dev_name }}</UiTableCell>
                <UiTableCell>
                    <div class="space-y-1">
                        <div class="app-copy-muted">{{ device.product_key || '未填写' }}</div>
                        <div class="app-copy-muted-soft text-xs">{{ device.device_product?.product_name || '' }}</div>
                    </div>
                </UiTableCell>
                <UiTableCell class="app-copy-muted">{{ device.sim_number || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ device.device_status || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ device.review_status || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ device.city_relation_id ?? '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ device.created_at?.slice(0, 16).replace('T', ' ') || '-' }}</UiTableCell>
                <UiTableCell class="text-right">
                    <div v-if="canWrite" class="flex justify-end gap-2">
                        <UiButton as-child variant="outline" size="sm" class="rounded-lg">
                            <Link :href="`/admin/devices/${device.terminal_id}/edit`" class="inline-flex items-center gap-2">
                                <PencilLine class="size-4" />
                                编辑
                            </Link>
                        </UiButton>
                        <UiButton
                            variant="outline"
                            size="sm"
                            class="rounded-lg text-red-600 hover:text-red-600"
                            @click="confirmDelete(device)"
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
