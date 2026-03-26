<script setup>
defineProps({
    sessions: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <UiTable>
        <UiTableHeader>
            <UiTableRow>
                <UiTableHead class="w-[18%]">客户端ID</UiTableHead>
                <UiTableHead>用户名</UiTableHead>
                <UiTableHead class="w-[15%]">最近事件时间</UiTableHead>
                <UiTableHead>最近事件类型</UiTableHead>
                <UiTableHead class="w-[15%]">最近连接时间</UiTableHead>
                <UiTableHead class="w-[15%]">最近断开时间</UiTableHead>
                <UiTableHead>最近对端地址</UiTableHead>
                <UiTableHead>最近协议</UiTableHead>
                <UiTableHead>最近原因码</UiTableHead>
            </UiTableRow>
        </UiTableHeader>

        <UiTableBody>
            <UiTableRow v-for="session in sessions.data" :key="session.client_id">
                <UiTableCell class="app-copy-strong font-medium">{{ session.client_id }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ session.username || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ session.last_event_ts?.slice(0, 19).replace('T', ' ') || '-' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ session.last_event_type || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ session.last_connect_ts?.slice(0, 19).replace('T', ' ') || '-' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ session.last_disconnect_ts?.slice(0, 19).replace('T', ' ') || '-' }}</UiTableCell>
                <UiTableCell class="app-copy-muted break-all">{{ session.last_peer || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ session.last_protocol || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ session.last_reason_code ?? '未填写' }}</UiTableCell>
            </UiTableRow>
        </UiTableBody>
    </UiTable>
</template>
