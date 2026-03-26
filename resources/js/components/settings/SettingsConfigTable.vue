<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { PencilLine, Trash2 } from 'lucide-vue-next';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { route } from '@/lib/routes';

const props = defineProps({
    configs: {
        type: Object,
        required: true,
    },
    resource: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const confirmDialog = useConfirmDialog();
const canWrite = computed(() => page.props.auth?.access?.[props.resource.write_permission] ?? false);

function confirmDelete(config) {
    confirmDialog.open({
        title: `删除${props.resource.title}`,
        description: `确定删除配置键 ${config.key} 吗？`,
        confirmLabel: '确认删除',
        cancelLabel: '取消',
        variant: 'destructive',
        onConfirm: () => {
            router.delete(route(props.resource.destroy_route, config), {
                preserveScroll: true,
                only: ['configs', 'filters', 'flash'],
            });
        },
    });
}
</script>

<template>
    <UiTable>
        <UiTableHeader>
            <UiTableRow>
                <UiTableHead class="w-[18%]">配置键</UiTableHead>
                <UiTableHead class="w-[22%]">配置值</UiTableHead>
                <UiTableHead>分类</UiTableHead>
                <UiTableHead>是否打码</UiTableHead>
                <UiTableHead class="w-[22%]">备注</UiTableHead>
                <UiTableHead class="w-[160px] text-right">操作</UiTableHead>
            </UiTableRow>
        </UiTableHeader>

        <UiTableBody>
            <UiTableRow v-for="config in configs.data" :key="config.id">
                <UiTableCell>
                    <div class="space-y-1">
                        <div class="app-copy-strong font-medium break-all">{{ config.key }}</div>
                    </div>
                </UiTableCell>
                <UiTableCell class="app-copy-muted break-all">{{ config.value_display }}</UiTableCell>
                <UiTableCell>{{ config.category_label }}</UiTableCell>
                <UiTableCell>
                    <UiBadge :variant="Boolean(config.is_masked) ? 'secondary' : 'outline'">
                        {{ config.is_masked_label }}
                    </UiBadge>
                </UiTableCell>
                <UiTableCell class="app-copy-muted break-words">{{ config.remark }}</UiTableCell>
                <UiTableCell class="text-right">
                    <div v-if="canWrite" class="flex justify-end gap-2">
                        <UiButton as-child variant="outline" size="sm" class="rounded-lg">
                            <Link :href="route(resource.edit_route, config)" class="inline-flex items-center gap-2">
                                <PencilLine class="size-4" />
                                编辑
                            </Link>
                        </UiButton>
                        <UiButton
                            variant="outline"
                            size="sm"
                            class="rounded-lg text-red-600 hover:text-red-600"
                            @click="confirmDelete(config)"
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
