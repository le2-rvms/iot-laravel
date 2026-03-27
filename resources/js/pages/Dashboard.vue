<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { hrefForRouteTarget } from '@/lib/routes';

const props = defineProps({
    hero: {
        type: Object,
        required: true,
    },
    kpis: {
        type: Array,
        default: () => [],
    },
    alertFeeds: {
        type: Object,
        required: true,
    },
    snapshots: {
        type: Object,
        required: true,
    },
    quickLinks: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbs = [
    { label: '仪表盘' },
];

function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    return String(value).replace('T', ' ').slice(0, 16);
}
</script>

<template>
    <Head title="仪表盘" />

    <AppLayout
        title="仪表盘"
        description="查看设备、客户端监控、GPS 与异常摘要的运维总览。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="space-y-6">
            <UiCard class="overflow-hidden rounded-xl border border-app-panel-border/80 bg-app-panel shadow-sm">
                <UiCardContent class="p-0">
                    <section class="grid gap-6 px-6 py-6 lg:grid-cols-[1.2fr_0.8fr] lg:px-8">
                        <div class="space-y-5">
                            <div class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-app-subtle-foreground">
                                    IoT 运维驾驶舱
                                </p>
                                <h1 class="text-3xl font-semibold tracking-tight text-app-panel-foreground lg:text-4xl">
                                    欢迎回来，{{ hero.userName || $page.props.auth.user?.name }}
                                </h1>
                                <p class="max-w-2xl text-sm leading-7 text-app-subtle-foreground">
                                    首屏聚合设备、在线会话、GPS 与异常摘要，便于快速识别当前系统态势并跳转处理。
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <UiButton
                                    v-for="(action, index) in hero.primaryActions"
                                    :key="`${action.routeName}:${action.label}`"
                                    as-child
                                    :variant="index === 0 ? 'default' : 'outline'"
                                    class="rounded-xl"
                                >
                                    <Link :href="hrefForRouteTarget(action)">{{ action.label }}</Link>
                                </UiButton>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                            <div
                                v-for="item in hero.summary"
                                :key="item.label"
                                class="rounded-xl border border-app-panel-border/70 bg-background/88 px-4 py-4 backdrop-blur"
                            >
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-app-subtle-foreground">
                                    {{ item.label }}
                                </div>
                                <div class="mt-3 flex items-end gap-3">
                                    <div class="text-3xl font-semibold tracking-tight text-app-panel-foreground">
                                        {{ item.value }}
                                    </div>
                                    <UiBadge :variant="item.tone" class="mb-1">
                                        {{ item.label }}
                                    </UiBadge>
                                </div>
                            </div>
                        </div>
                    </section>
                </UiCardContent>
            </UiCard>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <DashboardKpiCard
                    v-for="item in kpis"
                    :key="item.key"
                    :item="item"
                />
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <DashboardFeedList
                    title="GPS 告警"
                    description="优先关注最近触发的 GPS 告警记录。"
                    :items="alertFeeds.gpsAlarms"
                    empty-title="当前没有 GPS 告警"
                    empty-description="暂无 GPS 告警记录时，这里会保持清爽空态。"
                >
                    <template #item="{ item }">
                        <div class="flex items-start justify-between gap-4 px-5 py-4">
                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-app-panel-foreground">{{ item.alarm_type }}</p>
                                    <UiBadge variant="destructive">{{ item.terminal_id }}</UiBadge>
                                </div>
                                <p class="text-sm leading-6 text-app-subtle-foreground">
                                    {{ item.description || '未填写告警描述。' }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right text-xs text-app-subtle-foreground">
                                <div>{{ formatDateTime(item.gps_time) }}</div>
                                <div class="mt-1">记录于 {{ formatDateTime(item.created_at) }}</div>
                            </div>
                        </div>
                    </template>
                </DashboardFeedList>

                <DashboardFeedList
                    title="GPS 命令"
                    description="展示最近变更状态的 GPS 指令记录。"
                    :items="alertFeeds.gpsCommands"
                    empty-title="当前没有 GPS 命令"
                    empty-description="产生 GPS 指令后，会在这里显示最新执行状态。"
                >
                    <template #item="{ item }">
                        <div class="flex items-start justify-between gap-4 px-5 py-4">
                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-app-panel-foreground">{{ item.cmd_type }}</p>
                                    <UiBadge variant="outline">{{ item.status || '未填写状态' }}</UiBadge>
                                </div>
                                <p class="text-sm leading-6 text-app-subtle-foreground">
                                    终端 {{ item.terminal_id || '-' }}
                                    <span v-if="item.device_name"> · {{ item.device_name }}</span>
                                </p>
                            </div>
                            <div class="shrink-0 text-right text-xs text-app-subtle-foreground">
                                <div>#{{ item.id }}</div>
                                <div class="mt-1">{{ formatDateTime(item.updated_at || item.created_at) }}</div>
                            </div>
                        </div>
                    </template>
                </DashboardFeedList>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <DashboardFeedList
                    title="在线会话快照"
                    description="最近活跃的客户端会话摘要。"
                    :items="snapshots.sessions"
                    empty-title="当前没有在线会话"
                    empty-description="客户端建立会话后，这里会出现最新活动。"
                >
                    <template #item="{ item }">
                        <div class="flex items-start justify-between gap-4 px-5 py-4">
                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-app-panel-foreground">{{ item.client_id }}</p>
                                    <UiBadge variant="secondary">{{ item.last_event_type || '未填写事件' }}</UiBadge>
                                </div>
                                <p class="text-sm leading-6 text-app-subtle-foreground">
                                    {{ item.username || '未填写用户名' }} · {{ item.last_protocol || '未填写协议' }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right text-xs text-app-subtle-foreground">
                                {{ formatDateTime(item.last_event_ts) }}
                            </div>
                        </div>
                    </template>
                </DashboardFeedList>

                <DashboardFeedList
                    title="定位快照"
                    description="最近更新的终端定位记录。"
                    :items="snapshots.positions"
                    empty-title="当前没有定位快照"
                    empty-description="终端上报定位后，这里会显示最新快照。"
                >
                    <template #item="{ item }">
                        <div class="flex items-start justify-between gap-4 px-5 py-4">
                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-app-panel-foreground">{{ item.terminal_id }}</p>
                                    <UiBadge variant="outline">状态 {{ item.status ?? '-' }}</UiBadge>
                                    <UiBadge variant="secondary">告警 {{ item.alarm ?? '-' }}</UiBadge>
                                </div>
                                <p class="text-sm leading-6 text-app-subtle-foreground">
                                    <span v-if="item.device_name">{{ item.device_name }} · </span>
                                    速度 {{ item.speed ?? '未填写' }} · 定位 {{ formatDateTime(item.gps_time) }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right text-xs text-app-subtle-foreground">
                                {{ formatDateTime(item.updated_at) }}
                            </div>
                        </div>
                    </template>
                </DashboardFeedList>
            </section>

            <DashboardQuickLinksGrid :links="quickLinks" />
        </div>
    </AppLayout>
</template>
