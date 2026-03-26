<script setup>
function previewExtra(extra) {
    if (!extra || (typeof extra === 'object' && Object.keys(extra).length === 0)) {
        return '';
    }

    const text = typeof extra === 'string' ? extra : JSON.stringify(extra);

    return text.length > 120 ? `${text.slice(0, 120)}...` : text;
}

defineProps({
    cmdEvents: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <UiTable>
        <UiTableHeader>
            <UiTableRow>
                <UiTableHead class="w-[16%]">事件时间</UiTableHead>
                <UiTableHead>命令类型</UiTableHead>
                <UiTableHead>客户端ID</UiTableHead>
                <UiTableHead>用户名</UiTableHead>
                <UiTableHead>对端地址</UiTableHead>
                <UiTableHead>协议</UiTableHead>
                <UiTableHead>原因码</UiTableHead>
                <UiTableHead class="w-[24%]">附加信息</UiTableHead>
            </UiTableRow>
        </UiTableHeader>

        <UiTableBody>
            <UiTableRow v-for="event in cmdEvents.data" :key="event.id">
                <UiTableCell class="app-copy-muted">
                    <div>{{ event.ts?.slice(0, 19).replace('T', ' ') || '-' }}</div>
                    <div class="text-xs">#{{ event.id }}</div>
                </UiTableCell>
                <UiTableCell class="app-copy-strong font-medium">{{ event.event_type_label || event.event_type }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ event.client_id }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ event.username || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted break-all">{{ event.peer || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ event.protocol || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ event.reason_code ?? '未填写' }}</UiTableCell>
                <UiTableCell>
                    <div v-if="previewExtra(event.extra)" class="app-copy-muted whitespace-pre-wrap break-words rounded-lg bg-app-panel/70 px-3 py-2 font-mono text-xs leading-6">
                        {{ previewExtra(event.extra) }}
                    </div>
                    <span v-else class="app-copy-muted-soft text-xs">无</span>
                </UiTableCell>
            </UiTableRow>
        </UiTableBody>
    </UiTable>
</template>
