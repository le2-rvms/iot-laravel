<script setup>
defineProps({
    audits: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <UiTable>
        <UiTableHeader>
            <UiTableRow>
                <UiTableHead class="w-[16%]">时间</UiTableHead>
                <UiTableHead class="w-[12%]">事件</UiTableHead>
                <UiTableHead class="w-[14%]">资源</UiTableHead>
                <UiTableHead class="w-[10%]">资源 ID</UiTableHead>
                <UiTableHead class="w-[18%]">操作者</UiTableHead>
                <UiTableHead class="w-[22%]">变更内容</UiTableHead>
                <UiTableHead>路由</UiTableHead>
            </UiTableRow>
        </UiTableHeader>

        <UiTableBody>
            <UiTableRow v-for="audit in audits.data" :key="audit.id">
                <UiTableCell class="app-copy-muted">
                    <div class="space-y-1">
                        <div>{{ audit.created_at?.slice(0, 19).replace("T", " ") }}</div>
                        <div class="text-xs">#{{ audit.id }}</div>
                    </div>
                </UiTableCell>
                <UiTableCell>
                    <UiBadge variant="outline">
                        {{ audit.event_label }}
                    </UiBadge>
                </UiTableCell>
                <UiTableCell class="app-copy-strong font-medium">
                    {{ audit.resource_type_label }}
                </UiTableCell>
                <UiTableCell class="app-copy-muted">
                    {{ audit.auditable_id }}
                </UiTableCell>
                <UiTableCell>
                    <div v-if="audit.actor" class="space-y-1">
                        <div class="app-copy-strong font-medium">{{ audit.actor.name }}</div>
                        <div class="app-copy-muted text-xs">{{ audit.actor.email }}</div>
                    </div>
                    <span v-else class="app-copy-muted-soft text-xs">系统</span>
                </UiTableCell>
                <UiTableCell>
                    <div v-if="audit.change_summary" class="space-y-2">
                        <div class="app-copy-muted break-all rounded-lg bg-app-panel/70 px-3 py-2 font-mono text-xs">
                            {{ audit.change_summary }}
                        </div>
                        <UiBadge v-if="audit.changes_count" variant="outline">
                            {{ audit.changes_count }} 项字段变化
                        </UiBadge>
                    </div>
                    <span v-else class="app-copy-muted-soft text-xs">无字段变化</span>
                </UiTableCell>
                <UiTableCell>
                    <div class="space-y-1">
                        <UiBadge v-if="audit.method" variant="outline">
                            {{ audit.method }}
                        </UiBadge>
                        <div class="app-copy-muted break-all text-xs">
                            {{ audit.route || "-" }}
                        </div>
                    </div>
                </UiTableCell>
            </UiTableRow>
        </UiTableBody>
    </UiTable>
</template>
