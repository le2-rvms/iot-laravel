<script setup>
defineProps({
    authEvents: {
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
                <UiTableHead>鉴权结果</UiTableHead>
                <UiTableHead class="w-[18%]">原因</UiTableHead>
                <UiTableHead>客户端ID</UiTableHead>
                <UiTableHead>用户名</UiTableHead>
                <UiTableHead>对端地址</UiTableHead>
                <UiTableHead>协议</UiTableHead>
            </UiTableRow>
        </UiTableHeader>

        <UiTableBody>
            <UiTableRow v-for="event in authEvents.data" :key="event.id">
                <UiTableCell class="app-copy-muted">
                    <div>{{ event.ts?.slice(0, 19).replace('T', ' ') || '-' }}</div>
                    <div class="text-xs">#{{ event.id }}</div>
                </UiTableCell>
                <UiTableCell class="app-copy-strong font-medium">{{ event.result }}</UiTableCell>
                <UiTableCell class="app-copy-muted break-words">{{ event.reason || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ event.client_id || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ event.username || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted break-all">{{ event.peer || '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ event.protocol || '未填写' }}</UiTableCell>
            </UiTableRow>
        </UiTableBody>
    </UiTable>
</template>
