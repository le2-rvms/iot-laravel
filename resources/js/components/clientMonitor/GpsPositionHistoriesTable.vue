<script setup>
function previewExtra(extra) {
    if (!extra || (typeof extra === 'object' && Object.keys(extra).length === 0)) {
        return '';
    }

    const text = typeof extra === 'string' ? extra : JSON.stringify(extra);

    return text.length > 120 ? `${text.slice(0, 120)}...` : text;
}

defineProps({
    gpsPositionHistories: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <UiTable>
        <UiTableHeader>
            <UiTableRow>
                <UiTableHead class="w-[14%]">终端信息</UiTableHead>
                <UiTableHead class="w-[14%]">定位时间</UiTableHead>
                <UiTableHead class="w-[18%]">WGS84坐标</UiTableHead>
                <UiTableHead class="w-[18%]">GCJ坐标</UiTableHead>
                <UiTableHead>速度</UiTableHead>
                <UiTableHead>方向</UiTableHead>
                <UiTableHead>状态</UiTableHead>
                <UiTableHead>告警</UiTableHead>
                <UiTableHead class="w-[14%]">创建时间</UiTableHead>
                <UiTableHead class="w-[20%]">附加信息</UiTableHead>
            </UiTableRow>
        </UiTableHeader>

        <UiTableBody>
            <UiTableRow
                v-for="(position, index) in gpsPositionHistories.data"
                :key="`${position.terminal_id}-${position.gps_time}-${index}`"
            >
                <UiTableCell>
                    <div class="space-y-1">
                        <div class="app-copy-strong font-medium">{{ position.terminal_id }}</div>
                        <div class="app-copy-muted-soft text-xs">{{ position.device?.dev_name || '未关联设备名称' }}</div>
                    </div>
                </UiTableCell>
                <UiTableCell class="app-copy-muted">{{ position.gps_time?.slice(0, 19).replace('T', ' ') || '-' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">
                    {{ position.latitude ?? '-' }}, {{ position.longitude ?? '-' }}
                </UiTableCell>
                <UiTableCell class="app-copy-muted">
                    {{ position.latitude_gcj ?? '-' }}, {{ position.longitude_gcj ?? '-' }}
                </UiTableCell>
                <UiTableCell class="app-copy-muted">{{ position.speed ?? '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ position.direction ?? '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ position.status ?? '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ position.alarm ?? '未填写' }}</UiTableCell>
                <UiTableCell class="app-copy-muted">{{ position.created_at?.slice(0, 19).replace('T', ' ') || '-' }}</UiTableCell>
                <UiTableCell>
                    <div v-if="previewExtra(position.extra)" class="app-copy-muted whitespace-pre-wrap break-words rounded-lg bg-app-panel/70 px-3 py-2 font-mono text-xs leading-6">
                        {{ previewExtra(position.extra) }}
                    </div>
                    <span v-else class="app-copy-muted-soft text-xs">无</span>
                </UiTableCell>
            </UiTableRow>
        </UiTableBody>
    </UiTable>
</template>
