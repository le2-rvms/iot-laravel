<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { PencilLine, Trash2 } from 'lucide-vue-next';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

defineProps({
    products: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const confirmDialog = useConfirmDialog();
const canWrite = computed(() => page.props.auth?.access?.['device-product.write'] ?? false);

function confirmDelete(product) {
    confirmDialog.open({
        title: '删除设备产品',
        description: `确定删除设备产品 ${product.product_name} 吗？此操作不可撤销。`,
        confirmLabel: '确认删除',
        cancelLabel: '取消',
        variant: 'destructive',
        onConfirm: () => {
            router.delete(`/admin/device-products/${product.product_id}`, {
                preserveScroll: true,
                only: ['products', 'filters', 'flash'],
            });
        },
    });
}
</script>

<template>
    <UiTable>
        <UiTableHeader>
            <UiTableRow>
                <UiTableHead class="w-[10%]">产品ID</UiTableHead>
                <UiTableHead class="w-[14%]">产品标识</UiTableHead>
                <UiTableHead class="w-[16%]">产品名称</UiTableHead>
                <UiTableHead>厂商</UiTableHead>
                <UiTableHead>协议</UiTableHead>
                <UiTableHead>分类</UiTableHead>
                <UiTableHead>关联设备数</UiTableHead>
                <UiTableHead>关联分组数</UiTableHead>
                <UiTableHead class="w-[16%]">创建时间</UiTableHead>
                <UiTableHead class="w-[160px] text-right">操作</UiTableHead>
            </UiTableRow>
        </UiTableHeader>

        <UiTableBody>
            <UiTableRow v-for="product in products.data" :key="product.product_id">
                <UiTableCell class="app-copy-muted">{{ product.product_id }}</UiTableCell>
                <UiTableCell>
                    <div class="space-y-1">
                        <div class="app-copy-strong font-medium">{{ product.product_key }}</div>
                    </div>
                </UiTableCell>
                <UiTableCell>{{ product.product_name }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ product.manufacturer || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ product.protocol || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ product.category || '未填写' }}</UiTableCell>
                <UiTableCell>{{ product.devices_count }}</UiTableCell>
                <UiTableCell>{{ product.groups_count }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ product.created_at?.slice(0, 16).replace('T', ' ') || '-' }}</UiTableCell>
                <UiTableCell class="text-right">
                    <div v-if="canWrite" class="flex justify-end gap-2">
                        <UiButton as-child variant="outline" size="sm" class="rounded-lg">
                            <Link :href="`/admin/device-products/${product.product_id}/edit`" class="inline-flex items-center gap-2">
                                <PencilLine class="size-4" />
                                编辑
                            </Link>
                        </UiButton>
                        <UiButton
                            variant="outline"
                            size="sm"
                            class="rounded-lg text-red-600 hover:text-red-600"
                            @click="confirmDelete(product)"
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
