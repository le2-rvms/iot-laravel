<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { hrefForRouteTarget, route } from '@/lib/routes';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    sections: {
        type: Array,
        required: true,
    },
    deviceContext: {
        type: Object,
        required: true,
    },
    previews: {
        type: Object,
        required: true,
    },
});

function previewExtra(extra) {
    if (!extra || (typeof extra === 'object' && Object.keys(extra).length === 0)) {
        return '';
    }

    const text = typeof extra === 'string' ? extra : JSON.stringify(extra);

    return text.length > 240 ? `${text.slice(0, 240)}...` : text;
}

const breadcrumbs = [
    { label: '仪表盘', href: route('dashboard') },
    { label: '客户端监控', href: hrefForRouteTarget(props.deviceContext) },
];
</script>

<template>
    <Head title="客户端监控" />

    <AppLayout
        title="客户端监控"
        description="查看客户端在线会话、鉴权结果、命令事件和连接事件。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <AppPageToolbar
                title="客户端事件总览"
                :description="`查看 Client ID ${filters.client_id__eq} 的最新在线会话、鉴权、命令和连接事件。`"
            />

            <ClientMonitorDeviceContextBanner :device-context="deviceContext" :client-id="filters.client_id__eq" />

            <div class="grid gap-6">
                <UiCard class="min-w-0 overflow-hidden rounded-[1.5rem] border border-app-panel-border/80">
                    <UiCardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div class="space-y-2">
                            <UiCardTitle class="text-lg">在线会话</UiCardTitle>
                            <UiCardDescription>展示当前 Client ID 最近 5 条在线会话记录。</UiCardDescription>
                        </div>
                        <UiButton as-child variant="outline" size="sm" class="rounded-xl">
                            <Link :href="hrefForRouteTarget(sections[0])">查看更多</Link>
                        </UiButton>
                    </UiCardHeader>
                    <UiCardContent class="pt-0">
                        <ClientMonitorSessionsTable
                            v-if="previews?.sessions?.length"
                            :sessions="{ data: previews.sessions }"
                        />
                        <AppEmptyState
                            v-else
                            title="当前 Client ID 还没有在线会话"
                            description="客户端产生活跃会话后，会在这里显示最新记录。"
                        />
                    </UiCardContent>
                </UiCard>

                <UiCard class="min-w-0 overflow-hidden rounded-[1.5rem] border border-app-panel-border/80">
                    <UiCardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div class="space-y-2">
                            <UiCardTitle class="text-lg">鉴权事件</UiCardTitle>
                            <UiCardDescription>展示当前 Client ID 最近 5 条鉴权事件。</UiCardDescription>
                        </div>
                        <UiButton as-child variant="outline" size="sm" class="rounded-xl">
                            <Link :href="hrefForRouteTarget(sections[1])">查看更多</Link>
                        </UiButton>
                    </UiCardHeader>
                    <UiCardContent class="pt-0">
                        <ClientMonitorAuthEventsTable
                            v-if="previews?.authEvents?.length"
                            :auth-events="{ data: previews.authEvents }"
                        />
                        <AppEmptyState
                            v-else
                            title="当前 Client ID 还没有鉴权事件"
                            description="客户端发生鉴权成功或失败后，会在这里显示最新记录。"
                        />
                    </UiCardContent>
                </UiCard>

                <UiCard class="min-w-0 overflow-hidden rounded-[1.5rem] border border-app-panel-border/80">
                    <UiCardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div class="space-y-2">
                            <UiCardTitle class="text-lg">命令事件</UiCardTitle>
                            <UiCardDescription>展示当前 Client ID 最近 5 条命令事件。</UiCardDescription>
                        </div>
                        <UiButton as-child variant="outline" size="sm" class="rounded-xl">
                            <Link :href="hrefForRouteTarget(sections[2])">查看更多</Link>
                        </UiButton>
                    </UiCardHeader>
                    <UiCardContent class="pt-0">
                        <ClientMonitorCmdEventsTable
                            v-if="previews?.cmdEvents?.length"
                            :cmd-events="{ data: previews.cmdEvents }"
                        />
                        <AppEmptyState
                            v-else
                            title="当前 Client ID 还没有命令事件"
                            description="客户端发生命令上下行后，会在这里显示最新记录。"
                        />
                    </UiCardContent>
                </UiCard>

                <UiCard class="min-w-0 overflow-hidden rounded-[1.5rem] border border-app-panel-border/80">
                    <UiCardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div class="space-y-2">
                            <UiCardTitle class="text-lg">连接事件</UiCardTitle>
                            <UiCardDescription>展示当前 Client ID 最近 5 条连接事件。</UiCardDescription>
                        </div>
                        <UiButton as-child variant="outline" size="sm" class="rounded-xl">
                            <Link :href="hrefForRouteTarget(sections[3])">查看更多</Link>
                        </UiButton>
                    </UiCardHeader>
                    <UiCardContent class="pt-0">
                        <ClientMonitorConnEventsTable
                            v-if="previews?.connEvents?.length"
                            :conn-events="{ data: previews.connEvents }"
                        />
                        <AppEmptyState
                            v-else
                            title="当前 Client ID 还没有连接事件"
                            description="客户端建立或断开连接后，会在这里显示最新记录。"
                        />
                    </UiCardContent>
                </UiCard>

                <UiCard class="min-w-0 overflow-hidden rounded-[1.5rem] border border-app-panel-border/80">
                    <UiCardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div class="space-y-2">
                            <UiCardTitle class="text-lg">当前定位</UiCardTitle>
                            <UiCardDescription>展示当前 Client ID 的最新定位记录。</UiCardDescription>
                        </div>
                        <UiButton as-child variant="outline" size="sm" class="rounded-xl">
                            <Link :href="hrefForRouteTarget(sections[4])">查看更多</Link>
                        </UiButton>
                    </UiCardHeader>
                    <UiCardContent class="pt-0">
                        <div
                            v-if="previews?.gpsPositionLast"
                            class="grid gap-4 rounded-[1.25rem] border border-app-panel-border/70 bg-app-panel/50 p-5 md:grid-cols-2 xl:grid-cols-4"
                        >
                            <div class="space-y-1">
                                <div class="app-copy-muted text-xs">终端ID</div>
                                <div class="app-copy-strong font-medium">{{ previews.gpsPositionLast.terminal_id }}</div>
                                <div class="app-copy-muted-soft text-xs">{{ previews.gpsPositionLast.device?.dev_name || '未关联设备名称' }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="app-copy-muted text-xs">定位时间</div>
                                <div class="font-medium">{{ previews.gpsPositionLast.gps_time?.slice(0, 19).replace('T', ' ') || '-' }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="app-copy-muted text-xs">状态 / 告警</div>
                                <div class="font-medium">{{ previews.gpsPositionLast.status ?? '未填写' }} / {{ previews.gpsPositionLast.alarm ?? '未填写' }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="app-copy-muted text-xs">更新时间</div>
                                <div class="font-medium">{{ previews.gpsPositionLast.updated_at?.slice(0, 19).replace('T', ' ') || '-' }}</div>
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <div class="app-copy-muted text-xs">WGS84坐标</div>
                                <div class="font-medium">
                                    {{ previews.gpsPositionLast.latitude ?? '-' }}, {{ previews.gpsPositionLast.longitude ?? '-' }}
                                </div>
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <div class="app-copy-muted text-xs">GCJ坐标</div>
                                <div class="font-medium">
                                    {{ previews.gpsPositionLast.latitude_gcj ?? '-' }}, {{ previews.gpsPositionLast.longitude_gcj ?? '-' }}
                                </div>
                            </div>
                            <div class="space-y-1">
                                <div class="app-copy-muted text-xs">速度</div>
                                <div class="font-medium">{{ previews.gpsPositionLast.speed ?? '未填写' }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="app-copy-muted text-xs">方向</div>
                                <div class="font-medium">{{ previews.gpsPositionLast.direction ?? '未填写' }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="app-copy-muted text-xs">海拔</div>
                                <div class="font-medium">{{ previews.gpsPositionLast.altitude ?? '未填写' }}</div>
                            </div>
                            <div class="space-y-1 xl:col-span-4">
                                <div class="app-copy-muted text-xs">附加信息</div>
                                <div
                                    v-if="previewExtra(previews.gpsPositionLast.extra)"
                                    class="app-copy-muted whitespace-pre-wrap break-words rounded-lg bg-app-panel/70 px-3 py-2 font-mono text-xs leading-6"
                                >
                                    {{ previewExtra(previews.gpsPositionLast.extra) }}
                                </div>
                                <div v-else class="app-copy-muted-soft text-xs">无</div>
                            </div>
                        </div>
                        <AppEmptyState
                            v-else
                            title="当前 Client ID 还没有当前定位"
                            description="终端上报最新定位后，会在这里显示当前记录。"
                        />
                    </UiCardContent>
                </UiCard>

                <UiCard class="min-w-0 overflow-hidden rounded-[1.5rem] border border-app-panel-border/80">
                    <UiCardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div class="space-y-2">
                            <UiCardTitle class="text-lg">定位历史</UiCardTitle>
                            <UiCardDescription>展示当前 Client ID 最近 5 条定位历史记录。</UiCardDescription>
                        </div>
                        <UiButton as-child variant="outline" size="sm" class="rounded-xl">
                            <Link :href="hrefForRouteTarget(sections[5])">查看更多</Link>
                        </UiButton>
                    </UiCardHeader>
                    <UiCardContent class="pt-0">
                        <ClientMonitorGpsPositionHistoriesTable
                            v-if="previews?.gpsPositionHistories?.length"
                            :gps-position-histories="{ data: previews.gpsPositionHistories }"
                        />
                        <AppEmptyState
                            v-else
                            title="当前 Client ID 还没有定位历史"
                            description="终端持续上报定位后，会在这里显示最新历史记录。"
                        />
                    </UiCardContent>
                </UiCard>
            </div>
        </div>
    </AppLayout>
</template>
